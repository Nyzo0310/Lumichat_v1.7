@extends('layouts.admin')
@section('title','Diagnosis Reports')

@section('content')
@php
  $dateKey = request('date','all');
  $q       = request('q','');
@endphp

<div class="max-w-7xl mx-auto space-y-6">

  {{-- Page header (screen only) --}}
  <div class="mb-4 flex items-center justify-between screen-only">
    <div>
      <h2 class="text-2xl font-semibold tracking-tight text-slate-800">Diagnosis Reports</h2>
      <p class="text-sm text-slate-500">Review summary reports based on student responses and emotional patterns.</p>
    </div>
    <button type="button"
            onclick="printReports()"
            class="px-3 py-1.5 rounded-lg bg-green-600 text-white text-sm hover:bg-green-700">
      Print
    </button>
  </div>

  {{-- Filters + Search (screen only) --}}
  <div class="flex items-center justify-between gap-3 mb-6 screen-only">
    {{-- Date filter – preserves current search --}}
    <form method="GET" action="{{ route('admin.diagnosis-reports.index') }}" class="flex items-center gap-2">
      <input type="hidden" name="q" value="{{ $q }}">
      <select name="date"
              class="h-9 bg-white border border-slate-200 rounded-lg px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500"
              onchange="this.form.submit()">
        <option value="all"   @selected($dateKey==='all')>All Dates</option>
        <option value="7d"    @selected($dateKey==='7d')>Last 7 days</option>
        <option value="30d"   @selected($dateKey==='30d')>Last 30 days</option>
        <option value="month" @selected($dateKey==='month')>This month</option>
      </select>
    </form>

    {{-- Search – preserves current date --}}
    <form method="GET" action="{{ route('admin.diagnosis-reports.index') }}" class="relative w-full max-w-xs">
      <input type="hidden" name="date" value="{{ $dateKey }}">
      <input id="qInput" name="q" value="{{ $q }}" placeholder="Search student, counselor, result, or report ID…"
             class="w-full h-9 rounded-lg border border-slate-200 bg-white pl-10 pr-3 text-sm
                    placeholder:text-slate-400 shadow-sm
                    focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
      <img src="{{ asset('images/icons/search.png') }}" alt="Search"
           class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-70 pointer-events-none">
    </form>
  </div>

  {{-- ===== PRINT SCOPE ===== --}}
  <div id="print-root" class="space-y-3">

    {{-- Print-only title --}}
    <h1 class="print-title hidden">Diagnosis Reports</h1>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-[900px] w-full text-sm text-left">
          <thead class="bg-slate-200 text-slate-800 shadow-sm">
            <tr class="text-[12px] uppercase tracking-wide">
              <th class="px-6 py-3 font-semibold whitespace-nowrap">ID</th>
              <th class="px-6 py-3 font-semibold whitespace-nowrap">Student Name</th>
              <th class="px-6 py-3 font-semibold whitespace-nowrap">Counselor Name</th>
              <th class="px-6 py-3 font-semibold whitespace-nowrap">Diagnosis Result</th>
              <th class="px-6 py-3 font-semibold whitespace-nowrap">Date</th>
              <th class="px-6 py-3 text-right font-semibold whitespace-nowrap screen-only">Action</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100 text-slate-800">
            @forelse ($reports as $r)
              @php
                $code         = 'DRP-' . now()->format('Y') . '-' . str_pad($r->id, 4, '0', STR_PAD_LEFT);
                $studentName  = $r->student->name ?? '—';
                $counselorName= $r->counselor->name ?? ('Counselor #' . ($r->counselor_id ?? '—'));
                $date         = $r->created_at?->format('F d, Y') ?? '—';
              @endphp
              <tr class="hover:bg-slate-50 transition">
                <td class="px-6 py-4 font-medium text-slate-900 whitespace-nowrap">{{ $code }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $studentName }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $counselorName }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $r->diagnosis_result }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $date }}</td>
                <td class="px-6 py-4 text-right screen-only">
                  <a href="{{ route('admin.diagnosis-reports.show', $r->id) }}"
                     class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700">
                    View
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-6 pt-14 pb-10 text-center">
                  <div class="mx-auto w-full max-w-sm">
                    <div class="mx-auto w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center">
                      <img src="{{ asset('images/icons/nodata.png') }}" alt="" class="w-6 h-6 opacity-60">
                    </div>
                    <p class="mt-3 text-sm font-medium text-slate-700">No diagnosis reports found</p>
                    <p class="text-xs text-slate-500 mb-6">Reports will appear here once counselors finalize a diagnosis.</p>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination (bottom, safe) --}}
      @php
        $isPaginator = $reports instanceof \Illuminate\Contracts\Pagination\Paginator
                    || $reports instanceof \Illuminate\Pagination\LengthAwarePaginator;
      @endphp
      @if($isPaginator && $reports->hasPages())
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/70 screen-only">
          {{ $reports->onEachSide(1)->links() }}
        </div>
      @endif
    </div>
  </div>
</div>

{{-- Print styles + JS --}}
<style media="print">
  @page { margin: 12mm; }

  /* Only show the print scope */
  body * { visibility: hidden !important; }
  #print-root, #print-root * { visibility: visible !important; }

  /* Make the print scope take the full page */
  #print-root {
    position: fixed !important;
    inset: 0 !important;
    padding: 12mm !important;
    background: #fff !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    width: auto !important;
    max-width: none !important;
  }

  /* Remove card chrome */
  #print-root .rounded-2xl,
  #print-root .shadow-sm,
  #print-root .border { border: 0 !important; box-shadow: none !important; }

  /* Kill horizontal scrollers and width clamps */
  #print-root .overflow-x-auto { overflow: visible !important; }
  #print-root table {
    width: 100% !important;
    min-width: 0 !important;          /* override min-w-[900px] */
    table-layout: fixed !important;    /* keep columns inside page */
    border-collapse: collapse !important;
  }

  #print-root th,
#print-root td {
  font-size: 9.5pt !important;      /* smaller text so it fits */
  line-height: 1.15 !important;      /* tighter line height */
  padding: 4px 6px !important;       /* tighter padding */
  white-space: nowrap !important;    /* keep everything on one line */
  overflow: hidden !important;       /* hide overflow instead of wrapping */
  text-overflow: ellipsis !important;/* show … when too long */
}

/* keep the table compact and within page */
#print-root table {
  table-layout: fixed !important;    /* column widths are respected */
  width: 100% !important;
  min-width: 0 !important;
}

/* optional: slightly smaller header text for more room */
#print-root thead th { font-weight: 600 !important; font-size: 9pt !important; }

/* optional: slightly smaller title so more vertical space goes to the table */
#print-root .print-title { font-size: 16pt !important; }
 
  /* Hide UI-only bits */
  .screen-only { display: none !important; }

  /* Print title */
  .print-title {
    display: block !important;
    margin: 0 0 8mm !important;
    font-size: 20pt !important;
    font-weight: 700 !important;
    color: #000 !important;
  }
</style>
<script>
  function printReports(){ window.print(); }
</script>

@endsection
