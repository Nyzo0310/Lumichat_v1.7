@extends('layouts.admin')
@section('title','Diagnosis Report Details')

@section('content')
@php
  // ---- DEMO FALLBACK (only if $report not provided) ----
  if (!isset($report)) {
    $demo = [
      1 => [
        'id' => 1,
        'student_name' => 'Juan Dela Cruz',
        'counselor' => 'Ms. Grace Santos',
        'result' => 'Mild Anxiety',
        'created_at' => now()->subDays(2)->toDateTimeString(),
        'summary' => 'Student showing mild anxiety indicators primarily about exams; coping strategies discussed.',
        'recommendations' => [
          'Breathing exercises 5–10 minutes daily',
          'Schedule counseling check-in next week',
          'Reduce caffeine and improve sleep hygiene',
        ],
        'notes' => 'Monitor over the next two weeks; escalate if symptoms persist or worsen.',
      ],
      2 => [
        'id' => 2,
        'student_name' => 'Earl Sepida',
        'counselor' => 'Mr. Leo Ramirez',
        'result' => 'Normal',
        'created_at' => now()->subDays(3)->toDateTimeString(),
        'summary' => 'No significant risk indicators; student reports normal stress levels.',
        'recommendations' => ['Optional wellness seminar attendance'],
        'notes' => null,
      ],
      3 => [
        'id' => 3,
        'student_name' => 'Faith Magayon',
        'counselor' => 'Ms. Grace Santos',
        'result' => 'Starting conversation...',
        'created_at' => now()->subDays(4)->toDateTimeString(),
        'summary' => 'Initial conversation; insufficient data to categorize.',
        'recommendations' => ['Collect more responses via chatbot'],
        'notes' => null,
      ],
    ];
    $routeId = request()->route('id');
    $report = (object) ($demo[$routeId] ?? $demo[1]);
  }

  $id    = $report->id ?? $report['id'] ?? null;
  $code  = 'DRP-' . now()->format('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
  $name  = $report->student->name ?? ($report->student_name ?? '—');
  $coun  = $report->counselor->name ?? ($report->counselor ?? '—');
  $date  = isset($report->created_at) ? \Carbon\Carbon::parse($report->created_at)->format('F d, Y · h:i A') : '—';
  $res   = $report->result ?? '—';

  $badge = match (strtolower($res)) {
    'mild anxiety'      => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
    'moderate anxiety'  => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
    'severe anxiety'    => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
    'normal'            => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    default             => 'bg-slate-100 text-slate-700',
  };
@endphp

<div class="max-w-5xl mx-auto space-y-6">

  {{-- Header --}}
  <div class="flex items-start justify-between gap-4">
    <div>
      <h2 class="text-2xl font-semibold tracking-tight text-slate-800">Diagnosis Report</h2>
      <p class="text-sm text-slate-500">Finalized diagnosis summary and recommended next steps.</p>
    </div>
    <a href="{{ route('admin.diagnosis-reports.index') }}"
       class="inline-flex items-center h-9 px-3 rounded-lg text-sm font-medium bg-white border border-slate-200 shadow-sm hover:bg-slate-50">
      ← Back to list
    </a>
  </div>

  {{-- Confidential note --}}
  <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 text-sm">
    🔐 <span class="font-medium">Confidential:</span> This information is restricted to counseling staff only.
  </div>

  {{-- Summary card --}}
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

        @if(!empty($report->notes ?? null))
          <div class="text-xs uppercase text-slate-500 mt-4">Notes</div>
          <div class="text-slate-800">{{ $report->notes }}</div>
        @endif
      </div>
    </div>
  </div>

  {{-- Summary / Recommendations --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 p-5">
      <h3 class="text-base font-semibold text-slate-800 mb-2">Summary</h3>
      <p class="text-slate-700">{{ $report->summary ?? '—' }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 p-5">
      <h3 class="text-base font-semibold text-slate-800 mb-2">Recommended Next Steps</h3>
      @php $recs = $report->recommendations ?? []; @endphp
      @if(is_array($recs) && count($recs))
        <ul class="list-disc pl-5 space-y-1 text-slate-700">
          @foreach($recs as $step)
            <li>{{ $step }}</li>
          @endforeach
        </ul>
      @else
        <p class="text-slate-700">—</p>
      @endif
    </div>
  </div>

  {{-- Footer actions --}}
  <div class="flex items-center justify-end gap-2">
    <button type="button"
            onclick="window.print()"
            class="inline-flex items-center h-9 px-3 rounded-lg text-sm font-medium bg-white border border-slate-200 shadow-sm hover:bg-slate-50">
      Print
    </button>
  </div>

</div>
@endsection
