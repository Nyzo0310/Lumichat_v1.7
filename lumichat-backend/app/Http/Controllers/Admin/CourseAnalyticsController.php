<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CourseAnalyticsRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseAnalyticsController extends Controller
{
    public function __construct(
        protected CourseAnalyticsRepositoryInterface $analytics
    ) {}

    /** INDEX: list rows from tbl_course_analytics with year + search filters */
    public function index(Request $request): View
    {
        $yearKey = (string) $request->query('year', 'all'); // "all" | "1" | "2" | "3" | "4"
        $q       = trim((string) $request->query('q', ''));

        $courses = $this->analytics->listCourses($yearKey, $q);

        return view('admin.course-analytics.index', [
            'courses' => $courses,
            'yearKey' => $yearKey,
            'q'       => $q,
        ]);
    }

    /** SHOW: view one course/year with live diagnosis breakdown from reports */
    public function show(int $id): View
    {
        $course = $this->analytics->findCourseWithBreakdown($id);
        abort_unless($course, 404);

        $title = "{$course->course} • {$course->year_level}";

        return view('admin.course-analytics.show', compact('course', 'title'));
    }
}
