<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CourseAnalyticsController extends Controller
{
    /** INDEX: list rows from tbl_course_analytics with year + search filters */
    public function index(Request $request): View
    {
        $yearKey = (string) $request->query('year', 'all');   // "all" | "1" | "2" | "3" | "4"
        $q       = trim((string) $request->query('q', ''));

        $rows = DB::table('tbl_course_analytics')
            ->select('id', 'course', 'year_level', 'total_students', 'common_diagnosis', 'updated_at')
            ->when($this->isYearKey($yearKey), function ($qb) use ($yearKey) {
                // Allow different capitalizations / “Year” vs “year”
                $needle = match ($yearKey) {
                    '1' => '1st', '2' => '2nd', '3' => '3rd', '4' => '4th',
                    default => ''
                };
                $qb->whereRaw('LOWER(year_level) LIKE ?', ['%' . strtolower($needle) . '%']);
            })
            ->when($q !== '', function ($qb) use ($q) {
                $like = '%' . $q . '%';
                $qb->where(function ($sub) use ($like) {
                    $sub->where('course', 'like', $like)
                        ->orWhere('year_level', 'like', $like)
                        ->orWhere('common_diagnosis', 'like', $like);
                });
            })
            ->orderBy('course')
            ->orderBy('year_level')
            ->get();

        // Normalize for the blade: map JSON/CSV -> array, rename fields for display
        $courses = $rows->map(function ($r) {
            return (object) [
                'id'                => $r->id,
                'course'            => $r->course,
                'year_level'        => $r->year_level,
                'student_count'     => (int) $r->total_students,
                'common_diagnoses'  => $this->decodeCommon($r->common_diagnosis),
            ];
        });

        return view('admin.course-analytics.index', [
            'courses' => $courses,
            'yearKey' => $yearKey,
            'q'       => $q,
        ]);
    }

    /** SHOW: view one course/year with live diagnosis breakdown from reports */
    public function show(int $id): View
    {
        $row = DB::table('tbl_course_analytics')
            ->select('id', 'course', 'year_level', 'total_students')
            ->where('id', $id)
            ->first();

        abort_unless($row, 404);

        // Build breakdown from diagnosis reports joined to users of the same course/year
        $breakdown = DB::table('tbl_diagnosis_reports as dr')
            ->join('tbl_users as u', 'u.id', '=', 'dr.student_id')
            ->where('u.course', $row->course)
            ->where('u.year_level', $row->year_level)
            ->selectRaw('dr.diagnosis_result as label, COUNT(*) as cnt')
            ->groupBy('dr.diagnosis_result')
            ->orderByDesc('cnt')
            ->limit(20)
            ->get()
            ->map(fn ($x) => ['label' => (string) $x->label, 'count' => (int) $x->cnt])
            ->all();

        $course = (object) [
            'course'        => $row->course,
            'year_level'    => $row->year_level,
            'student_count' => (int) $row->total_students,
            'breakdown'     => $breakdown,
            'notes'         => null, // (optional) keep for future
        ];

        $title = "{$row->course} • {$row->year_level}";

        return view('admin.course-analytics.show', compact('course', 'title'));
    }

    // ---- helpers ----
    private function isYearKey(string $k): bool
    {
        return in_array($k, ['1', '2', '3', '4'], true);
    }

    private function decodeCommon(?string $raw): array
    {
        if ($raw === null || $raw === '') return [];
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }
        // fallback: CSV
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}