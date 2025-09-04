@extends('layouts.app')
@section('title','Appointment History')

@section('content')
<div class="max-w-6xl mx-auto p-6">

  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Appointment History</h2>
    <a href="{{ route('appointment.index') }}"
       class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
      <span class="font-medium">Book New</span>
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
    </a>
  </div>

  @isset($totalCount)
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
      Showing <span class="font-medium">{{ $appointments->firstItem() ?? 0 }}</span>–
      <span class="font-medium">{{ $appointments->lastItem() ?? 0 }}</span>
      of <span class="font-medium">{{ $totalCount }}</span> appointments
    </p>
  @endisset

  <form method="GET" action="{{ route('appointment.history') }}" class="flex flex-col gap-3 md:flex-row md:items-center mb-3">
    <div class="w-full md:w-64">
      <select name="status" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
        @php
          $statuses = ['all'=>'All Appointment','pending'=>'Pending','confirmed'=>'Confirmed','canceled'=>'Canceled','completed'=>'Completed'];
        @endphp
        @foreach ($statuses as $val=>$label)
          <option value="{{ $val }}" @selected(($status ?? 'all')===$val)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <div class="w-full md:w-64">
      <select name="period" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
        @php
          $periods = ['all'=>'All Dates','upcoming'=>'Upcoming','today'=>'Today','this_week'=>'This Week','this_month'=>'This Month','past'=>'Past'];
        @endphp
        @foreach ($periods as $val=>$label)
          <option value="{{ $val }}" @selected(($period ?? 'all')===$val)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <div class="flex w-full md:flex-1">
      <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Search counselor"
             class="flex-1 rounded-l-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"/>
      <button class="rounded-r-lg bg-slate-900 text-white px-4 dark:bg-indigo-600 dark:hover:bg-indigo-700">Search</button>
    </div>
  </form>

  @php
    $chips = [];
    if (!empty($status) && $status !== 'all') $chips[] = 'Status: '.ucfirst($status);
    if (!empty($period) && $period !== 'all') $chips[] = 'Period: '.Str::headline($period);
    if (!empty($q)) $chips[] = 'Search: '.$q;
  @endphp
  @if (count($chips))
    <div class="flex items-center gap-2 mb-3">
      <span class="text-sm text-gray-500 dark:text-gray-400">Filters:</span>
      @foreach ($chips as $c)
        <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $c }}</span>
      @endforeach
      <a href="{{ route('appointment.history') }}" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400 ml-2">Reset</a>
    </div>
  @endif

  <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-200">
        <tr>
          <th class="px-4 py-3 text-left">ID</th>
          <th class="px-4 py-3 text-left">Student Name</th>
          <th class="px-4 py-3 text-left">Counselor Name</th>
          <th class="px-4 py-3 text-left">Date &amp; Time</th>
          <th class="px-4 py-3 text-left">Status</th>
          <th class="px-4 py-3 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
        @forelse ($appointments as $row)
          @php
            $now   = \Carbon\Carbon::now();
            $start = \Carbon\Carbon::parse($row->scheduled_at);
            $mins  = $now->diffInMinutes($start, false);
            $abs   = abs($mins);
            $d     = intdiv($abs, 1440); $r=$abs%1440; $h=intdiv($r,60); $m=$r%60;
            $parts = [];
            if ($d) $parts[] = "{$d}d";
            if ($h) $parts[] = "{$h}h";
            if (!$d && $m) $parts[] = "{$m}m";
            $countdown = $mins === 0 ? 'Starting now'
                        : ($mins > 0 ? ('Starts in '.implode(' ', $parts)) : (implode(' ', $parts).' ago'));

            $styles = [
              'pending'   => ['chip'=>'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200','dot'=>'bg-amber-500','pulse'=>true],
              'confirmed' => ['chip'=>'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200','dot'=>'bg-blue-500','pulse'=>false],
              'canceled'  => ['chip'=>'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200','dot'=>'bg-rose-500','pulse'=>false],
              'completed' => ['chip'=>'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200','dot'=>'bg-emerald-500','pulse'=>false],
            ];
            $s = $styles[$row->status] ?? ['chip'=>'bg-gray-100 text-gray-700','dot'=>'bg-gray-400','pulse'=>false];
          @endphp

          <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30">
            <td class="px-4 py-3">{{ $row->id }}</td>
            <td class="px-4 py-3">{{ auth()->user()->name }}</td>
            <td class="px-4 py-3">{{ $row->counselor_name }}</td>
            <td class="px-4 py-3">
              <div>{{ $start->format('M d, Y · g:i A') }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $countdown }}</div>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $s['chip'] }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }} {{ $s['pulse'] ? 'animate-pulse' : '' }}"></span>
                {{ ucfirst($row->status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <a href="{{ route('appointment.view', $row->id) }}"
                 class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-1.5 text-white hover:bg-indigo-700">
                View
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-300">No appointments found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    {{ $appointments->links() }}
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Success toast from redirects (booking/cancel)
  const successMsg = @json(session('status'));
  if (successMsg) {
    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: successMsg,
      timer: 2200,
      showConfirmButton: false
    });
  }

  // Generic errors
  const pageErrors = @json($errors->all());
  if (Array.isArray(pageErrors) && pageErrors.length) {
    const html = '<ul style="text-align:left;margin:0;padding-left:1rem">' +
                 pageErrors.map(i => `<li>• ${i}</li>`).join('') + '</ul>';
    Swal.fire({ icon: 'error', title: 'Unable to proceed', html });
  }
});
</script>
@endpush
