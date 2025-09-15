<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;                  // <<< use User instead of Registration
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentController extends Controller
{
    // ==== Constants ====
    private const PER_PAGE      = 10;
    private const VIEW_INDEX    = 'admin.students.index';
    private const VIEW_SHOW     = 'admin.students.show';
    private const APPT_TABLE    = 'tbl_appointments';
    private const APPT_FK_COL   = 'student_id'; // in tbl_appointments

    /**
     * List students (from tbl_users) with optional text and year filters.
     */
    public function index(Request $request): View
    {
        $q    = trim((string) $request->input('q', ''));
        $year = $request->input('year'); // string/int

        $students = User::query()
            ->where('role', 'student') // only students
            ->when($year !== null && $year !== '', fn ($q1) => $q1->where('year_level', $year))
            ->when($q !== '', function ($q1) use ($q) {
                $like = "%{$q}%";
                $q1->where(function ($sub) use ($like) {
                    $sub->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('contact_number', 'like', $like)
                        ->orWhere('course', 'like', $like)
                        ->orWhere('year_level', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $yearLevels = User::query()
            ->where('role', 'student')
            ->whereNotNull('year_level')
            ->distinct()
            ->orderBy('year_level')
            ->pluck('year_level')
            ->toArray();

        return view(self::VIEW_INDEX, compact('students', 'q', 'year', 'yearLevels'));
    }

    /**
     * Show a student's appointment stats and chart for a selected year.
     * NOTE: We now bind to App\Models\User and query appointments by users.id.
     */
    public function show(Request $request, User $student): View
    {
        $requestedYear = (int) ($request->query('year') ?: now()->year);
        $studentId     = (int) $student->id;

        // Earliest year from appointments for this user
        $firstYearFromData = DB::table(self::APPT_TABLE)
            ->where(self::APPT_FK_COL, $studentId)
            ->whereNotNull('scheduled_at')
            ->selectRaw('MIN(YEAR(scheduled_at)) AS y')
            ->value('y');

        $minYear = (int) ($firstYearFromData ?: ($student->created_at?->year ?? now()->year));
        $maxYear = (int) now()->year;
        $floor   = min($minYear, $maxYear - 4);
        $yearsAvailable = range($maxYear, $floor, -1); // DESC
        $year = max(min($requestedYear, $maxYear), $floor);

        // Monthly counts for the selected year
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = Carbon::create($year, 12, 31)->endOfDay();

        $monthCounts = DB::table(self::APPT_TABLE)
            ->where(self::APPT_FK_COL, $studentId)
            ->whereBetween('scheduled_at', [$start, $end])
            ->whereNotNull('scheduled_at')
            // ->where('status', 'confirmed') // uncomment to count confirmed only
            ->selectRaw('MONTH(scheduled_at) AS m, COUNT(*) AS c')
            ->groupByRaw('MONTH(scheduled_at)')
            ->orderByRaw('m')
            ->pluck('c', 'm')
            ->all();

        [$labels, $series] = $this->buildMonthlySeries($year, $monthCounts);

        $total     = array_sum($series);
        $max       = $total ? max($series) : 0;
        $peakLabel = $max ? $labels[array_search($max, $series, true)] : null;

        return view(self::VIEW_SHOW, compact(
            'student',
            'year',
            'yearsAvailable',
            'labels',
            'series',
            'total',
            'peakLabel'
        ));
    }

    // ==== Private helpers ====

    /**
     * Build month labels (Jan–Dec) and a 12-length series using the plucked counts.
     *
     * @param  int $year
     * @param  \Illuminate\Support\Collection|array $monthCounts  [monthNumber => count]
     * @return array{0: array<int,string>, 1: array<int,int>}
     */
    private function buildMonthlySeries(int $year, $monthCounts): array
    {
        if ($monthCounts instanceof \Illuminate\Support\Collection) {
            $monthCounts = $monthCounts->all();
        }

        $labels = [];
        $series = [];

        for ($m = 1; $m <= 12; $m++) {
            $labels[] = Carbon::create($year, $m, 1)->format('M'); // Jan, Feb, ...
            $series[] = (int) ($monthCounts[$m] ?? 0);
        }

        return [$labels, $series];
    }
}
