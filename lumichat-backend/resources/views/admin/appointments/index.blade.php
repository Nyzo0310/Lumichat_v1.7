@extends('layouts.admin')
@section('title','Admin · Appointments')

@php
  use Carbon\Carbon;

  $status = $status ?? request('status', 'all');
  $period = $period ?? request('period', 'all');
  $q      = $q      ?? request('q', '');

  $statusOptions = [
    'all'       => 'All Statuses',
    'pending'   => 'Pending',
    'confirmed' => 'Confirmed',
    'completed' => 'Completed',
    'canceled'  => 'Canceled',
  ];
  $periodOptions = [
    'all'        => 'All Dates',
    'upcoming'   => 'Upcoming',
    'today'      => 'Today',
    'this_week'  => 'This Week',
    'this_month' => 'This Month',
    'past'       => 'Past',
  ];
@endphp

@section('content')
<div class="max-w-7xl mx-auto p-6">
  <h1 class="text-2xl font-semibold text-gray-900 mb-6">Appointments</h1>

  {{-- Filters --}}
  <form method="GET" action="{{ route('admin.appointments.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-5">
    {{-- Status --}}
    <div>
      <select name="status"
              class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        @foreach ($statusOptions as $value => $label)
          <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    {{-- Period --}}
    <div>
      <select name="period"
              class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        @foreach ($periodOptions as $value => $label)
          <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    {{-- Search --}}
    <div class="md:col-span-2 flex">
      <input type="text" name="q" value="{{ $q }}" placeholder="Search counselor"
             class="flex-1 rounded-l-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" />
      <button class="rounded-r-lg bg-gray-900 px-4 text-white hover:bg-gray-800">
        Search
      </button>
    </div>
  </form>

  {{-- Table --}}
  <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-200 text-slate-800 shadow-sm">
        <tr>
          <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">ID</th>
          <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Student</th>
          <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Counselor</th>
          <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Date & Time</th>
          <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Status</th>
          <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
      @forelse ($appointments as $row)
        @php
          $dt  = \Carbon\Carbon::parse($row->scheduled_at);
          $now = \Carbon\Carbon::now();
          $sub = $now->isBefore($dt)
              ? 'Starts in '.$dt->diffForHumans($now, ['parts'=>2, 'short'=>true, 'syntax'=>\Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW])
              : 'Started '.$dt->diffForHumans($now, ['parts'=>2, 'short'=>true, 'syntax'=>\Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW]);

          $badgeMap = [
            'pending'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
            'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
            'canceled'  => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
            'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
          ];
          $dotMap = [
            'pending'   => 'bg-amber-500',
            'confirmed' => 'bg-blue-500',
            'canceled'  => 'bg-rose-500',
            'completed' => 'bg-emerald-500',
          ];
          $cls = $badgeMap[$row->status] ?? 'bg-gray-100 text-gray-700';
          $dot = $dotMap[$row->status] ?? 'bg-gray-400';
        @endphp

        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30">
          <td class="px-4 py-3">{{ $row->id }}</td>
          <td class="px-4 py-3">{{ $row->student_name }}</td>
          <td class="px-4 py-3">{{ $row->counselor_name }}</td>
          <td class="px-4 py-3">
            <div class="leading-tight">
              <div>{{ $dt->format('M d, Y · g:i A') }}</div>
              <div class="text-xs text-gray-500">{{ $sub }}</div>
            </div>
          </td>
          <td class="px-4 py-3">
            <span id="badge-{{ $row->id }}"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cls }}">
              <span class="inline-block size-2 rounded-full {{ $dot }} mr-2 align-middle"></span>
              {{ ucfirst($row->status) }}
            </span>
          </td>
          <td class="px-4 py-3 text-right">
            <a href="{{ route('admin.appointments.show', $row->id) }}"
              class="px-3 py-1.5 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
              View
            </a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-300">
            No appointments found.
          </td>
        </tr>
      @endforelse
    </tbody>
    </table>
  </div>

  <div class="mt-4">
    {{ $appointments->withQueryString()->links() }}
  </div>
</div>
@endsection
