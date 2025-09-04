@extends('layouts.admin')
@section('title','Self-Assessment Details')

@section('content')
<div class="space-y-6 max-w-4xl">
  <a href="{{ route('admin.self-assessments.index') }}" class="text-indigo-600 hover:underline">← Back</a>

  <div class="bg-white border rounded-xl p-6 space-y-4">
    <h3 class="text-xl font-semibold">Assessment #{{ $assessment->id }}</h3>
    <div class="grid md:grid-cols-2 gap-4 text-sm">
      <div><span class="text-gray-500">Date:</span> {{ $assessment->created_at->format('Y-m-d H:i') }}</div>
      <div><span class="text-gray-500">Student:</span> {{ optional($assessment->student)->first_name }} {{ optional($assessment->student)->last_name }}</div>
      <div><span class="text-gray-500">Mood:</span> {{ $assessment->mood ?? '—' }}</div>
      <div><span class="text-gray-500">Score:</span> {{ $assessment->wellbeing_score }}</div>
      <div>
        <span class="text-gray-500">Risk:</span>
        @if($assessment->red_flag)
          <span class="text-red-600 font-medium">Needs attention</span>
        @else
          <span class="text-gray-700">Normal</span>
        @endif
      </div>
      <div><span class="text-gray-500">Sleep hours:</span> {{ $assessment->sleep_hours ?? '—' }}</div>
      <div><span class="text-gray-500">Appetite:</span> {{ $assessment->appetite ?? '—' }}</div>
      <div><span class="text-gray-500">Coping:</span> {{ $assessment->coping_mechanisms ?? '—' }}</div>
    </div>

    <div class="border-t pt-4 text-sm">
      <h4 class="font-medium mb-2">Symptoms (0=Never · 3=Always)</h4>
      <ul class="grid md:grid-cols-2 gap-2">
        <li>Concentration: {{ $assessment->concentration }}</li>
        <li>Anxiety: {{ $assessment->anxiety }}</li>
        <li>Interest loss: {{ $assessment->interest_loss }}</li>
        <li>Sleep issues: {{ $assessment->sleep_issues }}</li>
        <li>Hopelessness: {{ $assessment->hopelessness }}</li>
        <li>Self-harm thoughts: {{ $assessment->self_harm_thoughts }}</li>
      </ul>
    </div>
  </div>

  <div class="bg-white border rounded-xl p-6">
    @if(session('success'))
      <div class="rounded-xl bg-green-50 border border-green-200 text-green-700 px-4 py-3 mb-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.self-assessments.feedback',$assessment->id) }}" class="space-y-3">
      @csrf
      <label class="block text-sm font-medium">Counselor Feedback / Notes</label>
      <textarea name="counselor_feedback" rows="4" class="w-full rounded-lg border-gray-300">{{ old('counselor_feedback',$assessment->counselor_feedback) }}</textarea>
      <button class="px-5 py-2.5 rounded-xl bg-slate-800 text-white">Save Feedback</button>
    </form>
  </div>
</div>
@endsection
