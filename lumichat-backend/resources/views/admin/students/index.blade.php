@extends('layouts.admin')
@section('title','Student Records')

@section('content')
@php
  $pill = 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium';
  $pillSlate = $pill.' bg-slate-100 text-slate-700 ring-1 ring-slate-200';
  $pillBlue  = $pill.' bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200';
@endphp

<div class="max-w-7xl mx-auto p-6 space-y-6">

  {{-- Page header --}}
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold tracking-tight text-slate-800">Student Records</h2>
      <p class="text-sm text-slate-500">View and manage student accounts and their academic details.</p>
    </div>

    {{-- Search (minimal, submits on Enter) --}}
    <form method="GET" action="{{ route('admin.students.index') }}" class="flex items-center gap-2">
      <div class="relative">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search student"
               class="w-72 bg-white border border-slate-200 rounded-lg pl-3 pr-10 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
               autocomplete="off" />
        <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
          <path d="M21 21l-4.3-4.3" stroke-width="2" stroke-linecap="round"></path>
        </svg>
      </div>
      @if($q)
        <a href="{{ route('admin.students.index') }}" class="text-sm text-slate-600 hover:underline">Reset</a>
      @endif
    </form>
  </div>

  {{-- Table card (counselor-matched styling) --}}
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm table-auto">
        <thead class="bg-slate-200 text-slate-800 shadow-sm">
          <tr class="align-middle">
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Student Name</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Email</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Contact No.</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Course</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Year Level</th>
            <th class="px-6 py-3 text-right font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
        @forelse ($students as $s)
            <tr class="hover:bg-slate-50/70 align-middle">
            <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-900">{{ $s->full_name }}</td>
            <td class="px-6 py-4 text-slate-700 whitespace-nowrap">{{ $s->email }}</td>
            <td class="px-6 py-4 text-slate-700 whitespace-nowrap">{{ $s->contact_number }}</td>
            <td class="px-6 py-4 text-slate-800">{{ $s->course ?? '—' }}</td>   {{-- Normal text --}}
            <td class="px-6 py-4 text-slate-800">{{ $s->year_level ?? '—' }}</td> {{-- Normal text --}}
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                {{-- View --}}
                <a href="{{ route('admin.students.show', $s) }}"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white hover:bg-blue-700 active:scale-[.97] transition"
                    title="View">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z" />
                    <circle cx="12" cy="12" r="3" stroke-width="2" />
                    </svg>
                </a>

                {{-- Email --}}
                <a href="mailto:{{ $s->email }}"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 hover:ring-slate-300 active:scale-[.97] transition"
                    title="Send Email">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </a>

                {{-- Copy --}}
                <button type="button" onclick="copyToClipboard('{{ $s->email }}')"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 hover:ring-slate-300 active:scale-[.97] transition"
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
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/70">
        {{ $students->links() }}
      </div>
    @endif
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
@endsection
