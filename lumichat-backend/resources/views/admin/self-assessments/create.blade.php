@extends('layouts.app')
@section('title','Self-Assessment')

@section('content')
<div class="max-w-3xl mx-auto p-6 space-y-6">
  <h2 class="text-2xl font-semibold">Self-Assessment</h2>
  <p class="text-sm text-gray-600">This is not a diagnosis. Your responses may be shared with a counselor to support you.</p>

  <form method="POST" action="{{ route('self-assessment.store') }}" class="space-y-6">
    @csrf

    {{-- Mood --}}
    <div>
      <label class="block text-sm font-medium mb-1">How are you feeling right now?</label>
      <div class="flex flex-wrap gap-2">
        @foreach (['Happy','Sad','Anxious','Stressed','Angry','Lonely','Neutral'] as $m)
          <label class="inline-flex items-center gap-2 bg-gray-100 px-3 py-2 rounded-lg cursor-pointer">
            <input type="radio" name="mood" value="{{ $m }}" class="accent-indigo-600">
            <span>{{ $m }}</span>
          </label>
        @endforeach
      </div>
    </div>

    {{-- Likert 0..3 --}}
    @php
      $likert = [
        'concentration' => 'I find it hard to concentrate on my studies.',
        'anxiety'       => 'I feel nervous, anxious, or on edge.',
        'interest_loss' => 'I have lost interest in activities I used to enjoy.',
        'sleep_issues'  => 'I have trouble sleeping or sleep too much.',
        'hopelessness'  => 'I feel hopeless about the future.',
      ];
      $scale = [0=>'Never',1=>'Sometimes',2=>'Often',3=>'Always'];
    @endphp

    <div class="space-y-4">
      <h3 class="font-medium">In the past 2 weeks, how often have you felt the following?</h3>

      @foreach($likert as $name => $label)
        <div class="bg-white border rounded-xl p-4">
          <p class="text-sm mb-2">{{ $label }}</p>
          <div class="flex gap-4">
            @foreach($scale as $val => $txt)
              <label class="inline-flex items-center gap-2">
                <input required type="radio" name="{{ $name }}" value="{{ $val }}" class="accent-indigo-600">
                <span class="text-sm">{{ $txt }}</span>
              </label>
            @endforeach
          </div>
          @error($name) <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
      @endforeach
    </div>

    {{-- Lifestyle --}}
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Average sleep hours/night</label>
        <input type="number" name="sleep_hours" min="0" max="24" class="w-full rounded-lg border-gray-300" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Appetite</label>
        <select name="appetite" class="w-full rounded-lg border-gray-300">
          <option value="">Select…</option>
          <option>Good</option>
          <option>Poor</option>
          <option>Overeating</option>
        </select>
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Coping strategies (optional)</label>
      <input type="text" name="coping_mechanisms" placeholder="e.g., journaling, talking to a friend" class="w-full rounded-lg border-gray-300"/>
    </div>

    {{-- Risk --}}
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
      <label class="block text-sm font-medium mb-1">Have you had thoughts of harming yourself or others?</label>
      <div class="flex gap-4">
        @foreach($scale as $val => $txt)
          <label class="inline-flex items-center gap-2">
            <input required type="radio" name="self_harm_thoughts" value="{{ $val }}" class="accent-red-600">
            <span class="text-sm">{{ $txt }}</span>
          </label>
        @endforeach
      </div>
    </div>

    <div class="flex items-center gap-3">
      <button class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700">Submit</button>
      <a href="{{ route('self-assessment.history') }}" class="text-sm text-gray-600 hover:underline">View your history</a>
    </div>
  </form>
</div>
@endsection
