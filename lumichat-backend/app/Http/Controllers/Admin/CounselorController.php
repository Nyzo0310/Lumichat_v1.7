<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Counselor;
use App\Models\CounselorAvailability;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CounselorController extends Controller
{
    public function index()
    {
        $counselors = Counselor::with(['availabilities' => function($q){
            $q->orderBy('weekday')->orderBy('start_time');
        }])->latest()->paginate(10);

        return view('admin.counselors.index', compact('counselors'));
    }

    public function create()
    {
        return view('admin.counselors.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:tbl_counselors,email'],
            'phone' => ['nullable','string','max:30'],
            'is_active' => ['required','boolean'],
            // availability[]: [{weekday, start_time, end_time}]
            'availability' => ['array'],
            'availability.*.weekday' => ['required','integer','between:0,6'],
            'availability.*.start_time' => ['required','date_format:H:i'],
            'availability.*.end_time'   => ['required','date_format:H:i','after:availability.*.start_time'],
        ]);

        $c = Counselor::create([
            'name'=>$data['name'],
            'email'=>$data['email'],
            'phone'=>$data['phone'] ?? null,
            'is_active'=>$data['is_active'],
        ]);

        foreach (($data['availability'] ?? []) as $slot) {
            $c->availabilities()->create($slot);
        }

        return redirect()->route('admin.counselors.index')->with('success','Counselor added.');
    }

    public function edit(Counselor $counselor)
    {
        $counselor->load('availabilities');
        return view('admin.counselors.edit', compact('counselor'));
    }

    public function update(Request $request, Counselor $counselor)
    {
        $data = $request->validate([
            'name'  => ['required','string','max:255'],
            'email' => ['required','email','max:255', Rule::unique('tbl_counselors','email')->ignore($counselor->id)],
            'phone' => ['nullable','string','max:30'],
            'is_active' => ['required','boolean'],
            'availability' => ['array'],
            'availability.*.weekday' => ['required','integer','between:0,6'],
            'availability.*.start_time' => ['required','date_format:H:i'],
            'availability.*.end_time'   => ['required','date_format:H:i','after:availability.*.start_time'],
        ]);

        $counselor->update([
            'name'=>$data['name'],
            'email'=>$data['email'],
            'phone'=>$data['phone'] ?? null,
            'is_active'=>$data['is_active'],
        ]);

        // replace all slots (simple)
        $counselor->availabilities()->delete();
        foreach (($data['availability'] ?? []) as $slot) {
            $counselor->availabilities()->create($slot);
        }

        return redirect()->route('admin.counselors.index')->with('success', 'Counselor added successfully!');
    }

    public function destroy(Counselor $counselor)
    {
        $counselor->delete();
        return back()->with('success','Counselor removed.');
    }
}
