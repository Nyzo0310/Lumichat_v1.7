@extends('layouts.admin')
@section('title','Counselor Logs · '.$counselor->full_name)

@section('content')
@php
  $label = \Carbon\Carbon::create($year,$month,1)->format('F Y');
@endphp

<div class="space-y-6">

  {{-- Header (hidden on print) --}}
  <div class="flex items-start justify-between gap-3 screen-only">
    <div>
      <h2 class="text-2xl font-semibold text-slate-900">{{ $counselor->full_name }}</h2>
      <p class="text-sm text-slate-500">Logs for <span class="font-medium text-slate-700">{{ $label }}</span></p>
    </div>
    <div class="flex items-center gap-2">
      <button type="button"
              onclick="window.print()"
              class="inline-flex items-center h-10 px-3 rounded-lg text-sm font-medium bg-green-600 text-white hover:bg-green-700">
        Print
      </button>
      <a href="{{ route('admin.counselor-logs.index') }}"
         class="inline-flex items-center h-10 px-3 rounded-lg text-sm font-medium border border-slate-200 text-slate-700 hover:bg-slate-50">
        ← Back
      </a>
    </div>
  </div>

  {{-- PRINT SCOPE START --}}
  <div id="print-counselor-show" class="space-y-4">

    {{-- Print title --}}
    <h1 class="hidden print:block text-xl font-semibold">Counselor Logs — {{ $counselor->full_name }} ({{ $label }})</h1>

    {{-- Diagnosis summary (chips) --}}
    @if($dxCounts->count())
      <div class="flex flex-wrap gap-2">
        @foreach($dxCounts as $dx)
          <span class="px-3 py-1.5 rounded-full border border-sky-200 bg-sky-50 text-sky-700 text-xs">
            {{ $dx->diagnosis_result }} • {{ $dx->cnt }}
          </span>
        @endforeach
      </div>
    @endif

    {{-- Table --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-200 text-sm font-semibold text-slate-800">Non-Technical</div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="text-left px-4 py-3 font-medium">Student</th>
              <th class="text-left px-4 py-3 font-medium">Scheduled</th>
              <th class="text-left px-4 py-3 font-medium">Diagnosis / Result</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($students as $row)
              <tr class="hover:bg-slate-50/60">
                <td class="px-4 py-3">{{ $row->student_name ?? '—' }}</td>
                <td class="px-4 py-3">{{ $row->scheduled_at_fmt }}</td>
                <td class="px-4 py-3">{{ $row->diagnosis_result }}</td>
              </tr>
            @empty
              <tr><td colspan="3" class="px-4 py-10 text-center text-slate-500">No appointments this month.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>
  {{-- PRINT SCOPE END --}}
</div>

{{-- Print rules: isolate this report --}}
<style>
@media print{
  body *{ visibility:hidden !important; }
  #print-counselor-show, #print-counselor-show *{ visibility:visible !important; }
  #print-counselor-show{ position:fixed; inset:0; margin:12mm !important; background:#fff; }
  #print-counselor-show .overflow-x-auto{ overflow:visible !important; }
  #print-counselor-show .shadow-sm{ box-shadow:none !important; }
  #print-counselor-show .border{ border:0 !important; }
  .screen-only{ display:none !important; }
  @page{ size:A4; margin:12mm 14mm; }
}
</style>
@endsection