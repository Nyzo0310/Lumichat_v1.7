<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\AppointmentRepositoryInterface;
use App\Repositories\Contracts\CourseAnalyticsRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppointmentRepository implements AppointmentRepositoryInterface
{
    private const TABLE          = 'tbl_appointments';
    private const STUDENTS_TABLE = 'tbl_users';       // your schema
    private const COUNS_TABLE    = 'tbl_counselors';

    public function __construct(
        protected CourseAnalyticsRepositoryInterface $analytics
    ) {}

    public function all(): Collection
    {
        return $this->baseSelect()->orderByDesc('a.scheduled_at')->get();
    }

    public function paginateWithNames(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $q = $this->baseSelect();
        $this->applyCommonFilters($q, $filters);

        // optional text search (by counselor name by default; extend as needed)
        if (!empty($filters['q'])) {
            $term = '%' . trim((string)$filters['q']) . '%';
            $q->where(function ($w) use ($term) {
                $w->where('c.name', 'like', $term)
                ->orWhere('u.name', 'like', $term);
            });
        }

        return $q->orderBy('a.scheduled_at', 'desc')
                 ->paginate($perPage)
                 ->withQueryString();
    }

    public function findDetailedById(int $id): ?object
    {
        return $this->baseSelectForShow()
            ->where('a.id', $id)
            ->first();
    }

    public function findById(int $id, array $with = []): ?object
    {
        return DB::table(self::TABLE)->where('id', $id)->first();
    }

    public function create(array $data): object
    {
        $id = DB::table(self::TABLE)->insertGetId($data);
        return $this->findById($id);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) DB::table(self::TABLE)->where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) DB::table(self::TABLE)->where('id', $id)->delete();
    }

    public function firstAppointmentYearForStudent(int $studentId): ?int
    {
        $y = DB::table(self::TABLE)
            ->where('student_id', $studentId)
            ->whereNotNull('scheduled_at')
            ->selectRaw('MIN(YEAR(scheduled_at)) as y')
            ->value('y');

        return $y ? (int)$y : null;
    }

    public function monthlyCountsForStudent(int $studentId, int $year): array
    {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = Carbon::create($year, 12, 31)->endOfDay();

        return DB::table(self::TABLE)
            ->where('student_id', $studentId)
            ->whereBetween('scheduled_at', [$start, $end])
            ->whereNotNull('scheduled_at')
            ->selectRaw('MONTH(scheduled_at) AS m, COUNT(*) AS c')
            ->groupByRaw('MONTH(scheduled_at)')
            ->orderByRaw('m')
            ->pluck('c', 'm')
            ->toArray();
    }

    public function statsByStatus(array $filters = []): array
    {
        $q = DB::table(self::TABLE);
        $this->applyCommonFilters($q, $filters); // do not filter by 'status' here

        return $q->selectRaw('status, COUNT(*) as total')
                 ->groupBy('status')
                 ->pluck('total', 'status')
                 ->toArray();
    }

    public function saveFinalReport(int $appointmentId, string $diagnosis, ?string $finalNote, int $finalizedBy): array
    {
        $ap = DB::table(self::TABLE)->where('id', $appointmentId)->first();
        if (!$ap) {
            return ['ok' => false, 'reason' => 'not_found'];
        }
        if ($ap->status !== 'completed') {
            return ['ok' => false, 'reason' => 'not_completed'];
        }

        DB::transaction(function () use ($appointmentId, $ap, $diagnosis, $finalNote, $finalizedBy) {
            DB::table(self::TABLE)
                ->where('id', $appointmentId)
                ->update([
                    'final_note'   => $finalNote,
                    'finalized_by' => $finalizedBy,
                    'finalized_at' => now(),
                    'updated_at'   => now(),
                ]);

            DB::table('tbl_diagnosis_reports')->insert([
                'student_id'       => $ap->student_id,
                'counselor_id'     => $ap->counselor_id,
                'diagnosis_result' => $diagnosis,
                'notes'            => $finalNote,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // refresh analytics for the student’s course/year
            $this->analytics->refreshForStudent((int) $ap->student_id);
        });

        return ['ok' => true];
    }

    public function updateStatusByAction(int $appointmentId, string $action): array
    {
        $map = ['confirm' => 'confirmed', 'done' => 'completed'];
        if (!isset($map[$action])) {
            return ['ok' => false, 'reason' => 'invalid_action'];
        }

        $newStatus = $map[$action];

        if ($newStatus === 'completed') {
            $row = DB::table(self::TABLE)
                ->select('status', 'scheduled_at')
                ->where('id', $appointmentId)
                ->first();

            if (!$row) {
                return ['ok' => false, 'reason' => 'not_found'];
            }
            if ($row->status !== 'confirmed') {
                return ['ok' => false, 'reason' => 'must_be_confirmed'];
            }
            if (Carbon::parse($row->scheduled_at)->isFuture()) {
                return ['ok' => false, 'reason' => 'too_early'];
            }
        }

        DB::table(self::TABLE)
            ->where('id', $appointmentId)
            ->update([
                'status'     => $newStatus,
                'updated_at' => now(),
            ]);

        return ['ok' => true];
    }

    /* ===================== Helpers ===================== */

   // App\Repositories\Eloquent\AppointmentRepository.php

    protected function baseSelect(): Builder
    {
        return DB::table(self::TABLE.' as a')
            ->leftJoin(self::COUNS_TABLE.' as c', 'c.id', '=', 'a.counselor_id') // <— leftJoin
            ->join(self::STUDENTS_TABLE.' as u', 'u.id', '=', 'a.student_id')
            ->select([
                'a.id','a.scheduled_at','a.created_at as booked_at','a.status',
                DB::raw('COALESCE(c.name,"—") as counselor_name'),
                'u.name as student_name',
            ]);
    }

    protected function baseSelectForShow(): Builder
    {
        return DB::table(self::TABLE.' as a')
            ->leftJoin(self::COUNS_TABLE.' as c', 'c.id', '=', 'a.counselor_id') // <— leftJoin
            ->join(self::STUDENTS_TABLE.' as u', 'u.id', '=', 'a.student_id')
            ->select([
                'a.*',
                'c.name  as counselor_name','c.email as counselor_email','c.phone as counselor_phone',
                'u.name  as student_name','u.email as student_email',
            ]);
    }


    protected function applyCommonFilters(Builder $q, array $filters): void
    {
        // status
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $q->where('a.status', $filters['status']);
        }

        // period
        $period = $filters['period'] ?? 'all';
        if ($period && $period !== 'all') {
            $now = Carbon::now();
            if ($period === 'upcoming') {
                $q->where('a.scheduled_at', '>=', $now);
            } elseif ($period === 'today') {
                $q->whereDate('a.scheduled_at', $now->toDateString());
            } elseif ($period === 'this_week') {
                $q->whereBetween('a.scheduled_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
            } elseif ($period === 'this_month') {
                $q->whereBetween('a.scheduled_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
            } elseif ($period === 'past') {
                $q->where('a.scheduled_at', '<', $now);
            }
        }
    }

    // ==== NEW: who is free at a specific datetime (HH:MM slot) ====
    public function counselorIdsFreeAt(Carbon $scheduledAt): array
    {
        // 30-min slot end
        $slotEnd = $scheduledAt->copy()->addMinutes(30);

        // Use 1..7 (Mon..Sun) to match the rest of the codebase and DB data
        $dow = (int) $scheduledAt->isoWeekday();

        // Base date used to stitch time strings into a datetime
        $dateStr = $scheduledAt->toDateString();

        $cids = DB::table('tbl_counselors')->where('is_active', 1)->pluck('id')->all();
        if (empty($cids)) return [];

        $free = [];

        foreach ($cids as $cid) {
            // Get availability ranges for that weekday, ignore rows with NULL/empty times
            $ranges = DB::table('tbl_counselor_availabilities')
                ->where('counselor_id', $cid)
                ->where('weekday', $dow)           // 1..7 ✅
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->get(['start_time','end_time']);

            // Must fit in at least one range
            $fits = false;
            foreach ($ranges as $r) {
                // Skip malformed rows safely
                if (!\is_string($r->start_time) || !\is_string($r->end_time) ||
                    $r->start_time === '' || $r->end_time === '') {
                    continue;
                }

                // Protect against parse errors
                try {
                    $rangeStart = Carbon::parse($dateStr.' '.$r->start_time);
                    $rangeEnd   = Carbon::parse($dateStr.' '.$r->end_time);
                } catch (\Throwable $e) {
                    // Malformed time; ignore this range
                    continue;
                }

                // Slot is within availability window?
                if ($scheduledAt->gte($rangeStart) && $slotEnd->lte($rangeEnd)) {
                    $fits = true;
                    break;
                }
            }

            if (!$fits) {
                continue;
            }

            // Not already taken at that exact slot?
            $taken = DB::table('tbl_appointments')
                ->where('counselor_id', $cid)
                ->where('scheduled_at', $scheduledAt)
                ->whereIn('status', ['pending','confirmed','completed'])
                ->exists();

            if (!$taken) {
                $free[] = (int) $cid;
            }
        }

        return $free;
    }

    // ==== NEW: single counselor availability check ====
    public function counselorIsFreeAt(int $counselorId, Carbon $scheduledAt): bool
    {
        return \in_array($counselorId, $this->counselorIdsFreeAt($scheduledAt), true);
    }

    // ==== NEW: assign (or reassign) counselor to an appointment ====
    public function assignCounselor(int $appointmentId, int $counselorId): array
    {
        $ap = DB::table(self::TABLE)->where('id', $appointmentId)->first();
        if (!$ap) return ['ok'=>false, 'reason'=>'not_found'];

        $when = Carbon::parse($ap->scheduled_at);
        if ($when->lte(now())) return ['ok'=>false, 'reason'=>'in_past'];

        if (!$this->counselorIsFreeAt($counselorId, $when)) {
            return ['ok'=>false, 'reason'=>'not_available'];
        }

        try {
            DB::table(self::TABLE)
                ->where('id', $appointmentId)
                ->update([
                    'counselor_id' => $counselorId,
                    'updated_at'   => now(),
                ]);
            return ['ok'=>true];
        } catch (\Illuminate\Database\QueryException $e) {
            // 1062: unique key (counselor_id, scheduled_at) race condition
            $code = (int)($e->errorInfo[1] ?? 0);
            if ($code === 1062) return ['ok'=>false, 'reason'=>'race_taken'];
            return ['ok'=>false, 'reason'=>'db_error'];
        }
    }

}
