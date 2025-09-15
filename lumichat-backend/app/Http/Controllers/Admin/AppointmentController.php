<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    // ==== Flash keys (deduped) ====
    private const FLASH_SWAL = 'swal';

    // ==== Allowed filters (no behavior change) ====
    private const STATUS_ALL    = 'all';
    private const PERIOD_ALL    = 'all';
    private const STATUSES      = ['pending', 'confirmed', 'canceled', 'completed'];
    private const PERIODS       = ['all', 'upcoming', 'today', 'this_week', 'this_month', 'past'];

    // ==== Status action map (no behavior change) ====
    private const ACTION_TO_STATUS = [
        'confirm' => 'confirmed',
        'done'    => 'completed',
    ];

    /**
     * List appointments with optional status/period filters and search.
     */
    public function index(Request $r): View
    {
        $status = \in_array($r->query('status'), self::STATUSES, true)
            ? $r->query('status')
            : self::STATUS_ALL;

        $period = \in_array($r->query('period'), self::PERIODS, true)
            ? $r->query('period')
            : self::PERIOD_ALL;

        $q   = \trim((string) $r->query('q', ''));
        $now = Carbon::now();

        $appointments = DB::table('tbl_appointments as a')
            ->join('tbl_counselors as c', 'c.id', '=', 'a.counselor_id')
            ->join('tbl_users as u', 'u.id', '=', 'a.student_id')
            ->select([
                'a.id',
                'a.scheduled_at',
                'a.created_at as booked_at', // “Booked On”
                'a.status',
                'c.name as counselor_name',
                'u.name as student_name',
            ])
            ->when($status !== self::STATUS_ALL, fn ($qb) => $qb->where('a.status', $status))
            ->when($period !== self::PERIOD_ALL, fn ($qb) => $this->applyPeriodFilter($qb, $period, $now))
            ->when($q !== '', fn ($qb) => $qb->where('c.name', 'like', '%' . $q . '%'))
            ->orderBy('a.scheduled_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.appointments.index', compact('appointments', 'status', 'period', 'q'));
    }

    /**
     * Persist final diagnosis report for a completed appointment.
     */
  public function saveReport(Request $r, int $id): RedirectResponse
{
    $data = $r->validate([
        'diagnosis'  => ['required', 'string', 'max:20000'],
        'final_note' => ['nullable', 'string', 'max:20000'],
    ]);

    $ap = DB::table('tbl_appointments')->where('id', $id)->first();
    abort_unless($ap, 404);

    if ($ap->status !== 'completed') {
        return back()->with(self::FLASH_SWAL, [
            'icon'  => 'warning',
            'title' => 'Not allowed',
            'text'  => 'You can save the diagnosis only for completed appointments.',
        ]);
    }

    DB::transaction(function () use ($id, $ap, $r, $data) {
        // keep original updates
        DB::table('tbl_appointments')
            ->where('id', $id)
            ->update([
                'final_note'   => $r->input('final_note'),
                'finalized_by' => auth()->id(),
                'finalized_at' => now(),
                'updated_at'   => now(),
            ]);

        DB::table('tbl_diagnosis_reports')->insert([
            'student_id'       => $ap->student_id,
            'counselor_id'     => $ap->counselor_id,
            'diagnosis_result' => $data['diagnosis'],
            'notes'            => $data['final_note'] ?? null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // NEW: refresh course analytics for this student
        $this->refreshCourseAnalyticsForStudent((int) $ap->student_id);
    });

    return back()->with(self::FLASH_SWAL, [
        'icon'  => 'success',
        'title' => 'Saved',
        'text'  => 'Diagnosis report has been saved.',
    ]);
}

    /**
     * Show appointment details + latest report for that student/counselor pair.
     */
    public function show(int $id): View
    {
        $row = DB::table('tbl_appointments as a')
            ->join('tbl_counselors as c', 'c.id', '=', 'a.counselor_id')
            ->join('tbl_users as u', 'u.id', '=', 'a.student_id')
            ->select([
                'a.*', // includes created_at, student_id, counselor_id, etc.
                'c.name  as counselor_name',
                'c.email as counselor_email',
                'c.phone as counselor_phone',
                'u.name  as student_name',
                'u.email as student_email',
            ])
            ->where('a.id', $id)
            ->first();

        abort_unless($row, 404);

        $latestReport = DB::table('tbl_diagnosis_reports')
            ->where('student_id', $row->student_id)
            ->where('counselor_id', $row->counselor_id)
            ->orderByDesc('id')
            ->first();

        return view('admin.appointments.show', [
            'appointment'  => $row,
            'latestReport' => $latestReport,
        ]);
    }

    public function updateStatus(Request $r, int $id)
{
    
    $map = ['confirm' => 'confirmed', 'done' => 'completed'];
    $newStatus = $map[$r->input('action')];

        if ($newStatus === 'completed') {
            // fetch both status and scheduled_at
            $row = DB::table('tbl_appointments')
                ->select('status', 'scheduled_at')
                ->where('id', $id)
                ->first();

            if (!$row || $row->status !== 'confirmed') {
                return back()->with(self::FLASH_SWAL, [
                    'icon'  => 'warning',
                    'title' => 'Not allowed',
                    'text'  => 'Appointment must be confirmed before you can mark it as done.',
                ]);
            }

            // prevent completion before the start time
            if (Carbon::parse($row->scheduled_at)->isFuture()) {
                return back()->with(self::FLASH_SWAL, [
                    'icon'  => 'warning',
                    'title' => 'Too early',
                    'text'  => 'You can only mark the appointment as done once it has started.',
                ]);
            }
        }

        DB::table('tbl_appointments')
            ->where('id', $id)
            ->update([
                'status'     => $newStatus,
                'updated_at' => now(),
            ]);

        return back()->with(self::FLASH_SWAL, [
            'icon'  => 'success',
            'title' => 'Updated',
            'text'  => 'Appointment status has been updated.',
        ]);
    }

    // ==== Private helpers (presentation-only / no logic change) ====

    /**
     * Apply the period date filter to the query builder.
     * (Keeps original branching/logic exactly the same.)
     */
    private function applyPeriodFilter($qb, string $period, Carbon $now)
    {
        if ($period === 'upcoming') {
            $qb->where('a.scheduled_at', '>=', $now);
        } elseif ($period === 'today') {
            $qb->whereDate('a.scheduled_at', $now->toDateString());
        } elseif ($period === 'this_week') {
            $qb->whereBetween('a.scheduled_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
        } elseif ($period === 'this_month') {
            $qb->whereBetween('a.scheduled_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
        } elseif ($period === 'past') {
            $qb->where('a.scheduled_at', '<', $now);
        }

        return $qb;
    }
    /**
 * Ensure tbl_course_analytics has a fresh row for the student's course/year:
 * - course & year_level are taken from tbl_users
 * - total_students is the live count of users in that course/year
 * - common_diagnosis is the top 8 diagnosis results (JSON array of labels)
 */
private function refreshCourseAnalyticsForStudent(int $studentId): void
{
    $student = DB::table('tbl_users')
        ->select('course', 'year_level')
        ->where('id', $studentId)
        ->first();

    if (!$student || empty($student->course) || empty($student->year_level)) {
        return; // nothing to aggregate
    }

    $course    = (string) $student->course;
    $yearLevel = (string) $student->year_level;

    // live count of students in this course/year
    $totalStudents = (int) DB::table('tbl_users')
        ->where('course', $course)
        ->where('year_level', $yearLevel)
        ->count();

    // top diagnoses for this course/year (after the insert we just did)
    $topDiag = DB::table('tbl_diagnosis_reports as dr')
        ->join('tbl_users as u', 'u.id', '=', 'dr.student_id')
        ->where('u.course', $course)
        ->where('u.year_level', $yearLevel)
        ->selectRaw('dr.diagnosis_result as label, COUNT(*) as c')
        ->groupBy('dr.diagnosis_result')
        ->orderByDesc('c')
        ->limit(8)
        ->pluck('label')
        ->map(fn($v) => (string) $v)
        ->all();

    $jsonList = json_encode(array_values($topDiag), JSON_UNESCAPED_UNICODE);

    // upsert row in tbl_course_analytics (unique: course+year_level)
    DB::table('tbl_course_analytics')->updateOrInsert(
        ['course' => $course, 'year_level' => $yearLevel],
        [
            'total_students'   => $totalStudents,
            'common_diagnosis' => $jsonList,   // model accessor parses JSON/CSV
            'generated_at'     => now(),
            'updated_at'       => now(),
            'created_at'       => now(),       // affects insert only
        ]
    );
}
}
