@extends('layouts.admin')
@section('title','Course Analytics')

@section('content')
@php
  // ---- DEMO FALLBACK (used only if $courses not provided) ----
  $HAS_DATA = isset($courses) && count($courses ?? []) > 0;

  if (!$HAS_DATA) {
    $courses = [
      [
        'id' => 1,
        'course' => 'BSIT',
        'year_level' => '2nd Year',
        'student_count' => 300,
        'common_diagnoses' => [
          'Anxiety Disorders', 'Academic Stress', 'Social Anxiety',
          'Depression', 'Adjustment Disorders', 'Sleep Disorders'
        ],
      ],
      [
        'id' => 2,
        'course' => 'BSBA',
        'year_level' => '1st Year',
        'student_count' => 220,
        'common_diagnoses' => ['Academic Stress', 'Time Management', 'Sleep Disorders'],
      ],
      [
        'id' => 3,
        'course' => 'BSED',
        'year_level' => '3rd Year',
        'student_count' => 180,
        'common_diagnoses' => ['Anxiety Disorders', 'Burnout Risk'],
      ],
    ];
  }

  $yearKey = request('year','all');
  $q = request('q');
@endphp

<div class="max-w-7xl mx-auto space-y-6">

  {{-- ===== Page header ===== --}}
  <div class="mb-4">
    <h2 class="text-2xl font-semibold tracking-tight text-slate-800">Course Analytics</h2>
    <p class="text-sm text-slate-500">Visual breakdown of mental wellness patterns across different student programs.</p>
  </div>

  {{-- ===== Filters + Search row ===== --}}
  <div class="flex items-center justify-between gap-3 mb-6">
    <form method="GET" action="{{ route('admin.course-analytics.index') }}" class="flex items-center gap-2">
      <select name="year"
        class="h-9 w-40 bg-white border border-slate-200 rounded-lg px-3 text-sm
               focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500">
        <option value="all"   {{ $yearKey==='all' ? 'selected' : '' }}>Select Year Level</option>
        <option value="1"     {{ $yearKey==='1' ? 'selected' : '' }}>1st Year</option>
        <option value="2"     {{ $yearKey==='2' ? 'selected' : '' }}>2nd Year</option>
        <option value="3"     {{ $yearKey==='3' ? 'selected' : '' }}>3rd Year</option>
        <option value="4"     {{ $yearKey==='4' ? 'selected' : '' }}>4th Year</option>
        </select>
    </form>

    <form method="GET" action="{{ route('admin.course-analytics.index') }}" class="relative w-full max-w-xs">
      <input type="hidden" name="year" value="{{ $yearKey }}">
      <label for="qInput" class="sr-only">Search Course</label>
      <input id="qInput" name="q" value="{{ $q }}" placeholder="Search course..."
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
      <table class="min-w-[920px] w-full text-sm text-left">
        {{-- Dark header like your other report pages --}}
        <thead class="bg-slate-200 text-slate-800 shadow-sm">
          <tr class="text-[12px] uppercase tracking-wide">
            <th class="px-6 py-3 font-semibold whitespace-nowrap">Course</th>
            <th class="px-6 py-3 font-semibold whitespace-nowrap">Year Level</th>
            <th class="px-6 py-3 font-semibold whitespace-nowrap">No. of Students</th>
            <th class="px-6 py-3 font-semibold whitespace-nowrap">Common Diagnosis</th>
            <th class="px-6 py-3 text-right font-semibold whitespace-nowrap">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100 text-slate-800">
          @forelse ($courses as $c)
            @php
              $id     = is_array($c) ? $c['id'] : $c->id;
              $course = is_array($c) ? $c['course'] : ($c->course ?? '—');
              $year   = is_array($c) ? $c['year_level'] : ($c->year_level ?? '—');
              $count  = is_array($c) ? $c['student_count'] : ($c->student_count ?? '—');
              $list   = is_array($c)
                        ? ($c['common_diagnoses'] ?? [])
                        : (is_array($c->common_diagnoses ?? null) ? $c->common_diagnoses : []);
              $diagnoses = count($list) ? implode(', ', $list) : '—';
            @endphp

            <tr class="hover:bg-slate-50 transition align-top">
              <td class="px-6 py-4 font-medium text-slate-900 whitespace-nowrap">{{ $course }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ $year }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ $count }}</td>
              <td class="px-6 py-4">
                <div class="max-w-[420px] leading-relaxed">{{ $diagnoses }}</div>
              </td>
              <td class="px-6 py-4 text-right">
                <a href="{{ route('admin.course-analytics.show', $id) }}"
                   class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-medium
                          bg-indigo-600 text-white hover:bg-indigo-700">
                  View
                </a>
              </td>
            </tr>
          @empty
            {{-- Empty state --}}
            <tr>
              <td colspan="5" class="px-6 pt-14 pb-10 text-center">
                <div class="mx-auto w-full max-w-sm">
                  <div class="mx-auto w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center">
                    <img src="{{ asset('images/icons/nodata.png') }}" alt="" class="w-6 h-6 opacity-60">
                  </div>
                  <p class="mt-3 text-sm font-medium text-slate-700">No course analytics found</p>
                  <p class="text-xs text-slate-500 mb-6">Analytics will appear here once data becomes available.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
