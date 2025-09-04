@extends('layouts.admin')
@section('title','Diagnosis Reports')

@section('content')
@php
  // ---- DEMO FALLBACK (used only if $reports is not provided) ----
  $HAS_DATA = isset($reports) && count($reports ?? []) > 0;

  if (!$HAS_DATA) {
    $reports = [
      [
        'id'            => 1,
        'student_name'  => 'Juan Dela Cruz',
        'counselor'     => 'Ms. Grace Santos',
        'result'        => 'Mild Anxiety',
        'created_at'    => now()->subDays(2)->toDateTimeString(),
      ],
      [
        'id'            => 2,
        'student_name'  => 'Earl Sepida',
        'counselor'     => 'Mr. Leo Ramirez',
        'result'        => 'Normal',
        'created_at'    => now()->subDays(3)->toDateTimeString(),
      ],
      [
        'id'            => 3,
        'student_name'  => 'Faith Magayon',
        'counselor'     => 'Ms. Grace Santos',
        'result'        => 'Starting conversation...',
        'created_at'    => now()->subDays(4)->toDateTimeString(),
      ],
    ];
  }

  $dateKey = request('date','all');
  $q = request('q');
@endphp

<div class="max-w-7xl mx-auto space-y-6">

  {{-- ===== Page header ===== --}}
  <div class="mb-4">
    <h2 class="text-2xl font-semibold tracking-tight text-slate-800">Diagnosis Reports</h2>
    <p class="text-sm text-slate-500">Review summary reports based on student responses and emotional patterns.</p>
  </div>

  {{-- ===== Filters + Search row ===== --}}
  <div class="flex items-center justify-between gap-3 mb-6">
    <form method="GET" action="{{ route('admin.diagnosis-reports.index') }}" class="flex items-center gap-2">
      <select name="date"
              class="h-9 bg-white border border-slate-200 rounded-lg px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500">
        <option value="all"   {{ $dateKey==='all' ? 'selected' : '' }}>All Dates</option>
        <option value="7d"    {{ $dateKey==='7d' ? 'selected' : '' }}>Last 7 days</option>
        <option value="30d"   {{ $dateKey==='30d' ? 'selected' : '' }}>Last 30 days</option>
        <option value="month" {{ $dateKey==='month' ? 'selected' : '' }}>This month</option>
      </select>
    </form>

    <form method="GET" action="{{ route('admin.diagnosis-reports.index') }}" class="relative w-full max-w-xs">
      <input type="hidden" name="date" value="{{ $dateKey }}">
      <label for="qInput" class="sr-only">Search Student name</label>
      <input id="qInput" name="q" value="{{ $q }}" placeholder="Search student or report ID..."
             class="w-full h-9 rounded-lg border border-slate-200 bg-white pl-10 pr-3 text-sm
                    placeholder:text-slate-400 shadow-sm
                    focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
      <img src="{{ asset('images/icons/search.png') }}"
           alt="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-70 pointer-events-none">
    </form>
  </div>

  {{-- ===== Table ===== --}}
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-[900px] w-full text-sm text-left">
        {{-- Dark header to match Chatbot Sessions --}}
        <thead class="bg-slate-200 text-slate-800 shadow-sm">
          <tr class="text-[12px] uppercase tracking-wide">
            <th class="px-6 py-3 font-semibold whitespace-nowrap">ID</th>
            <th class="px-6 py-3 font-semibold whitespace-nowrap">Student Name</th>
            <th class="px-6 py-3 font-semibold whitespace-nowrap">Counselor Name</th>
            <th class="px-6 py-3 font-semibold whitespace-nowrap">Diagnosis Result</th>
            <th class="px-6 py-3 font-semibold whitespace-nowrap">Date</th>
            <th class="px-6 py-3 text-right font-semibold whitespace-nowrap">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100 text-slate-800">
          @forelse ($reports as $r)
            @php
              // Works with model or array
              $id       = is_array($r) ? $r['id'] : $r->id;
              $name     = is_array($r) ? ($r['student_name'] ?? '—') : ($r->student->name ?? '—');
              $counsel  = is_array($r) ? ($r['counselor'] ?? '—') : ($r->counselor->name ?? '—');
              $result   = is_array($r) ? ($r['result'] ?? '—') : ($r->result ?? '—');
              $date     = is_array($r)
                          ? \Carbon\Carbon::parse($r['created_at'])->format('F d, Y')
                          : ($r->created_at?->format('F d, Y') ?? '—');
              $code     = 'DRP-' . now()->format('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
            @endphp

            <tr class="hover:bg-slate-50 transition">
              <td class="px-6 py-4 font-medium text-slate-900 whitespace-nowrap">{{ $code }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ $name }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ $counsel }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ $result }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ $date }}</td>
              <td class="px-6 py-4 text-right">
                <a href="{{ route('admin.diagnosis-reports.show', $id) }}"
                   class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-medium
                          bg-indigo-600 text-white hover:bg-indigo-700">
                  View
                </a>
              </td>
            </tr>
          @empty
            {{-- Empty state --}}
            <tr>
              <td colspan="6" class="px-6 pt-14 pb-10 text-center">
                <div class="mx-auto w-full max-w-sm">
                  <div class="mx-auto w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center">
                    <img src="{{ asset('images/icons/nodata.png') }}" alt="" class="w-6 h-6 opacity-60">
                  </div>
                  <p class="mt-3 text-sm font-medium text-white/90"></p>
                  <p class="mt-3 text-sm font-medium text-slate-700">No diagnosis reports found</p>
                  <p class="text-xs text-slate-500 mb-6">Reports will appear here once counselors finalize a diagnosis.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination (auto if paginator provided) --}}
    @if(isset($reports) && is_object($reports) && method_exists($reports,'hasPages') && $reports->hasPages())
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/70">
        {{ $reports->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
