@extends('layouts.admin')
@section('title','Counselor Logs')

@section('content')
@php
  // For the subtitle header
  $cName = $cid ? optional($counselors->firstWhere('id',$cid))->full_name : 'All';
  $mName = $month ? \Carbon\Carbon::create(null,$month,1)->format('F') : 'All';
  $yName = $year ?: 'All';
@endphp

<div class="space-y-6">

  {{-- Header (hidden on print) --}}
  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 screen-only">
    <div>
      <h2 class="text-2xl font-semibold text-slate-900">Counselor Logs</h2>
      <p class="text-sm text-slate-500">Per counselor, grouped by Month/Year with students handled and most common diagnosis.</p>
    </div>
    <button type="button"
            onclick="window.print()"
            class="px-3 py-2 rounded-lg bg-green-600 text-white text-sm hover:bg-green-700">
      Print
    </button>
  </div>

  {{-- PRINT SCOPE START --}}
  <div id="print-counselor-index" class="space-y-4">

    {{-- Filters --}}
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 screen-only">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Counselor</label>
        <select name="counselor_id"
                class="w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-sky-500">
          <option value="">All counselors</option>
          @foreach($counselors as $co)
            <option value="{{ $co->id }}" @selected($cid==$co->id)>{{ $co->full_name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Month</label>
        <select name="month"
                class="w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-sky-500">
          <option value="">All</option>
          @for($m=1;$m<=12;$m++)
            <option value="{{ $m }}" @selected($month==$m)>{{ \Carbon\Carbon::create(null,$m,1)->format('F') }}</option>
          @endfor
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Year</label>
        <select name="year"
                class="w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-sky-500">
          <option value="">All</option>
          @foreach($years as $y)
            <option value="{{ $y }}" @selected($year==$y)>{{ $y }}</option>
          @endforeach
        </select>
      </div>

      <div class="flex items-end gap-2">
        <button class="px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white">Apply</button>
        <a href="{{ route('admin.counselor-logs.index') }}"
           class="px-3 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50">Reset</a>
      </div>
    </form>

    {{-- Results --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
        <div class="text-sm font-semibold text-slate-800">
          Non-Technical ({{ $cName }}, {{ $mName }}, {{ $yName }})
        </div>
        <div class="text-xs text-slate-500 screen-only">
          Showing {{ $rows->firstItem() }}–{{ $rows->lastItem() }} of {{ $rows->total() }}
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="text-left px-4 py-3 font-medium">Counselor</th>
              <th class="text-left px-4 py-3 font-medium">Month / Year</th>
              <th class="text-left px-4 py-3 font-medium">Students handled</th>
              <th class="text-left px-4 py-3 font-medium">Common diagnosis</th>
              <th class="text-right px-4 py-3 font-medium screen-only">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
          @forelse($rows as $r)
            <tr class="hover:bg-slate-50/60">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-sky-100 text-sky-700 grid place-items-center text-xs font-semibold">
                    {{ \Illuminate\Support\Str::of($r->counselor_name)->explode(' ')->map(fn($p)=>mb_substr($p,0,1))->take(2)->join('') }}
                  </div>
                  <div class="font-medium text-slate-800">{{ $r->counselor_name }}</div>
                </div>
              </td>
              <td class="px-4 py-3">{{ $r->month_year }}</td>
              <td class="px-4 py-3">
                @if($r->students_list)
                  <div class="line-clamp-2 text-slate-700">{{ str_replace(' | ', ', ', $r->students_list) }}</div>
                  <div class="text-xs text-slate-500 mt-0.5">{{ $r->students_count }} unique</div>
                @else
                  <span class="text-slate-400">—</span>
                @endif
              </td>
              <td class="px-4 py-3">{{ $r->common_dx ?: '—' }}</td>
              <td class="px-4 py-3 text-right screen-only">
                <a href="{{ route('admin.counselor-logs.show', ['counselor'=>$r->counselor_id, 'month'=>$r->month_num, 'year'=>$r->year_num]) }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white">
                  View
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No records found.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>

      <div class="p-3 border-t border-slate-200 screen-only">
        {{ $rows->withQueryString()->links() }}
      </div>
    </div>
  </div>
  {{-- PRINT SCOPE END --}}

</div>

{{-- Print rules: show only the report section, hide Action col, remove chrome --}}
<style>
@media print{
  body *{ visibility:hidden !important; }
  #print-counselor-index, #print-counselor-index *{ visibility:visible !important; }
  #print-counselor-index{ position:fixed; inset:0; margin:12mm !important; background:#fff; }
  #print-counselor-index .overflow-x-auto{ overflow:visible !important; }
  #print-counselor-index .shadow-sm{ box-shadow:none !important; }
  #print-counselor-index .border{ border:0 !important; }
  .screen-only{ display:none !important; }
  /* Hide Action column on print */
  #print-counselor-index thead th:last-child,
  #print-counselor-index tbody td:last-child{ display:none !important; }
  @page{ size:A4; margin:12mm 14mm; }
}
</style>
@endsection