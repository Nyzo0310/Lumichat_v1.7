@extends('layouts.admin')
@section('title','Admin · Student Records')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

  {{-- Page header (screen only) --}}
  {{-- Page header (screen only) --}}
@php
  $totalStudents = method_exists($students, 'total') ? $students->total() : $students->count();
@endphp
<div class="flex items-center justify-between animate-fadeup screen-only">
  <div>
    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Student Records</h2>
    <p class="text-sm text-slate-600">
      View and manage student accounts and their academic details.
      <span class="ml-2 text-slate-400">•</span>
      <span class="ml-2 text-slate-600">
        {{ $totalStudents }} {{ Str::plural('student', $totalStudents) }}
      </span>
    </p>
  </div>

    <div class="flex items-center gap-3">
      {{-- Search (debounced; no extra vars needed) --}}
      <form id="searchForm" method="GET" action="{{ route('admin.students.index') }}" class="hidden sm:block">
        <div class="relative">
          <input id="q-input" type="text" name="q" value="{{ request('q') }}" autocomplete="off"
                 placeholder="Search student"
                 class="w-72 bg-white border border-slate-200 rounded-xl pl-10 pr-9 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"/>
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
            <path d="M21 21l-4.3-4.3" stroke-width="2" stroke-linecap="round"></path>
          </svg>
          @if(request('q'))
          <button type="button" title="Clear" aria-label="Clear"
                  class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-md text-slate-400 hover:text-slate-600 focus:ring-2 focus:ring-indigo-500"
                  onclick="document.getElementById('q-input').value=''; document.getElementById('searchForm').submit();">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
          @endif
        </div>
      </form>

      <button type="button" onclick="printStudentTable()"
              class="inline-flex items-center gap-2 bg-emerald-600 text-white px-4 py-2 rounded-xl shadow-sm hover:bg-emerald-700 active:scale-[.99] transition">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V4h12v5M6 18h12a2 2 0 002-2v-5H4v5a2 2 0 002 2z"/>
        </svg>
        Print
      </button>
    </div>
  </div>

  {{-- PRINT SCOPE + TABLE --}}
  <div id="print-root" class="space-y-2">
    <h1 class="print-title hidden">Student Records</h1>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 overflow-hidden">
      <div class="relative overflow-x-auto">
        <table class="min-w-full text-sm leading-6 table-auto">
          <colgroup>
            <col style="width:24%">
            <col style="width:25%">
            <col style="width:18%">
            <col style="width:15%">
            <col style="width:15%">
            <col class="col-action" style="width:0"> {{-- hidden in print --}}
          </colgroup>

          {{-- Sticky header (screen) to match Counselor --}}
          <thead class="bg-slate-100 border-b border-slate-200 text-slate-700">
            <tr class="align-middle">
              <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Student Name</th>
              <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Email</th>
              <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Contact No.</th>
              <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Course</th>
              <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Year Level</th>
              <th class="px-6 py-3 text-right font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap col-action">Action</th>
            </tr>
          </thead>

          {{-- PRINT HEADER (optional; non-sticky) --}}
          <thead class="hidden print:table-header-group bg-slate-200 text-slate-800">
            <tr>
              <th class="px-6 py-2 text-left text-[11px] uppercase">Student Name</th>
              <th class="px-6 py-2 text-left text-[11px] uppercase">Email</th>
              <th class="px-6 py-2 text-left text-[11px] uppercase">Contact No.</th>
              <th class="px-6 py-2 text-left text-[11px] uppercase">Course</th>
              <th class="px-6 py-2 text-left text-[11px] uppercase">Year Level</th>
              <th class="px-6 py-2 text-right text-[11px] uppercase col-action">Action</th>
            </tr>
          </thead>


          <tbody class="divide-y divide-slate-100">
            @forelse ($students as $s)
              <tr class="align-middle even:bg-slate-50 hover:bg-slate-100/60 transition">
                <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-900">{{ $s->name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-slate-700">{{ $s->email }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-slate-700">{{ $s->contact_number ?? '—' }}</td>

                {{-- Course chip (indigo) --}}
                <td class="px-6 py-4 whitespace-nowrap">
                  @if($s->course)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200">
                      {{ $s->course }}
                    </span>
                  @else
                    <span class="text-slate-400">—</span>
                  @endif
                </td>

                {{-- Year chip (violet) --}}
                <td class="px-6 py-4 whitespace-nowrap">
                  @if($s->year_level)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs bg-violet-50 text-violet-700 ring-1 ring-violet-200">
                      {{ $s->year_level }}
                    </span>
                  @else
                    <span class="text-slate-400">—</span>
                  @endif
                </td>

                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                    <a href="{{ route('admin.students.show', $s->id) }}"
                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-600 text-white hover:-translate-y-0.5 active:scale-[.98] transition"
                       title="View" aria-label="View student">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z" />
                        <circle cx="12" cy="12" r="3" stroke-width="2" />
                      </svg>
                    </a>

                    <a href="mailto:{{ $s->email }}"
                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 hover:ring-slate-300 active:scale-[.98] transition"
                       title="Send Email" aria-label="Send email">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                    </a>

                    <button type="button" onclick="copyToClipboard('{{ $s->email }}')"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 hover:ring-slate-300 active:scale-[.98] transition"
                            title="Copy Email" aria-label="Copy email">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2" stroke-width="2"/>
                        <rect x="3" y="3" width="13" height="13" rx="2" ry="2" stroke-width="2"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-6 py-10 text-center text-slate-500">No students found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($students->hasPages())
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/70 not-print">
          {{ $students->appends(['q'=>request('q')])->links() }}
        </div>
      @endif
    </div>
  </div>
</div>

{{-- Helpers (copy + debounce + print) --}}
<script>
  const qInput = document.getElementById('q-input');
  const form   = document.getElementById('searchForm');
  let t = null;
  if (qInput && form) {
    qInput.addEventListener('input', function () {
      if (t) clearTimeout(t);
      t = setTimeout(function () { form.submit(); }, 300);
    });
  }
  function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function () {
      if (window.Swal) {
        Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Email copied', showConfirmButton:false, timer:1500 });
      }
    });
  }
  function printStudentTable(){ window.print(); }
</script>

{{-- PRINT ONLY --}}
<style media="print">
  @page { margin: 12mm; }
  body * { visibility: hidden !important; }
  #print-root, #print-root * { visibility: visible !important; }
  #print-root {
    position: fixed !important; inset: 0 !important; margin: 12mm !important;
    background:#fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }
  #print-root .rounded-2xl, #print-root .shadow-sm, #print-root .border { border:0 !important; box-shadow:none !important; }
  #print-root .overflow-hidden, #print-root .overflow-x-auto { overflow: visible !important; }

  .print-title { display:block !important; margin:0 0 8mm !important; font-size:20pt !important; font-weight:700 !important; color:#000 !important; }

  /* Hide Action column on print */
  #print-root th.col-action,
  #print-root td.col-action,
  #print-root col.col-action,
  #print-root thead th:last-child,
  #print-root tbody td:last-child { display:none !important; visibility:hidden !important; }

  #print-root tr { page-break-inside: avoid !important; }
</style>
@endsection
