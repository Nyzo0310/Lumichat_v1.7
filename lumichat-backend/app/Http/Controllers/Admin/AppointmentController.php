<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function index(Request $r)
{
    $status = in_array($r->query('status'), ['pending','confirmed','canceled','completed'])
        ? $r->query('status') : 'all';

    $period = in_array($r->query('period'), ['all','upcoming','today','this_week','this_month','past'])
        ? $r->query('period') : 'all';

    $q   = trim((string) $r->query('q', ''));
    $now = \Carbon\Carbon::now();

    $appointments = DB::table('tbl_appointments as a')
        ->join('tbl_counselors as c', 'c.id', '=', 'a.counselor_id')
        ->join('tbl_users as u', 'u.id', '=', 'a.student_id')
        ->select([
            'a.id',
            'a.scheduled_at',
            'a.created_at as booked_at',   // <- this gives you “Booked On”
            'a.status',
            'c.name as counselor_name',
            'u.name as student_name',
        ])
        ->when($status !== 'all', fn($qB) => $qB->where('a.status', $status))
        ->when($period !== 'all', function ($qB) use ($period, $now) {
            if ($period === 'upcoming')       $qB->where('a.scheduled_at', '>=', $now);
            elseif ($period === 'today')      $qB->whereDate('a.scheduled_at', $now->toDateString());
            elseif ($period === 'this_week')  $qB->whereBetween('a.scheduled_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
            elseif ($period === 'this_month') $qB->whereBetween('a.scheduled_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
            elseif ($period === 'past')       $qB->where('a.scheduled_at', '<', $now);
        })
        ->when($q !== '', fn($qB) => $qB->where('c.name', 'like', '%'.$q.'%'))
        ->orderBy('a.scheduled_at', 'desc')
        ->paginate(10)
        ->withQueryString();

    return view('admin.appointments.index', compact('appointments','status','period','q'));
}


    public function saveReport(Request $r, int $id)
{
        $data = $r->validate([
        'diagnosis'  => 'required|string|max:20000',
        'final_note' => 'nullable|string|max:20000',
    ]);
 // read the appointment to grab student/counselor ids
    $ap = DB::table('tbl_appointments')->where('id', $id)->first();
    abort_unless($ap, 404);

    // optional: only allow when completed
     if ($ap->status !== 'completed') {
         return back()->with('swal', [
         'icon' => 'warning',
         'title' => 'Not allowed',
            'text' => 'You can save the diagnosis only for completed appointments.',
        ]);
    }
        $affected = DB::table('tbl_appointments')->where('id', $id)->update([
            'final_note'   => $r->input('final_note'),
            'finalized_by' => auth()->id(),
            'finalized_at' => now(),
            'updated_at'   => now(),
        ]);

        DB::table('tbl_diagnosis_reports')->insert([
        'student_id'       => $ap->student_id,
        'counselor_id'     => $ap->counselor_id,
        'diagnosis_result' => $data['diagnosis'],
        'notes'            => $data['final_note'] ?? null,
        'created_at'       => now(),
        'updated_at'       => now(),
    ]);

    return back()->with('swal', [
        'icon'  => 'success',
        'title' => 'Saved',
        'text'  => 'Diagnosis report has been saved.',
    ]);
}


public function show(int $id)
{
    $row = DB::table('tbl_appointments as a')
        ->join('tbl_counselors as c', 'c.id', '=', 'a.counselor_id')
        ->join('tbl_users as u', 'u.id', '=', 'a.student_id')
        ->select([
            'a.*', // includes created_at, student_id, counselor_id, etc.
            'c.name  as counselor_name',
            'c.email as counselor_email',
            'c.phone as counselor_phone',
            'u.name  as student_name',
            'u.email as student_email',
        ])
        ->where('a.id', $id)
        ->first();

    abort_unless($row, 404);

    $latestReport = DB::table('tbl_diagnosis_reports')
        ->where('student_id', $row->student_id)
        ->where('counselor_id', $row->counselor_id)
        ->orderByDesc('id')
        ->first();

    return view('admin.appointments.show', [
        'appointment'  => $row,
        'latestReport' => $latestReport,
    ]);
}

    public function updateStatus(Request $r, int $id)
{
    $r->validate([
        'action' => 'required|in:confirm,done',
    ]);

    $map = ['confirm' => 'confirmed', 'done' => 'completed'];
    $newStatus = $map[$r->input('action')];

    if ($newStatus === 'completed') {
        // fetch both status and scheduled_at
        $row = DB::table('tbl_appointments')
            ->select('status', 'scheduled_at')
            ->where('id', $id)
            ->first();

        if (!$row || $row->status !== 'confirmed') {
            return back()->with('swal', [
                'icon'  => 'warning',
                'title' => 'Not allowed',
                'text'  => 'Appointment must be confirmed before you can mark it as done.',
            ]);
        }

        // prevent completion before the start time
        if (\Carbon\Carbon::parse($row->scheduled_at)->isFuture()) {
            return back()->with('swal', [
                'icon'  => 'warning',
                'title' => 'Too early',
                'text'  => 'You can only mark the appointment as done once it has started.',
            ]);
        }
    }

    DB::table('tbl_appointments')->where('id', $id)->update([
        'status'     => $newStatus,
        'updated_at' => now(),
    ]);

    return back()->with('swal', [
        'icon'  => 'success',
        'title' => 'Updated',
        'text'  => 'Appointment status has been updated.',
    ]);
}
}