<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
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
    private const APPT_FK_COL   = 'student_id'; // column name in appointments table

    /**
     * List students with optional text and year filters.
     */
    public function index(Request $request): View
    {
        $q    = \trim((string) $request->input('q', ''));
        $year = $request->input('year'); // keep as-is (string/int); used in equals

        $students = Registration::query()
            ->when($year !== null && $year !== '', fn ($q1) => $q1->where('year_level', $year))
            ->when($q !== '', function ($q1) use ($q) {
                $like = "%{$q}%";
                $q1->where(function ($sub) use ($like) {
                    $sub->where('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('contact_number', 'like', $like)
                        ->orWhere('course', 'like', $like)
                        ->orWhere('year_level', 'like', $like);
                });
            })
            ->orderBy('full_name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $yearLevels = Registration::query()
            ->select('year_level')
            ->whereNotNull('year_level')
            ->distinct()
            ->orderBy('year_level')
            ->pluck('year_level')
            ->toArray();

        return view(self::VIEW_INDEX, compact('students', 'q', 'year', 'yearLevels'));
    }

    /**
     * Show a student's appointment stats and chart for a selected year.
     */
public function show(Request $request, Registration $student): View
{
    $requestedYear = (int) ($request->query('year') ?: now()->year);

    // --- Build the acceptable IDs for appointments.student_id ---
    // Some rows might use registrations.id, others users.id.
    $ids = [];
    $ids[] = (int) $student->id;
    if (!empty($student->user_id)) {
        $ids[] = (int) $student->user_id;
    }
    // unique + drop zeros/nulls
    $ids = array_values(array_filter(array_unique($ids)));

    // --- Year window: derive earliest year from any matching rows ---
    $firstYearFromData = DB::table(self::APPT_TABLE)
        ->whereIn(self::APPT_FK_COL, $ids)          // student_id IN (...)
        ->whereNotNull('scheduled_at')
        ->selectRaw('MIN(YEAR(scheduled_at)) AS y')
        ->value('y');

    $minYear = (int) ($firstYearFromData ?: ($student->created_at?->year ?? now()->year));
    $maxYear = (int) now()->year;
    $floor   = min($minYear, $maxYear - 4);
    $yearsAvailable = range($maxYear, $floor, -1);  // DESC
    $year = max(min($requestedYear, $maxYear), $floor);

    // --- Monthly counts for the selected year across BOTH IDs ---
    $start = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
    $end   = \Carbon\Carbon::create($year, 12, 31)->endOfDay();

    $monthCounts = DB::table(self::APPT_TABLE)
        ->whereIn(self::APPT_FK_COL, $ids)          // student_id IN (...)
        ->whereBetween('scheduled_at', [$start, $end])
        ->whereNotNull('scheduled_at')
        // ->where('status', 'confirmed') // (optional) count only confirmed
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
    // Normalize to array
    if ($monthCounts instanceof \Illuminate\Support\Collection) {
        $monthCounts = $monthCounts->all();
    }

    $labels = [];
    $series = [];

    for ($m = 1; $m <= 12; $m++) {
        $labels[] = \Carbon\Carbon::create($year, $m, 1)->format('M'); // Jan, Feb, ...
        $series[] = (int) ($monthCounts[$m] ?? 0);
    }

    return [$labels, $series];
}

}