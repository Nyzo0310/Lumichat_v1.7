@extends('layouts.admin')
@section('title','Self-Assessments')

@section('content')
<div class="space-y-6">
  {{-- Header --}}
  <div class="flex items-center justify-between">
      <div>
          <h2 class="text-2xl font-semibold text-gray-800">Self-Assessments</h2>
          <p class="text-sm text-gray-500">Overview of all student-submitted assessments and risk flags</p>
      </div>

      <form method="GET" class="flex items-center gap-2">
          <input type="text" name="q" value="{{ $q ?? '' }}" 
                placeholder="Search student..." 
                class="rounded-lg border-gray-300">
          <select name="risk" class="rounded-lg border-gray-300">
              <option value="">All</option>
              <option value="red" @selected($risk==='red')>Red-flag only</option>
              <option value="safe" @selected($risk==='safe')>Safe only</option>
          </select>
          <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Filter</button>
      </form>
  </div>
</div>

  {{-- Table --}}
<div class="bg-white border rounded-xl overflow-hidden mt-8">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-200 text-slate-800 shadow-sm">
        <tr>
          <th class="text-left p-3">Date</th>
          <th class="text-left p-3">Student</th>
          <th class="text-left p-3">Mood</th>
          <th class="text-left p-3">Score</th>
          <th class="text-left p-3">Risk</th>
          <th class="text-left p-3">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $it)
          <tr class="border-t {{ $it->red_flag ? 'bg-red-50' : '' }}">
            <td class="p-3">{{ $it->created_at->format('Y-m-d H:i') }}</td>
            <td class="p-3">
              {{ optional($it->student)->first_name }} {{ optional($it->student)->last_name }}
              <div class="text-xs text-gray-500">{{ optional($it->student)->email }}</div>
            </td>
            <td class="p-3">{{ $it->mood ?? '—' }}</td>
            <td class="p-3">{{ $it->wellbeing_score }}</td>
            <td class="p-3">
              @if($it->red_flag)
                <span class="text-red-600 font-medium">Needs attention</span>
              @else
                <span class="text-gray-600">Normal</span>
              @endif
            </td>
            <td class="p-3">
              <a class="text-indigo-600 hover:underline" href="{{ route('admin.self-assessments.show',$it->id) }}">View</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="p-4 text-center text-gray-500">No records found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div>
    {{ $items->links() }}
  </div>
</div>
@endsection
