<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\DiagnosisReportRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosisReportController extends Controller
{
    private const PER_PAGE = 10;

    public function __construct(
        protected DiagnosisReportRepositoryInterface $reportsRepo
    ) {}

    public function index(Request $request): View
    {
        $dateKey = (string) $request->input('date', 'all');
        $q       = trim((string) $request->input('q', ''));

        $reports = $this->reportsRepo->paginateWithFilters($dateKey, $q, self::PER_PAGE);

        return view('admin.diagnosis-reports.index', compact('reports', 'dateKey', 'q'));
    }

    public function show(int $id): View
    {
        $report = $this->reportsRepo->findWithRelations($id, ['student:id,name,email', 'counselor']);
        abort_unless($report, 404);

        return view('admin.diagnosis-reports.show', compact('report'));
    }
}
