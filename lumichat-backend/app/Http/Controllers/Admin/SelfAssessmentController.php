<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SelfAssessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SelfAssessmentController extends Controller
{
    // ==== Constants ====
    private const PER_PAGE      = 15;
    private const FLASH_SUCCESS = 'success';
    private const VIEW_INDEX    = 'admin.self-assessments.index';
    private const VIEW_SHOW     = 'admin.self-assessments.show';

    /**
     * List self-assessments with optional search and risk filter.
     */
    public function index(Request $request): View
    {
        $q    = \trim((string) $request->input('q', ''));
        $risk = (string) $request->input('risk', '');

        $items = SelfAssessment::query()
            ->with([
                'student' => function ($s) {
                    $s->select('id', 'first_name', 'last_name', 'email');
                },
            ])
            ->when($q !== '', function ($query) use ($q) {
                $like = "%{$q}%";
                $query->whereHas('student', function ($s) use ($like) {
                    $s->where('first_name', 'like', $like)
                      ->orWhere('last_name', 'like', $like)
                      ->orWhere('email', 'like', $like);
                });
            })
            ->when($risk === 'red',  fn ($query) => $query->where('red_flag', 1))
            ->when($risk === 'safe', fn ($query) => $query->where('red_flag', 0))
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view(self::VIEW_INDEX, [
            'items' => $items,
            'q'     => $q,
            'risk'  => $risk,
        ]);
    }

    /**
     * Show a single self-assessment with its student.
     */
    public function show(SelfAssessment $assessment): View
    {
        $assessment->load('student');

        return view(self::VIEW_SHOW, compact('assessment'));
    }

    /**
     * Save counselor feedback for a self-assessment.
     */
    public function feedback(Request $request, SelfAssessment $assessment): RedirectResponse
    {
        $data = $request->validate([
            'counselor_feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $assessment->update([
            'counselor_feedback' => $data['counselor_feedback'] ?? null,
        ]);

        return back()->with(self::FLASH_SUCCESS, 'Feedback saved.');
    }
}
