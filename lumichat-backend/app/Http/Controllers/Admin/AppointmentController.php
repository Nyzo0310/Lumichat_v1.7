<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class AppointmentController extends Controller
{
    // ==== Flash keys ====
    private const FLASH_SWAL = 'swal';

    // ==== Filters ====
    private const STATUS_ALL = 'all';
    private const PERIOD_ALL = 'all';
    private const STATUSES   = ['pending','confirmed','canceled','completed'];
    private const PERIODS    = ['all','upcoming','today','this_week','this_month','past'];

    public function __construct(
        protected AppointmentRepositoryInterface $appointments
    ) {}  

    /** List appointments with optional filters + search (counselor name). */
    public function index(Request $r): View
    {
        $status = \in_array($r->query('status'), self::STATUSES, true) ? $r->query('status') : self::STATUS_ALL;
        $period = \in_array($r->query('period'), self::PERIODS, true)   ? $r->query('period') : self::PERIOD_ALL;
        $q      = \trim((string) $r->query('q', ''));

        $appointments = $this->appointments->paginateWithNames([
            'status' => $status,
            'period' => $period,
            'q'      => $q,
        ], 10);

        return view('admin.appointments.index', compact('appointments', 'status', 'period', 'q'));
    }

    /** Show appointment details + latest report for that pair. */
    public function show(int $id): View
    {
        $row = $this->appointments->findDetailedById($id);
        abort_unless($row, 404);

        // latest report for student+counselor
        $latestReport = \DB::table('tbl_diagnosis_reports')
            ->where('student_id', $row->student_id)
            ->where('counselor_id', $row->counselor_id)
            ->orderByDesc('id')
            ->first();

        return view('admin.appointments.show', [
            'appointment'  => $row,
            'latestReport' => $latestReport,
        ]);
    }

    /** Persist final report for a completed appointment. */
    public function saveReport(Request $r, int $id): RedirectResponse
    {
        $data = $r->validate([
            'diagnosis'  => ['required','string','max:4000'],
            'final_note' => ['nullable','string','max:4000'],
        ]);

        $res = $this->appointments->saveFinalReport(
            appointmentId: $id,
            diagnosis:     $data['diagnosis'],
            finalNote:     $data['final_note'] ?? null,
            finalizedBy:   auth()->id()
        );

        if (!$res['ok']) {
            $map = [
                'not_found'      => ['warning','Not found','Appointment not found.'],
                'not_completed'  => ['warning','Not allowed','You can save the diagnosis only for completed appointments.'],
            ];
            [$icon,$title,$text] = $map[$res['reason']] ?? ['error','Error','Unable to save report.'];
            return back()->with(self::FLASH_SWAL, compact('icon','title','text'));
        }

        return back()->with(self::FLASH_SWAL, [
            'icon'  => 'success',
            'title' => 'Saved',
            'text'  => 'Diagnosis report has been saved.',
        ]);
    }

    /** Update status via action ('confirm' | 'done') with rule checks. */
    public function updateStatus(Request $r, int $id): RedirectResponse
    {
        $action = $r->input('action'); // 'confirm' | 'done'
        $res = $this->appointments->updateStatusByAction($id, $action);

        if (!$res['ok']) {
            $map = [
                'invalid_action'   => ['warning','Not allowed','Invalid action.'],
                'not_found'        => ['warning','Not allowed','Appointment not found.'],
                'must_be_confirmed'=> ['warning','Not allowed','Appointment must be confirmed before you can mark it as done.'],
                'too_early'        => ['warning','Too early','You can only mark the appointment as done once it has started.'],
            ];
            [$icon,$title,$text] = $map[$res['reason']] ?? ['error','Error','Unable to update status.'];
            return back()->with(self::FLASH_SWAL, compact('icon','title','text'));
        }

        return back()->with(self::FLASH_SWAL, [
            'icon'  => 'success',
            'title' => 'Updated',
            'text'  => 'Appointment status has been updated.',
        ]);
    }
    public function exportPdf(Request $request)
    {
        $status = (string) $request->query('status', 'all');
        $period = (string) $request->query('period', 'all');
        $q      = trim((string) $request->query('q', ''));

        $now = now();

        $query = DB::table('tbl_appointments as a')
            ->leftJoin('tbl_users as s', 's.id', '=', 'a.student_id')
            ->leftJoin('tbl_counselors as c', 'c.id', '=', 'a.counselor_id')
            ->select([
                'a.id',
                'a.scheduled_at',
                'a.created_at as booked_at',
                'a.status',
                DB::raw("COALESCE(s.name,'—') as student_name"),
                DB::raw("COALESCE(c.name,'—') as counselor_name"),
            ]);

        if ($status !== 'all') {
            $query->where('a.status', $status);
        }

        switch ($period) {
            case 'today':
                $query->whereDate('a.scheduled_at', $now->toDateString());
                break;
            case 'upcoming':
                $query->where('a.scheduled_at', '>=', $now);
                break;
            case 'this_week':
                $query->whereBetween('a.scheduled_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
                break;
            case 'this_month':
                $query->whereBetween('a.scheduled_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
                break;
            case 'past':
                $query->where('a.scheduled_at', '<', $now);
                break;
            case 'all':
            default:
                // no date filter
                break;
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('s.name', 'like', "%{$q}%")
                ->orWhere('c.name', 'like', "%{$q}%");
            });
        }

        $appointments = $query->orderByDesc('a.scheduled_at')->get();

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('admin.appointments.pdf', [
            'appointments' => $appointments,
            'status'       => $status,
            'period'       => $period,
            'q'            => $q,
            'generatedAt'  => now()->format('Y-m-d H:i'),
        ]);

        return $pdf->download('Appointments_'.now()->format('Ymd_His').'.pdf');
    }
    public function exportShowPdf(int $id)
    {
        $appointment = $this->appointments->findDetailedById($id);
        abort_unless($appointment, 404);

        // If you also want the latest report block (optional)
        $latestReport = \DB::table('tbl_diagnosis_reports')
            ->where('student_id', $appointment->student_id)
            ->where('counselor_id', $appointment->counselor_id)
            ->orderByDesc('id')
            ->first();

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
        ]);

        $pdf->loadView('admin.appointments.pdf-show', [
            'appointment'  => $appointment,
            'latestReport' => $latestReport, // remove if you don't want the report section
        ]);

        return $pdf->download('Appointment_'.$appointment->id.'.pdf');
    }

    public function assignForm(int $id)
    {
        $appointment = $this->appointments->findDetailedById($id);
        abort_unless($appointment, 404);

        if ($appointment->status !== 'pending') {
            return redirect()
                ->route('admin.appointments.show', $appointment->id)
                ->with(self::FLASH_SWAL, [
                    'icon'  => 'warning',
                    'title' => 'Not allowed',
                    'text'  => 'You can only assign a counselor to pending appointments.',
                ]);
        }

        $slotStart = \Carbon\Carbon::parse($appointment->scheduled_at);
        $slotEnd   = $slotStart->copy()->addMinutes(30); // matches your slot length
        $dow       = $slotStart->isoWeekday();           // 1..7

        $counselors = \DB::table('tbl_counselors')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id','name','email']);

        foreach ($counselors as $c) {
            // fits counselor’s weekly schedule?
            $fits = \DB::table('tbl_counselor_availabilities')
                ->where('counselor_id', $c->id)
                ->where('weekday', $dow)
                ->where('start_time', '<=', $slotStart->format('H:i:s'))
                ->where('end_time',   '>=', $slotEnd->format('H:i:s'))
                ->exists();

            // already booked at that exact time?
            $booked = \DB::table('tbl_appointments')
                ->where('counselor_id', $c->id)
                ->where('scheduled_at', $appointment->scheduled_at)
                ->whereIn('status', ['pending','confirmed','completed'])
                ->exists();

            $c->available = ($fits && !$booked) ? 1 : 0;
        }

        return view('admin.appointments.assign', compact('appointment', 'counselors'));
    }

    public function assign(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'counselor_id' => ['required', 'exists:tbl_counselors,id'],
        ]);

        $ap = \DB::table('tbl_appointments')->where('id', $id)->first();
        abort_unless($ap, 404);

        if ($ap->status !== 'pending') {
            return back()->with(self::FLASH_SWAL, [
                'icon'  => 'warning',
                'title' => 'Not allowed',
                'text'  => 'Only pending appointments can be assigned.',
            ]);
        }

        $res = $this->appointments->assignCounselor($id, (int)$data['counselor_id']);
        if (!$res['ok']) {
            $map = [
                'not_found'     => ['warning','Not found','Appointment not found.'],
                'in_past'       => ['warning','Not allowed','Cannot assign in the past.'],
                'not_available' => ['error','Counselor busy','Selected counselor is no longer free.'],
                'race_taken'    => ['error','Just taken','That slot was taken moments ago.'],
            ];
            [$icon,$title,$text] = $map[$res['reason']] ?? ['error','Error','Unable to assign counselor.'];
            return back()->with(self::FLASH_SWAL, compact('icon','title','text'));
        }

        // Auto-confirm after successful assign (optional business rule)
        $this->appointments->updateStatusByAction($id, 'confirm');

        return redirect()->route('admin.appointments.index')->with(self::FLASH_SWAL, [
            'icon'  => 'success',
            'title' => 'Counselor assigned',
            'text'  => 'Appointment has been confirmed.',
        ]);
    }
}
