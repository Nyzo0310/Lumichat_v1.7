<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;

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

    // Route model binding: {student} resolves to Registration
    public function show(Registration $student)
    {
        return view('admin.students.show', compact('student'));
    }
}
