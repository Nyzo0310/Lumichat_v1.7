@extends('layouts.admin')
@section('title','Diagnosis Report Details')

@section('content')
@php
  $id    = $report->id;
  $code  = 'DRP-' . now()->format('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
  $name  = $report->student->name ?? '—';
  $coun  = $report->counselor->name ?? ('Counselor #' . ($report->counselor_id ?? '—'));
  $date  = $report->created_at?->format('F d, Y · h:i A') ?? '—';
  $res   = $report->diagnosis_result ?? '—';
  $badge = match (strtolower($res)) {
    'mild anxiety'      => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
    'moderate anxiety'  => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
    'severe anxiety'    => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
    'normal'            => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    default             => 'bg-slate-100 text-slate-700',
  };
@endphp

<div class="max-w-5xl mx-auto space-y-6">

  {{-- Header (screen only) --}}
  <div class="flex items-start justify-between gap-4 screen-only">
    <div>
      <h2 class="text-2xl font-semibold tracking-tight text-slate-800">Diagnosis Report</h2>
      <p class="text-sm text-slate-500">Finalized diagnosis summary.</p>
    </div>
    <a href="{{ route('admin.diagnosis-reports.index') }}"
       class="inline-flex items-center h-9 px-3 rounded-lg text-sm font-medium bg-white border border-slate-200 shadow-sm hover:bg-slate-50">
      ← Back to list
    </a>
  </div>

  {{-- ===== PRINT SCOPE ===== --}}
  <div id="print-report-root" class="space-y-6">
    <h1 class="print-title hidden">Diagnosis Report — {{ $code }}</h1>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 p-5">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <div class="text-xs uppercase text-slate-500">Report ID</div>
          <div class="font-semibold text-slate-900">{{ $code }}</div>

          <div class="text-xs uppercase text-slate-500 mt-4">Student Name</div>
          <div class="font-medium text-slate-900">{{ $name }}</div>

          <div class="text-xs uppercase text-slate-500 mt-4">Counselor Name</div>
          <div class="font-medium text-slate-900">{{ $coun }}</div>

          <div class="text-xs uppercase text-slate-500 mt-4">Date</div>
          <div class="font-medium text-slate-900">{{ $date }}</div>
        </div>

        <div class="space-y-2">
          <div class="text-xs uppercase text-slate-500">Diagnosis Result</div>
          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $badge }}">
            {{ $res }}
          </span>

          @if(!empty($report->notes))
            <div class="text-xs uppercase text-slate-500 mt-4">Notes</div>
            <div class="text-slate-800">{{ $report->notes }}</div>
          @endif
        </div>
      </div>
    </div>

    {{-- Footer actions (screen only) --}}
    <div class="flex items-center justify-end gap-2 screen-only">
      <button type="button"
              onclick="window.print()"
              class="inline-flex items-center h-9 px-3 rounded-lg text-sm font-medium bg-white border border-slate-200 shadow-sm hover:bg-slate-50">
        Print
      </button>
    </div>
  </div>
</div>

{{-- Print styles --}}
<style media="print">
  @page { margin: 12mm; }
  body * { visibility: hidden !important; }
  #print-report-root, #print-report-root * { visibility: visible !important; }

 
  .screen-only { display: none !important; }
  .print-title {
    display: block !important;
    margin: 0 0 8mm !important;
    font-size: 12pt !important;
    font-weight: 700 !important;
    color: #000 !important;
  }
</style>
@endsection
