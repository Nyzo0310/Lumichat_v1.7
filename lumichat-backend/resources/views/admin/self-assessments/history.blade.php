@extends('layouts.app')
@section('title','Self-Assessment History')

@section('content')
<div class="max-w-5xl mx-auto p-6 space-y-6">
  <h2 class="text-2xl font-semibold">Your Self-Assessments</h2>

  @if(session('success'))
    <div class="rounded-xl bg-green-50 border border-green-200 text-green-700 px-4 py-3">{{ session('success') }}</div>
  @endif

  <div class="bg-white rounded-xl border overflow-hidden">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="text-left p-3">Date</th>
          <th class="text-left p-3">Mood</th>
          <th class="text-left p-3">Score</th>
          <th class="text-left p-3">Risk</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $it)
          <tr class="border-t">
            <td class="p-3">{{ $it->created_at->format('Y-m-d H:i') }}</td>
            <td class="p-3">{{ $it->mood ?? '—' }}</td>
            <td class="p-3">{{ $it->wellbeing_score }}</td>
            <td class="p-3">
              @if($it->red_flag)
                <span class="text-red-600 font-medium">Needs attention</span>
              @else
                <span class="text-gray-600">Normal</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="p-4 text-center text-gray-500">No records yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div>{{ $items->links() }}</div>
</div>
@endsection
