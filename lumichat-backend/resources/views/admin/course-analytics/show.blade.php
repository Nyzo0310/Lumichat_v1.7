@extends('layouts.admin')
@section('title','Course Analytics • Details')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

  {{-- Header --}}
  <div class="flex items-start justify-between gap-4 screen-only">
    <div>
      <h2 class="text-2xl font-semibold tracking-tight text-slate-800">Course Analytics</h2>
      <p class="text-sm text-slate-500">{{ $title }}</p>
    </div>
    <div class="flex gap-2">
      <button type="button"
              onclick="window.print()"
              class="inline-flex items-center h-9 px-3 rounded-lg text-sm font-medium bg-green-600 text-white hover:bg-green-700">
        Print
      </button>
      <a href="{{ route('admin.course-analytics.index') }}"
         class="inline-flex items-center h-9 px-3 rounded-lg text-sm font-medium bg-white border border-slate-200 shadow-sm hover:bg-slate-50">
        ← Back to list
      </a>
    </div>
  </div>

  {{-- PRINT SCOPE START --}}
  <div id="print-analytics-show" class="space-y-6">
    {{-- Title for print --}}
    <h1 class="hidden print:block text-xl font-semibold">Course Analytics — {{ $title }}</h1>

    {{-- Summary --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 p-5">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <div class="text-xs uppercase text-slate-500">Course</div>
          <div class="font-semibold text-slate-900">{{ $course->course ?? '—' }}</div>
        </div>
        <div>
          <div class="text-xs uppercase text-slate-500">Year Level</div>
          <div class="font-medium text-slate-900">{{ $course->year_level ?? '—' }}</div>
        </div>
        <div>
          <div class="text-xs uppercase text-slate-500">No. of Students</div>
          <div class="font-medium text-slate-900">{{ $course->student_count ?? '—' }}</div>
        </div>
      </div>
    </div>

    {{-- Breakdown --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 p-5">
      <h3 class="text-base font-semibold text-slate-800 mb-3">Common Diagnosis Breakdown</h3>
      @php $items = $course->breakdown ?? []; @endphp
      @if(is_array($items) && count($items))
        <div class="divide-y divide-slate-100">
          @foreach($items as $row)
            <div class="py-3 flex items-center justify-between">
              <div class="text-slate-800">{{ $row['label'] ?? '—' }}</div>
              <div class="text-slate-700 font-medium">{{ $row['count'] ?? 0 }}</div>
            </div>
          @endforeach
        </div>
      @else
        <div class="py-12 text-center">
          <div class="mx-auto w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center">
            <img src="{{ asset('images/icons/nodata.png') }}" class="w-6 h-6 opacity-60" alt="">
          </div>
          <p class="mt-3 text-sm font-medium text-slate-700">No breakdown available</p>
          <p class="text-xs text-slate-500">This course has no compiled diagnosis data yet.</p>
        </div>
      @endif
    </div>

  
  {{-- PRINT SCOPE END --}}

</div>

{{-- Print CSS: show only the report section --}}
<style>
@media print{
  body *{ visibility:hidden !important; }
  #print-analytics-show, #print-analytics-show *{ visibility:visible !important; }
  #print-analytics-show{ position:fixed; inset:0; margin:12mm !important; background:#fff; }
  #print-analytics-show .shadow-sm{ box-shadow:none !important; }
  #print-analytics-show .border{ border:0 !important; }
  .screen-only{ display:none !important; }
  @page{ size:A4; margin:12mm 14mm; }
}
</style>
@endsection