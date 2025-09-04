<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SelfAssessment;
use Illuminate\Http\Request;

class SelfAssessmentController extends Controller
{
    public function index(Request $request)
    {
        $q    = $request->input('q', '');
        $risk = $request->input('risk', '');

        $query = SelfAssessment::with(['student' => function ($s) {
            $s->select('id','first_name','last_name','email');
        }]);

        if ($q !== '') {
            $query->whereHas('student', function ($s) use ($q) {
                $s->where('first_name','like',"%{$q}%")
                  ->orWhere('last_name','like',"%{$q}%")
                  ->orWhere('email','like',"%{$q}%");
            });
        }

        if ($risk === 'red') {
            $query->where('red_flag', 1);
        } elseif ($risk === 'safe') {
            $query->where('red_flag', 0);
        }

        $items = $query->orderByDesc('created_at')
                       ->paginate(15)
                       ->withQueryString();

        return view('admin.self-assessments.index', [
            'items' => $items,
            'q'     => $q,
            'risk'  => $risk,
        ]);
    }

    public function show(SelfAssessment $assessment)
    {
        $assessment->load('student');
        return view('admin.self-assessments.show', compact('assessment'));
    }

    public function feedback(Request $request, SelfAssessment $assessment)
    {
        $data = $request->validate([
            'counselor_feedback' => 'nullable|string|max:2000',
        ]);

        $assessment->update([
            'counselor_feedback' => $data['counselor_feedback'] ?? null,
        ]);

        return back()->with('success', 'Feedback saved.');
    }
}
