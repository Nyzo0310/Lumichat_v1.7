<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CourseAnalyticsController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request): View
    {
        $yearKey = (string) $request->input('year', 'all');
        $q       = trim((string) $request->input('q', ''));

        $query = CourseAnalytics::query()
            ->select(['id', 'course', 'year_level', 'total_students', 'common_diagnosis'])
            ->when($yearKey !== 'all', fn ($q1) => $q1->where('year_level', $this->normalizeYearLabel($yearKey)))
            ->when($q !== '', function ($q1) use ($q) {
                $like = "%{$q}%";
                $q1->where(function ($sub) use ($like) {
                    $sub->where('course', 'like', $like)
                        ->orWhere('year_level', 'like', $like)
                        ->orWhere('common_diagnosis', 'like', $like);
                });
            })
            ->orderBy('course')->orderBy('year_level');

        $courses = $query->paginate(self::PER_PAGE)->withQueryString();

        // Eloquent accessors make these usable in the blade:
        //  - $c->student_count (alias to total_students)
        //  - $c->common_diagnoses (array)

        return view('admin.course-analytics.index', [
            'courses' => $courses,
            'yearKey' => $yearKey,
            'q'       => $q,
        ]);
    }

    /** Show detail page + live breakdown built from diagnosis reports */
    public function show(CourseAnalytics $course): View
    {
        // Build diagnosis breakdown from tbl_diagnosis_reports joined with tbl_users
        $breakdown = DB::table('tbl_diagnosis_reports as dr')
            ->join('tbl_users as u', 'u.id', '=', 'dr.student_id')
            ->where('u.course', $course->course)
            ->where('u.year_level', $course->year_level)
            ->selectRaw('dr.diagnosis_result AS label, COUNT(*) AS cnt')
            ->groupBy('dr.diagnosis_result')
            ->orderByDesc('cnt')
            ->limit(12)
            ->get()
            ->map(fn($r) => ['label' => (string) $r->label, 'count' => (int) $r->cnt])
            ->toArray();

        // Build a simple DTO the blade expects
        $dto = (object) [
            'id'             => $course->id,
            'course'         => $course->course,
            'year_level'     => $course->year_level,
            'student_count'  => $course->student_count,
            'breakdown'      => $breakdown,
            'notes'          => null, // optional; blade handles null
        ];

        return view('admin.course-analytics.show', ['course' => $dto]);
    }

    /** Accepts '1'|'2'|'3'|'4' or full labels; returns '1st year' etc. */
    private function normalizeYearLabel(string $val): string
    {
        $map = [
            '1' => '1st year', '1st' => '1st year', '1st year' => '1st year',
            '2' => '2nd year', '2nd' => '2nd year', '2nd year' => '2nd year',
            '3' => '3rd year', '3rd' => '3rd year', '3rd year' => '3rd year',
            '4' => '4th year', '4th' => '4th year', '4th year' => '4th year',
        ];
        $k = strtolower(trim($val));
        return $map[$k] ?? $val;
    }
}
