@extends('layouts.admin')
@section('title', 'Create Follow-up · Appointment #'.$appointment->id)

@section('content')
<div class="max-w-xl mx-auto p-6">
  <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="px-5 py-4 border-b border-slate-200">
      <h2 class="text-lg font-semibold text-slate-900">Create follow-up</h2>
      <div class="text-sm text-slate-600 mt-1">
        Student: <b>{{ $appointment->student_name }}</b>
        @if($appointment->counselor_name) · Counselor: <b>{{ $appointment->counselor_name }}</b> @endif
      </div>
    </div>

    <form method="POST" action="{{ route('admin.appointments.follow.store', $appointment->id) }}" class="p-5 space-y-5">
      @csrf
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Date</label>
          <input type="date" name="date" value="{{ $suggest->toDateString() }}"
                 class="w-full h-11 bg-white border border-slate-200 rounded-xl px-3 text-sm focus:ring-2 focus:ring-indigo-500" required>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Time</label>
          <input type="time" name="time" value="{{ $suggest->format('H:i') }}"
                 class="w-full h-11 bg-white border border-slate-200 rounded-xl px-3 text-sm focus:ring-2 focus:ring-indigo-500" required>
        </div>
      </div>

      {{-- Optional: allow choosing counselor; preselect original if present --}}
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Counselor (optional)</label>
        <select name="counselor_id"
                class="w-full h-11 bg-white border border-slate-200 rounded-xl px-3 text-sm focus:ring-2 focus:ring-indigo-500">
          <option value="">— Auto (use original if free; else pending) —</option>
          @foreach($counselors as $c)
            <option value="{{ $c->id }}" @selected($appointment->counselor_id == $c->id)>{{ $c->name }}</option>
          @endforeach
        </select>
        <p class="mt-1 text-[12px] text-slate-500">
          If the selected counselor isn’t free at that time, the follow-up will be created as <b>Pending</b> without a counselor.
        </p>
      </div>

      <div class="flex items-center justify-end gap-2">
        <a href="{{ route('admin.appointments.show', $appointment->id) }}"
           class="inline-flex items-center h-10 rounded-xl bg-slate-100 text-slate-700 px-4 hover:bg-slate-200">Cancel</a>
        <button class="inline-flex items-center h-10 rounded-xl bg-indigo-600 text-white px-5 hover:bg-indigo-700">
          Create follow-up
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
