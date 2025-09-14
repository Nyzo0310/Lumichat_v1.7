<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $q    = $request->input('q');
        $year = $request->input('year');

        $students = Registration::query()
            ->when($year, fn($q1) => $q1->where('year_level', $year))
            ->when($q, function ($q1) use ($q) {
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
            ->paginate(10)
            ->withQueryString();

        $yearLevels = Registration::query()
            ->select('year_level')->whereNotNull('year_level')
            ->distinct()->orderBy('year_level')->pluck('year_level')->toArray();

        return view('admin.students.index', compact('students', 'q', 'year', 'yearLevels'));
    }

public function show(Request $request, Registration $student)
{
    $year = (int) ($request->query('year') ?: now()->year);

    $fkCol = 'student_id'; // column in tbl_appointments
    // Prefer registrations.id; if no rows and user_id exists, fallback to users.id
    $id = $student->id;
    $hasRegRows = DB::table('tbl_appointments')->where($fkCol, $id)->exists();
    if (!$hasRegRows && !empty($student->user_id)) {
        $id = $student->user_id;
    }

    // earliest year for this student
    $firstYearFromData = DB::table('tbl_appointments')
        ->where($fkCol, $id)
        ->whereNotNull('scheduled_at')
        ->selectRaw('MIN(YEAR(scheduled_at)) AS y')
        ->value('y');

    $minYear = (int) ($firstYearFromData ?: ($student->created_at?->year ?? now()->year));
    $maxYear = (int) now()->year;
    $floor   = min($minYear, $maxYear - 4);
    $yearsAvailable = range($maxYear, $floor); // DESC
    $year = max(min($year, $maxYear), $floor);

    // monthly counts in selected year
    $start = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
    $end   = \Carbon\Carbon::create($year, 12, 31)->endOfDay();

    $monthCounts = DB::table('tbl_appointments')
        ->where($fkCol, $id)
        ->whereBetween('scheduled_at', [$start, $end])
        ->whereNotNull('scheduled_at')
        ->selectRaw('MONTH(scheduled_at) AS m, COUNT(*) AS c')
        ->groupByRaw('MONTH(scheduled_at)')
        ->orderByRaw('m')
        ->pluck('c', 'm'); // [9 => 3, 10 => 1, ...]

    $labels = [];
    $series = [];
    for ($m = 1; $m <= 12; $m++) {
        $labels[] = \Carbon\Carbon::create($year, $m, 1)->format('M');
        $series[] = (int) ($monthCounts[$m] ?? 0);
    }

    $total = array_sum($series);
    $max   = $total ? max($series) : 0;
    $peakLabel = $max ? $labels[array_search($max, $series, true)] : null;

    return view('admin.students.show', compact(
        'student', 'year', 'yearsAvailable', 'labels', 'series', 'total', 'peakLabel'
    ));
}
}