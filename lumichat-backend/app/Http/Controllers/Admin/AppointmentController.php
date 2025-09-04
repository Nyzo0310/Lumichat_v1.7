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
        // Read filters with safe defaults
        $status = in_array($r->query('status'), ['pending','confirmed','canceled','completed'])
            ? $r->query('status')
            : 'all';

        $period = in_array($r->query('period'), ['all','upcoming','today','this_week','this_month','past'])
            ? $r->query('period')
            : 'all';

        $q = trim((string) $r->query('q', ''));

        $now = Carbon::now();

        $appointments = DB::table('tbl_appointments as a')
            ->join('tbl_counselors as c', 'c.id', '=', 'a.counselor_id')
            ->join('tbl_users as u', 'u.id', '=', 'a.student_id')
            ->select([
                'a.id',
                'a.scheduled_at',
                'a.status',
                'c.name as counselor_name',
                'u.name as student_name',
            ])
            ->when($status !== 'all', fn($qB) => $qB->where('a.status', $status))
            ->when($period !== 'all', function ($qB) use ($period, $now) {
                if ($period === 'upcoming') {
                    $qB->where('a.scheduled_at', '>=', $now);
                } elseif ($period === 'today') {
                    $qB->whereDate('a.scheduled_at', $now->toDateString());
                } elseif ($period === 'this_week') {
                    $qB->whereBetween('a.scheduled_at', [
                        $now->copy()->startOfWeek(), $now->copy()->endOfWeek()
                    ]);
                } elseif ($period === 'this_month') {
                    $qB->whereBetween('a.scheduled_at', [
                        $now->copy()->startOfMonth(), $now->copy()->endOfMonth()
                    ]);
                } elseif ($period === 'past') {
                    $qB->where('a.scheduled_at', '<', $now);
                }
            })
            ->when($q !== '', fn($qB) => $qB->where('c.name', 'like', '%'.$q.'%'))
            ->orderBy('a.scheduled_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.appointments.index', [
            'appointments' => $appointments,
            'status'       => $status,
            'period'       => $period,
            'q'            => $q,
        ]);
    }

    public function saveNote(Request $r, int $id)
    {
        $r->validate([
            'final_note' => 'nullable|string|max:20000',
        ]);

        $affected = DB::table('tbl_appointments')->where('id', $id)->update([
            'final_note'   => $r->input('final_note'),
            'finalized_by' => auth()->id(),
            'finalized_at' => now(),
            'updated_at'   => now(),
        ]);

        return back()->with('swal', [
            'icon'  => 'success',
            'title' => 'Saved',
            'text'  => 'Final note has been saved.',
        ]);
    }


    public function show(int $id)
    {
        $row = DB::table('tbl_appointments as a')
            ->join('tbl_counselors as c', 'c.id', '=', 'a.counselor_id')
            ->join('tbl_users as u', 'u.id', '=', 'a.student_id')
            ->select([
                'a.*',
                'c.name  as counselor_name',
                'c.email as counselor_email',
                'c.phone as counselor_phone',
                'u.name  as student_name',
                'u.email as student_email',
            ])
            ->where('a.id', $id)
            ->first();

        abort_unless($row, 404);

        return view('admin.appointments.show', ['appointment' => $row]);
    }

    public function updateStatus(Request $r, int $id)
    {
        // Admin may only confirm or mark done (no cancel)
        $r->validate([
            'action' => 'required|in:confirm,done',
        ]);

        $map = [
            'confirm' => 'confirmed',
            'done'    => 'completed',
        ];
        $newStatus = $map[$r->input('action')];

        // simple “cannot mark done unless confirmed” guard
        if ($newStatus === 'completed') {
            $current = DB::table('tbl_appointments')->where('id', $id)->value('status');
            if ($current !== 'confirmed') {
                return back()->with('swal', [
                    'icon'  => 'warning',
                    'title' => 'Not allowed',
                    'text'  => 'Appointment must be confirmed before you can mark it as done.',
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
