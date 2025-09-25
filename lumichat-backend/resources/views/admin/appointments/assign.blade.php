@extends('layouts.admin')
@section('title', 'Assign Counselor · Appointment #'.$appointment->id)

@section('content')
<div class="max-w-xl mx-auto p-6">
  <h2 class="text-lg font-semibold mb-4">Assign Counselor</h2>

  <form method="POST" action="{{ route('admin.appointments.assign', $appointment->id) }}" class="space-y-4">
    @csrf
    <div>
      <label class="block text-sm font-medium mb-1">Counselor</label>
      <select name="counselor_id" class="w-full border rounded-lg h-10 px-3">
        <option value="" disabled selected>Choose counselor…</option>
        @foreach($counselors as $c)
          <option value="{{ $c->id }}">{{ $c->name }} @if($c->email) ({{ $c->email }}) @endif</option>
        @endforeach
      </select>
      @error('counselor_id') <div class="text-rose-600 text-sm mt-1">• {{ $message }}</div> @enderror
    </div>

    <div class="flex gap-2">
      <a href="{{ route('admin.appointments.show', $appointment->id) }}"
         class="px-4 py-2 rounded-lg bg-gray-100">Cancel</a>
      <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white">Assign</button>
    </div>
  </form>
</div>
@endsection
