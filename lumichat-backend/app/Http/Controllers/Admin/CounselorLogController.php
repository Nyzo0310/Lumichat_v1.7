<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CounselorLogRepositoryInterface;
use Illuminate\Http\Request;

class CounselorLogController extends Controller
{
    public function __construct(
        protected CounselorLogRepositoryInterface $logs
    ) {}

    /** List view with filters (by counselor / month / year) */
    public function index(Request $request)
    {
        $month = (int) $request->integer('month') ?: null; // 1..12
        $year  = (int) $request->integer('year')  ?: null; // e.g., 2025
        $cid   = (int) $request->integer('counselor_id') ?: null;

        $counselors = $this->logs->listCounselors();

        $rows = $this->logs->paginateLogs([
            'month'        => $month,
            'year'         => $year,
            'counselor_id' => $cid,
            'per_page'     => 12,
        ]);

        $years = $this->logs->availableYears();

        return view('admin.counselor-logs.index', compact('rows','counselors','years','month','year','cid'));
    }

    /** Drilldown page: one counselor + selected month/year */
    public function show(Request $request, int $counselor)
    {
        $month = (int) $request->integer('month') ?: (int) now()->format('n');
        $year  = (int) $request->integer('year')  ?: (int) now()->year;

        $data = $this->logs->counselorMonthDetail($counselor, $month, $year);
        abort_unless($data['counselor'] ?? null, 404);

        return view('admin.counselor-logs.show', [
            'counselor' => $data['counselor'],
            'month'     => $month,
            'year'      => $year,
            'students'  => $data['students'],
            'dxCounts'  => $data['dxCounts'],
        ]);
    }

    /** Export filtered list to PDF */
    public function exportPdf(Request $request)
    {
        $month = (int) $request->integer('month') ?: null;
        $year  = (int) $request->integer('year')  ?: null;
        $cid   = (int) $request->integer('counselor_id') ?: null;

        if (method_exists($this->logs, 'allLogs')) {
            $rows = $this->logs->allLogs([
                'month'        => $month,
                'year'         => $year,
                'counselor_id' => $cid,
            ]);
        } else {
            $p    = $this->logs->paginateLogs([
                'month'        => $month,
                'year'         => $year,
                'counselor_id' => $cid,
                'per_page'     => PHP_INT_MAX,
            ]);
            $rows = method_exists($p, 'items') ? collect($p->items()) : collect($p);
        }

        $counselors = $this->logs->listCounselors();
        $cName = $cid ? optional($counselors->firstWhere('id',$cid))->full_name : 'All';
        $mName = $month ? \Carbon\Carbon::create(null,$month,1)->format('F') : 'All';
        $yName = $year ?: 'All';

        $generatedAt = now()->format('Y-m-d H:i');

        // Dompdf wrapper + explicit default font (match your PDF blade font)
        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->getOptions()->set('defaultFont', 'DejaVu Sans');
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);
        $pdf->setPaper('a4', 'portrait');

        $pdf->loadView('admin.counselor-logs.pdf', [
            'rows'        => $rows,
            'cName'       => $cName,
            'mName'       => $mName,
            'yName'       => $yName,
            'generatedAt' => $generatedAt,
        ]);

        return $pdf->download('Counselor_Logs_'.now()->format('Ymd_His').'.pdf');
    }
}
