@extends('layouts.admin')
@section('title','Admin · Chatbot Sessions')


@php
  $q = $q ?? request('q', '');
  $dateKey = $dateKey ?? request('date','all');
  $total = method_exists($sessions,'total') ? $sessions->total() : $sessions->count();
@endphp

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

  {{-- ========= Page Header (consistent) ========= --}}
  <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between animate-fadeup screen-only">
    <div>
      <h2 class="text-2xl font-bold tracking-tight text-slate-900">Chatbot Sessions</h2>
      <p class="text-sm text-slate-600">
        View conversation histories and emotional trends from chatbot sessions.
        <span class="ml-2 text-slate-400">•</span>
        <span class="ml-2 text-slate-500">{{ $total }} {{ Str::plural('session', $total) }}</span>
      </p>
    </div>

    <button type="button" onclick="window.print()"
            class="inline-flex items-center gap-2 bg-emerald-600 text-white px-4 py-2 h-10 rounded-xl shadow-sm hover:bg-emerald-700 active:scale-[.99] transition">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V4h12v5M6 18h12a2 2 0 002-2v-5H4v5a2 2 0 002 2z"/>
      </svg>
      Print
    </button>
  </div>

  {{-- ========= Filter Bar ========= --}}
  <form method="GET" action="{{ route('admin.chatbot-sessions.index') }}" class="mb-6 screen-only">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end animate-fadeup">

      {{-- Date Range --}}
      <div class="md:col-span-3 min-w-0">
        <label class="block text-xs font-medium text-slate-600 mb-1">Date Range</label>
        <select name="date"
                class="w-full h-10 bg-white border border-slate-200 rounded-xl px-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
          <option value="all"    @selected($dateKey==='all')>All Dates</option>
          <option value="7d"     @selected($dateKey==='7d')>Last 7 days</option>
          <option value="30d"    @selected($dateKey==='30d')>Last 30 days</option>
          <option value="month"  @selected($dateKey==='month')>This month</option>
        </select>
      </div>

      {{-- Search --}}
      <div class="md:col-span-3 min-w-0">
        <label class="block text-xs font-medium text-slate-600 mb-1">Search</label>
        <div class="relative">
          <input type="text" name="q" value="{{ $q }}" placeholder="Search student or session ID"
                class="w-full h-10 bg-white border border-slate-200 rounded-xl pl-10 pr-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
          <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="11" cy="11" r="7" stroke-width="2"/>
            <path d="M21 21l-4.3-4.3" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
      </div>

      {{-- Buttons --}}
      <div class="md:col-span-6 flex items-center justify-end gap-2">
        <a href="{{ route('admin.chatbot-sessions.index') }}"
          class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-sm">
          Reset
        </a>
        <button class="inline-flex items-center justify-center h-10 px-5 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm text-sm">
          Apply
        </button>
      </div>

    </div>
  </form>


  {{-- ========= Table ========= --}}
  <div id="cb-print-root" class="bg-white rounded-2xl shadow-sm border border-slate-200/70 overflow-hidden">
    <div class="relative overflow-x-auto">
      <table class="min-w-full text-sm leading-6 table-auto">
        <colgroup>
          <col style="width:22%">
          <col style="width:26%">
          <col style="width:28%">
          <col style="width:16%">
          <col class="col-action" style="width:8%"> {{-- hidden in print --}}
        </colgroup>

        <thead class="bg-slate-100 border-b border-slate-200 text-slate-700">
          <tr class="align-middle">
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Session ID</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Student Name</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Initial Result</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Initial Date</th>
            <th class="px-6 py-3 text-right font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap col-action">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
          @forelse ($sessions as $s)
            @php
              $code = 'LMC-' . now()->format('Y') . '-' . str_pad($s->id, 4, '0', STR_PAD_LEFT);
            @endphp
            <tr class="align-middle even:bg-slate-50 hover:bg-slate-100/60 transition">
              <td class="px-6 py-4 font-semibold text-slate-900">{{ $code }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-slate-700">{{ $s->user->name ?? '—' }}</td>
              <td class="px-6 py-4 text-slate-700">{{ $s->topic_summary ?? '—' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-slate-700">{{ $s->created_at?->format('M d, Y') }}</td>

              <td class="px-6 py-4 text-right col-action">
                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                  <a href="{{ route('admin.chatbot-sessions.show', $s) }}"
                     class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-600 text-white hover:-translate-y-0.5 active:scale-[.98] transition"
                     title="View" aria-label="View session">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z" />
                      <circle cx="12" cy="12" r="3" stroke-width="2" />
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-6 py-10 text-center text-slate-500">No sessions found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($sessions->hasPages())
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/70 not-print">
        {{ $sessions->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>

{{-- ========= PRINT ONLY (mirror other pages) ========= --}}
<style media="print">
  @page { margin: 12mm; }
  body * { visibility: hidden !important; }
  #cb-print-root, #cb-print-root * { visibility: visible !important; }
  #cb-print-root {
    position: fixed !important; inset: 0 !important; margin: 12mm !important;
    background:#fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }
  #cb-print-root .rounded-2xl, #cb-print-root .shadow-sm, #cb-print-root .border { border:0 !important; box-shadow:none !important; }
  #cb-print-root .overflow-hidden, #cb-print-root .overflow-x-auto { overflow: visible !important; }

  /* Hide Action column on print */
  #cb-print-root th.col-action,
  #cb-print-root td.col-action,
  #cb-print-root col.col-action,
  #cb-print-root thead th:last-child,
  #cb-print-root tbody td:last-child { display:none !important; visibility:hidden !important; }

  #cb-print-root tr { page-break-inside: avoid !important; }
</style>
@endsection
