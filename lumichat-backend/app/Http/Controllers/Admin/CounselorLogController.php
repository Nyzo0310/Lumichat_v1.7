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
}
