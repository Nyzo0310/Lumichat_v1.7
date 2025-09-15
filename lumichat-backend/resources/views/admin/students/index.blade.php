@extends('layouts.admin')
@section('title','Student Records')

@section('content')
@php
  $pill = 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium';
  $pillSlate = $pill.' bg-slate-100 text-slate-700 ring-1 ring-slate-200';
  $pillBlue  = $pill.' bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200';
@endphp

<div class="max-w-7xl mx-auto p-6 space-y-6">

  {{-- Page header (hidden in print) --}}
  <div class="flex items-center justify-between screen-only">
    <div>
      <h2 class="text-2xl font-bold tracking-tight text-slate-800">Student Records</h2>
      <p class="text-sm text-slate-500">View and manage student accounts and their academic details.</p>
    </div>

    <div class="flex gap-3">
      <button type="button" onclick="printStudentTable()" class="px-3 py-1.5 rounded-lg bg-green-600 text-white text-sm hover:bg-green-700">
        Print
      </button>

      {{-- Search (minimal, submits on Enter) --}}
      <form method="GET" action="{{ route('admin.students.index') }}" class="flex items-center gap-2">
        <div class="relative">
          <input type="text" name="q" value="{{ old('q', $q ?? '') }}" placeholder="Search student"
                 class="w-72 bg-white border border-slate-200 rounded-lg pl-3 pr-10 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                 autocomplete="off" />
          <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
            <path d="M21 21l-4.3-4.3" stroke-width="2" stroke-linecap="round"></path>
          </svg>
        </div>
        @if(!empty($q))
          <a href="{{ route('admin.students.index') }}" class="text-sm text-slate-600 hover:underline">Reset</a>
        @endif
      </form>
    </div>
  </div>

  {{-- PRINT SCOPE + TABLE --}}
  <div id="print-root" class="space-y-2">
    {{-- Print-only title (shows only in print) --}}
    <h1 class="print-title hidden">Student Records</h1>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm table-auto">
          <colgroup>
            <col style="width:24%">
            <col style="width:25%">
            <col style="width:18%">
            <col style="width:15%">
            <col style="width:15%">
            <col class="col-action" style="width:0"> {{-- hidden in print --}}
          </colgroup>
          <thead class="bg-slate-200 text-slate-800 shadow-sm">
            <tr class="align-middle">
              <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Student Name</th>
              <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Email</th>
              <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Contact No.</th>
              <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Course</th>
              <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Year Level</th>
              <th class="px-6 py-3 text-right font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap col-action">Action</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            @forelse ($students as $s)
              <tr class="hover:bg-slate-50/70 align-middle">
                {{-- switched to users.name --}}
                <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-900">{{ $s->name }}</td>

                <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-900">{{ $s->email }}</td>
                <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-900">{{ $s->contact_number ?? '—' }}</td>
                <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-900">{{ $s->course ?? '—' }}</td>
                <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-900">{{ $s->year_level ?? '—' }}</td>

                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                    {{-- be explicit: pass id to show route (now bound to User) --}}
                    <a href="{{ route('admin.students.show', $s->id) }}"
                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white" title="View">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z" />
                        <circle cx="12" cy="12" r="3" stroke-width="2" />
                      </svg>
                    </a>

                    <a href="mailto:{{ $s->email }}"
                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white text-slate-700 ring-1 ring-slate-200"
                       title="Send Email">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                    </a>

                    <button type="button" onclick="copyToClipboard('{{ $s->email }}')"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white text-slate-700 ring-1 ring-slate-200"
                            title="Copy Email">
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
                <td colspan="6" class="px-6 py-10 text-center">
                  <div class="text-slate-500">No students found.</div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($students->hasPages())
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/70 not-print">
          {{ $students->links() }}
        </div>
      @endif
    </div>
  </div>
</div>

{{-- copy helper (uses SweetAlert2 already loaded in layout) --}}
<script>
  function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
      if (window.Swal) {
        Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Email copied', showConfirmButton:false, timer:1500 });
      }
    });
  }
</script>

{{-- PRINT ONLY: "Student Records" + the table --}}
<style media="print">
  @page { margin: 12mm; }

  /* Hide everything; show only the print scope */
  body * { visibility: hidden !important; }
  #print-root, #print-root * { visibility: visible !important; }

  /* Full-width canvas; remove card chrome & inner scrollbars */
  #print-root {
    position: fixed !important; inset: 0 !important; margin: 12mm !important;
    background:#fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }
  #print-root .rounded-2xl, #print-root .shadow-sm, #print-root .border { border:0 !important; box-shadow:none !important; }
  #print-root .overflow-hidden, #print-root .overflow-x-auto { overflow: visible !important; }

  /* Title */
  .print-title { display:block !important; margin:0 0 8mm !important; font-size:20pt !important; font-weight:700 !important; color:#000 !important; }

  /* Hide the Action column entirely on print */
  #print-root th.col-action,
  #print-root td.col-action,
  #print-root col.col-action,
  #print-root thead th:last-child,
  #print-root tbody td:last-child { display:none !important; visibility:hidden !important; }

  /* Avoid page-break glitches */
  #print-root tr { page-break-inside: avoid !important; }
</style>
<script>
  function printStudentTable(){ window.print(); }
</script>

@endsection
