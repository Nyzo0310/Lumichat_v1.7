@extends('layouts.admin')
@section('title','Chatbot Sessions')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold tracking-tight text-slate-800">Chatbot Sessions</h2>
      <p class="text-sm text-slate-500">View conversation histories and emotional trends from chatbot sessions.</p>
    </div>

    {{-- Controls row --}}
    <form method="GET" action="{{ route('admin.chatbot-sessions.index') }}" class="flex items-center gap-2 no-print">
       {{-- PRINT (after All Dates) --}}
      <button type="button"
              class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-slate-800 hover:bg-slate-50"
              onclick="printNode('#listPrintable', 'Chatbot Sessions')">
        Print
      </button>
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

  {{-- PRINTABLE AREA (give the table card an id) --}}
  <div id="listPrintable" class="bg-white rounded-2xl shadow-sm border border-slate-200/70 overflow-hidden print-area">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm table-auto">
        <thead class="bg-slate-200 text-slate-800 shadow-sm">
          <tr class="align-middle">
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Session ID</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Student Name</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Initial Result</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Initial Date</th>
            <th class="px-6 py-3 text-right font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap no-print">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
          @forelse ($sessions as $s)
            <tr class="hover:bg-slate-50/70 align-middle">
              @php
                $code = 'LMC-' . now()->format('Y') . '-' . str_pad($s->id, 4, '0', STR_PAD_LEFT);
              @endphp
              <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-900">{{ $code }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-slate-800">{{ $s->user->name ?? '—' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-slate-800">{{ $s->topic_summary ?? '—' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-slate-800">{{ $s->created_at?->format('F d, Y') }}</td>

              <td class="px-6 py-4 no-print">
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
      <div class="px-6 py-4 bg-slate-50 border-top border-slate-200/70 no-print">
        {{ $sessions->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
@push('scripts')
<script>
/**
 * Print a specific DOM node by cloning it into a hidden iframe.
 * Works well with popup blockers.
 */
window.printNode = function(selector, title = document.title) {
  const node = document.querySelector(selector) || document.body;

  // Build a hidden iframe
  const iframe = document.createElement('iframe');
  iframe.style.position = 'fixed';
  iframe.style.right = '0';
  iframe.style.bottom = '0';
  iframe.style.width = '0';
  iframe.style.height = '0';
  iframe.style.border = '0';
  document.body.appendChild(iframe);

  const doc = iframe.contentWindow.document;

  // Reuse page styles so the printout looks the same
  const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
    .map(n => n.outerHTML).join('\n');

  doc.open();
  doc.write(`
    <html>
      <head>
        <meta charset="utf-8">
        <title>${title}</title>
        ${styles}
        <style>
          @page { margin: 1.2cm; }
          @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .print-area { box-shadow: none !important; border: 0 !important; }
          }
        </style>
      </head>
      <body>${node.outerHTML}</body>
    </html>
  `);
  doc.close();

  iframe.onload = () => {
    // Print and clean up
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
    setTimeout(() => document.body.removeChild(iframe), 200);
  };
};
</script>
@endpush

