<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiagnosisReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosisReportController extends Controller
{
    private const PER_PAGE = 10;

public function index(Request $request): View
{
    $dateKey = $request->input('date', 'all');
    $q       = trim((string) $request->input('q', ''));

    $reports = DiagnosisReport::query()
        ->with([
            'student:id,name,email',
            'counselor',              // <- no column list (avoids unknown columns)
        ])
        ->when($dateKey !== 'all', function ($q1) use ($dateKey) {
            return match ($dateKey) {
                '7d'    => $q1->where('created_at', '>=', now()->subDays(7)),
                '30d'   => $q1->where('created_at', '>=', now()->subDays(30)),
                'month' => $q1->whereYear('created_at', now()->year)
                              ->whereMonth('created_at', now()->month),
                default => $q1,
            };
        })
        ->when($q !== '', function ($q1) use ($q) {
            $like = "%{$q}%";
            $q1->where(function ($sub) use ($q, $like) {
                if (ctype_digit($q)) {
                    $sub->orWhere('id', (int) $q);
                }
                $sub->orWhere('diagnosis_result', 'like', $like)
                    ->orWhereHas('student', fn ($qq) =>
                        $qq->where('name', 'like', $like)->orWhere('email', 'like', $like)
                    )
                    // Only search 'name' on counselors (no 'full_name' in your DB)
                    ->orWhereHas('counselor', fn ($qq) =>
                        $qq->where('name', 'like', $like)
                    );
            });
        })
        ->orderByDesc('created_at')
        ->paginate(10)
        ->withQueryString();

    return view('admin.diagnosis-reports.index', compact('reports', 'dateKey', 'q'));
}

public function show(DiagnosisReport $report): View
{
    $report->load(['student:id,name,email', 'counselor']); // <- no column list
    return view('admin.diagnosis-reports.show', compact('report'));
}
}