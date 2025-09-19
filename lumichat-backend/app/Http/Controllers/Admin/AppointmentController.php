<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
            'diagnosis'  => ['required','string','max:20000'],
            'final_note' => ['nullable','string','max:20000'],
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
}
