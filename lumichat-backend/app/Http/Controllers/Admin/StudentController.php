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
    // 1) Which year does the user want?
    $year = (int) ($request->query('year') ?: now()->year);

    // 2) Find the earliest relevant year (from data OR student created_at)
    $firstYearFromData = DB::table('tbl_appointments')
        ->where('student_id', $student->id)      // adjust FK if different
        ->selectRaw('MIN(YEAR(scheduled_at)) as y')
        ->value('y');

    $minYear = (int) ($firstYearFromData ?: $student->created_at->year ?: now()->year);
    $maxYear = (int) now()->year;

    // Optional: show at least the last 5 years even if there’s not much data
    $floor = min($minYear, $maxYear - 4);

    // 3) Years for the dropdown (DESC so newest first)
    $yearsAvailable = range($maxYear, $floor);

    // Clamp requested year into the allowed range
    if ($year < $floor) $year = $floor;
    if ($year > $maxYear) $year = $maxYear;

    // 4) Build Jan–Dec series for the selected year
    $rows = DB::table('tbl_appointments')
        ->where('student_id', $student->id)
        ->whereYear('scheduled_at', $year)
        ->selectRaw('MONTH(scheduled_at) as m, COUNT(*) as c')
        ->groupBy('m')
        ->get()
        ->keyBy('m');

    $labels = [];
    $series = [];
    for ($m = 1; $m <= 12; $m++) {
        $labels[] = Carbon::createFromDate($year, $m, 1)->format('M');
        $series[] = (int) optional($rows->get($m))->c ?? 0;
    }

    $total = array_sum($series);
    $max   = $total ? max($series) : 0;
    $peakLabel = $max ? $labels[array_search($max, $series, true)] : null;

    return view('admin.students.show', compact(
        'student', 'year', 'yearsAvailable', 'labels', 'series', 'total', 'peakLabel'
    ));
}
}

