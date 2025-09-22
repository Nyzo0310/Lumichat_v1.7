<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User; // kept for route-model binding type-hint
use App\Repositories\Contracts\StudentRepositoryInterface;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf; // <-- add this
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    // ==== Constants ====
    private const PER_PAGE    = 10;
    private const VIEW_INDEX  = 'admin.students.index';
    private const VIEW_SHOW   = 'admin.students.show';

    public function __construct(
        protected StudentRepositoryInterface $students,
        protected AppointmentRepositoryInterface $appointments
    ) {}

    /**
     * List students (from tbl_users) with optional text and year filters.
     */
    public function index(Request $request): View
    {
        $q    = trim((string) $request->input('q', ''));
        $year = $request->input('year'); // string|int

        $paginated = $this->students->paginateWithFilters([
            'q'    => $q,
            'year' => $year,
        ], self::PER_PAGE);

        $yearLevels = $this->students->distinctYearLevels();

        return view(self::VIEW_INDEX, [
            'students'   => $paginated,
            'q'          => $q,
            'year'       => $year,
            'yearLevels' => $yearLevels,
        ]);
    }

    /**
     * Show a student's appointment stats and chart for a selected year.
     * NOTE: We still type-hint App\Models\User for route-model binding.
     */
    public function show(Request $request, User $student): View
    {
        $requestedYear = (int) ($request->query('year') ?: now()->year);
        $studentId     = (int) $student->id;

        // Earliest year from appointments for this user
        $firstYearFromData = $this->appointments->firstAppointmentYearForStudent($studentId);

        $minYear = (int) ($firstYearFromData ?: ($student->created_at?->year ?? now()->year));
        $maxYear = (int) now()->year;
        $floor   = min($minYear, $maxYear - 4);
        $yearsAvailable = range($maxYear, $floor, -1); // DESC
        $year = max(min($requestedYear, $maxYear), $floor);

        // Monthly counts for the selected year
        $monthCounts = $this->appointments->monthlyCountsForStudent($studentId, $year);

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

    /**
     * Export the filtered Student list to PDF (all matching rows, no pagination).
     */
    public function exportPdf(Request $request)
    {
        $q    = trim((string) $request->input('q', ''));
        $year = $request->input('year');

        // Prefer a non-paginated fetch; fallback if your repo lacks it.
        $students = method_exists($this->students, 'allWithFilters')
            ? $this->students->allWithFilters(['q' => $q, 'year' => $year])
            : $this->students->paginateWithFilters(['q' => $q, 'year' => $year], PHP_INT_MAX);

        $generatedAt = now()->format('Y-m-d H:i');

        $pdf = Pdf::loadView('admin.students.pdf', [
            'students'    => $students,
            'q'           => $q,
            'year'        => $year,
            'generatedAt' => $generatedAt,
        ])->setPaper('a4', 'portrait');

        $filename = 'Student_Records_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($filename);
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
