@extends('layouts.admin')
@section('title','Chatbot Sessions')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold tracking-tight text-slate-800">Chatbot Sessions</h2>
      <p class="text-sm text-slate-500">View conversation histories and emotional trends from chatbot sessions.</p>
    </div>

    <form method="GET" action="{{ route('admin.chatbot-sessions.index') }}" class="flex items-center gap-2">
      <select name="date"
              class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
        <option value="all"  @selected(($dateKey ?? 'all') === 'all')>All Dates</option>
        <option value="7d"   @selected(($dateKey ?? 'all') === '7d')>Last 7 days</option>
        <option value="30d"  @selected(($dateKey ?? 'all') === '30d')>Last 30 days</option>
        <option value="month"@selected(($dateKey ?? 'all') === 'month')>This month</option>
      </select>

      <div class="relative">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search student or session ID"
               class="w-72 bg-white border border-slate-200 rounded-lg pl-3 pr-10 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
               autocomplete="off" />
        <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
          <path d="M21 21l-4.3-4.3" stroke-width="2" stroke-linecap="round"></path>
        </svg>
      </div>

      @if($q || ($dateKey && $dateKey !== 'all'))
        <a href="{{ route('admin.chatbot-sessions.index') }}" class="text-sm text-slate-600 hover:underline">Reset</a>
      @endif
    </form>
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm table-auto">
        <thead class="bg-slate-200 text-slate-800 shadow-sm">
          <tr class="align-middle">
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Session ID</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Student Name</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Assessment Result</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Assessment Date</th>
            <th class="px-6 py-3 text-right font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
          @forelse ($sessions as $s)
            <tr class="hover:bg-slate-50/70 align-middle">
              {{-- Session code like LMC-2025-0042; fallback to #ID --}}
              @php
                $code = 'LMC-' . now()->format('Y') . '-' . str_pad($s->id, 4, '0', STR_PAD_LEFT);
              @endphp
              <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-900">{{ $code }}</td>

              <td class="px-6 py-4 whitespace-nowrap text-slate-800">{{ $s->user->name ?? '—' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-slate-800">{{ $s->topic_summary ?? '—' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-slate-800">{{ $s->created_at?->format('F d, Y') }}</td>

              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2">
                  <a href="{{ route('admin.chatbot-sessions.show', $s) }}"
                     class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white hover:bg-blue-700 active:scale-[.97] transition"
                     title="View">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z" />
                      <circle cx="12" cy="12" r="3" stroke-width="2" />
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-6 py-10 text-center">
                <div class="text-slate-500">No sessions found.</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($sessions->hasPages())
      <div class="px-6 py-4 bg-slate-50 border-top border-slate-200/70">
        {{ $sessions->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
