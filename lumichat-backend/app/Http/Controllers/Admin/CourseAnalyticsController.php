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

    /** INDEX: list rows with year + search filters */
    public function index(Request $request): View
    {
        $yearKey = (string) $request->query('year', 'all');
        $q       = trim((string) $request->query('q', ''));

        $courses = $this->analytics->listCourses($yearKey, $q);

        return view('admin.course-analytics.index', [
            'courses' => $courses,
            'yearKey' => $yearKey,
            'q'       => $q,
        ]);
    }

    /** SHOW: one course/year with diagnosis breakdown */
    public function show(int $id): View
    {
        $course = $this->analytics->findCourseWithBreakdown($id);
        abort_unless($course, 404);

        $title = "{$course->course} • {$course->year_level}";

        // Pass id explicitly so Blade can build the export link
        return view('admin.course-analytics.show', [
            'course'   => $course,
            'title'    => $title,
            'courseId' => $id,
        ]);
    }

    /** Export the INDEX list to PDF (matches route: admin.course-analytics.export.pdf) */
    public function exportIndexPdf(Request $request)
    {
        $yearKey = (string) $request->query('year', 'all');
        $q       = trim((string) $request->query('q', ''));

        $courses = $this->analytics->listCourses($yearKey, $q);

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        // Ensure you have: resources/views/admin/course-analytics/index-pdf.blade.php
        $pdf->loadView('admin.course-analytics.index-pdf', [
            'courses'     => $courses,
            'yearKey'     => $yearKey,
            'q'           => $q,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ]);

        return $pdf->download('Course_Analytics_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Alias so the existing route pointing at exportPdf still works.
     * Route can call this or exportIndexPdf directly.
     */
    public function exportPdf(Request $request)
    {
        return $this->exportIndexPdf($request);
    }


}
