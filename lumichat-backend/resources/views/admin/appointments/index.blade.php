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

  {{-- Header + Print --}}
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-semibold text-gray-900">Appointments</h1>

    <button type="button"
            class="no-print inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-slate-800 hover:bg-slate-50"
            onclick="printNode('#appointmentsPrintable', `Appointments — {{ ucfirst($status) }} / {{ str_replace('_',' ',ucfirst($period)) }}`)">
      Print
    </button>
  </div>

  {{-- Filters (not printed) --}}
  <form method="GET" action="{{ route('admin.appointments.index') }}"
        class="no-print grid grid-cols-1 md:grid-cols-4 gap-3 mb-5">
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

  {{-- Printable region: title + table only --}}
  <div id="appointmentsPrintable">
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-200 text-slate-800 shadow-sm">
          <tr>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">ID</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Student</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Counselor</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Date & Time</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Booked On</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Status</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap no-print">Actions</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          @forelse ($appointments as $row)
            @php
              $dt  = \Carbon\Carbon::parse($row->scheduled_at);
              $bookedAt = $row->booked_at ?? $row->created_at ?? null;

              $badgeMap = [
                'pending'   => 'bg-amber-100 text-amber-800',
                'confirmed' => 'bg-blue-100 text-blue-800',
                'canceled'  => 'bg-rose-100 text-rose-800',
                'completed' => 'bg-emerald-100 text-emerald-800',
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
              <td class="px-4 py-3 whitespace-nowrap">{{ $row->student_name }}</td>
              <td class="px-4 py-3 whitespace-nowrap">{{ $row->counselor_name }}</td>

              {{-- Scheduled --}}
              <td class="px-4 py-3">
                <div class="leading-tight">
                  <div>{{ $dt->format('M d, Y · g:i A') }}</div>
                </div>
              </td>

              {{-- Booked On --}}
              <td class="px-4 py-3">
                @if ($bookedAt)
                  @php $booked = \Carbon\Carbon::parse($bookedAt); @endphp
                  <div class="leading-tight">
                    <div>{{ $booked->format('M d, Y · g:i A') }}</div>
                  </div>
                @else
                  —
                @endif
              </td>

              {{-- Status --}}
              <td class="px-4 py-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cls }}">
                  <span class="inline-block size-2 rounded-full {{ $dot }} mr-2 align-middle"></span>
                  {{ ucfirst($row->status) }}
                </span>
              </td>

              {{-- Actions (hidden on print) --}}
              <td class="px-4 py-3 text-right no-print">
                <a href="{{ route('admin.appointments.show', $row->id) }}"
                   class="px-3 py-1.5 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                  View
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-10 text-center text-gray-500 dark:text-gray-300">
                No appointments found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Pagination (not printed) --}}
  <div class="mt-4 no-print">
    {{ $appointments->withQueryString()->links() }}
  </div>
</div>
@endsection

@push('styles')
<style>
  @media print {
    .no-print { display: none !important; }
    body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    /* Optional: tighter page margins for the list */
    @page { size: A4; margin: 12mm 14mm; }
  }
   /* keep every table cell on one line when printing */
  @media print {
    #appointmentsPrintable th,
    #appointmentsPrintable td,
    #appointmentsPrintable .leading-tight {
      white-space: nowrap !important;
    }
    #appointmentsPrintable table { font-size: 12px; } /* optional: shrink a bit */
  }
</style>
@endpush

@push('scripts')
<script>
/**
 * Print only a specific node of the page, with app styles copied in.
 * Usage: printNode('#appointmentsPrintable', 'Appointments — Filtered');
 */
function printNode(selector, title) {
  const node = document.querySelector(selector);
  if (!node) {
    console.warn('printNode: selector not found →', selector);
    return;
  }

  const iframe = document.createElement('iframe');
  Object.assign(iframe.style, {
    position: 'fixed', right: '0', bottom: '0',
    width: '0', height: '0', border: '0', visibility: 'hidden'
  });
  document.body.appendChild(iframe);

  const doc = iframe.contentDocument || iframe.contentWindow.document;

  // copy existing stylesheets and inline styles so Tailwind/your CSS is available
  const styleHTML = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
    .map(el => el.outerHTML)
    .join('\n');

  doc.open();
  doc.write(`
    <!doctype html>
    <html>
      <head>
        <meta charset="utf-8">
        <base href="${document.baseURI}">
        <title>${title ? String(title) : document.title}</title>
        ${styleHTML}
        <style>
          @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 14mm; }
          }
          html, body { background: #fff; margin: 0; padding: 0; }
          .print-wrap { padding: 0; }
        </style>
      </head>
      <body>
        <div class="print-wrap">${node.outerHTML}</div>
      </body>
    </html>
  `);
  doc.close();

  const win = iframe.contentWindow;
  setTimeout(() => {
    win.focus();
    win.print();
    setTimeout(() => document.body.removeChild(iframe), 100);
  }, 200);
}
</script>
@endpush
