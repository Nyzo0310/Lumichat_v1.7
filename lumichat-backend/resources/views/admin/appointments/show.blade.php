@extends('layouts.admin')
@section('title', 'Admin · Appointment #'.$appointment->id)

@section('content')
@php
  use Carbon\Carbon;

  $dt  = Carbon::parse($appointment->scheduled_at);
  $now = Carbon::now();

  $when = $now->isBefore($dt)
      ? 'Starts in '.$dt->diffForHumans($now, ['parts'=>2,'short'=>true,'syntax'=>Carbon::DIFF_RELATIVE_TO_NOW])
      : 'Started '.$dt->diffForHumans($now, ['parts'=>2,'short'=>true,'syntax'=>Carbon::DIFF_RELATIVE_TO_NOW]);

  $hasStarted = $now->gte($dt);                        // has the start time passed?

  $canConfirm = ($appointment->status === 'pending');
  $canDone    = ($appointment->status === 'confirmed') && $hasStarted;

  $doneTitle = $appointment->status !== 'confirmed'
      ? 'You can only mark confirmed appointments as done'
      : ($hasStarted ? 'Mark as completed' : 'You can only mark as done after the scheduled start time');

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
  $cls = $badgeMap[$appointment->status] ?? 'bg-gray-100 text-gray-700';
  $dot = $dotMap[$appointment->status] ?? 'bg-gray-400';

  $bookedAt = $appointment->created_at ? Carbon::parse($appointment->created_at) : null;
@endphp





<div class="max-w-5xl mx-auto p-6" id="appointmentPrintable"> 
  <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 print-container">

    {{-- Header with status + actions (no back link) --}}
    <div class="px-6 pt-6 pb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
      <div>
        <div class="flex items-center gap-3">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Appointment #{{ $appointment->id }}
          </h2>
          <span class="print-badge inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $cls }}">
  <span class="inline-block w-1.5 h-1.5 rounded-full {{ $dot }} mr-2 align-middle"></span>
  {{ ucfirst($appointment->status) }}
</span>

        </div>
        <div class="mt-1 text-sm text-gray-500 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 8v5l3.5 3.5 1.5-1.5-3-3V8z"/><path d="M12 22a10 10 0 110-20 10 10 0 010 20zm0-2a8 8 0 100-16 8 8 0 010 16z"/>
          </svg>
          {{ $when }}
        </div>
      </div>

<div class="flex items-center gap-2 no-print">



  {{-- Confirm (its own form) --}}
  <form method="POST"
        action="{{ route('admin.appointments.status', $appointment->id) }}"
        onsubmit="return askAction(event, this, 'confirm')">
    @csrf @method('PATCH')
      {{-- Print --}}
  <button type="button"
          class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-slate-800 hover:bg-slate-50"
          onclick="printNode('#appointmentPrintable', 'Appointment #{{ $appointment->id }}')">
    Print
  </button>
    <input type="hidden" name="action" value="confirm">
    <button type="submit"
            title="{{ $canConfirm ? 'Confirm this appointment' : 'Only pending appointments can be confirmed' }}"
            class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            {{ $canConfirm ? '' : 'disabled' }}>
      Confirm 
    </button>
  </form>

  {{-- Done (its own form) --}}
  <form method="POST"
        action="{{ route('admin.appointments.status', $appointment->id) }}"
        onsubmit="return askAction(event, this, 'done')">
    @csrf @method('PATCH')
    <input type="hidden" name="action" value="done">
    <button type="submit"
            title="{{ $doneTitle }}"
            class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
      Done
    </button>
  </form>

</div>
    </div>

{{-- Meta --}}
<div class="px-6 pb-2">
  <div class="meta-grid grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
    {{-- Left: Student & Counselor --}}
    <div class="meta-left md:col-span-6 space-y-6">
      <!-- Student -->
      <div>
        <div class="text-xs uppercase tracking-wide text-gray-500">Student</div>
        <div class="mt-1 font-medium text-gray-900 dark:text-gray-100">
          {{ $appointment->student_name }}
        </div>
        @if(!empty($appointment->student_email))
          <div class="text-sm text-gray-600 dark:text-gray-300">
            {{ $appointment->student_email }}
          </div>
        @endif
      </div>

      <!-- Counselor -->
      <div>
        <div class="text-xs uppercase tracking-wide text-gray-500">Counselor</div>
        <div class="mt-1 font-medium text-gray-900 dark:text-gray-100">
          {{ $appointment->counselor_name }}
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-300">
          {{ $appointment->counselor_email }}
          @if(!empty($appointment->counselor_phone)) · {{ $appointment->counselor_phone }} @endif
        </div>
      </div>
    </div>

    {{-- Right: Booked On & Scheduled --}}
    <div class="meta-right md:col-span-6 grid grid-cols-1 gap-6">
      <div>
        <div class="text-xs uppercase tracking-wide text-gray-500">Booked On</div>
        <div class="mt-1 font-medium text-gray-900 dark:text-gray-100">
          {{ $bookedAt ? $bookedAt->format('l, M d, Y · g:i A') : '—' }}
        </div>
      </div>

      <div>
        <div class="text-xs uppercase tracking-wide text-gray-500">Scheduled</div>
        <div class="mt-1 font-medium text-gray-900 dark:text-gray-100">
          {{ $dt->format('l, M d, Y · g:i A') }}
        </div>
      </div>
    </div>
  </div>
</div>
<div class="rounded-xl bg-indigo-50/40 dark:bg-indigo-900/20 card-muted">
  ...
</div>

  {{-- Final Diagnosis (saved to tbl_diagnosis_reports) --}}
<div class="px-6 pb-6 mt-2">
  <div class="rounded-xl bg-indigo-50/40 dark:bg-indigo-900/20">
    <div class="flex items-center justify-between px-4 py-3">
      <div class="text-xs font-semibold tracking-wide uppercase text-gray-700 dark:text-gray-200">
        Final Diagnosis (Report)
      </div>
      @isset($latestReport)
        <div class="text-xs text-gray-500">
          Last saved {{ \Carbon\Carbon::parse($latestReport->updated_at)->format('M d, Y g:i A') }}
        </div>
      @endisset
    </div>

    <div class="px-4 pb-4">
      @if($appointment->status === 'completed')
        <form method="POST" action="{{ route('admin.appointments.saveReport', $appointment->id) }}" class="space-y-4">
          @csrf
          @method('PATCH')

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Final Diagnosis <span class="text-rose-600">*</span></label>
            <textarea name="diagnosis" rows="4" required
              class="w-full rounded-lg border-0 ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-gray-900/40 p-3"
              placeholder="Write the final diagnosis...">{{ old('diagnosis') }}</textarea>
            @error('diagnosis') <div class="text-sm text-rose-600 mt-1">• {{ $message }}</div> @enderror
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Note for this report (optional)</label>
            <textarea name="final_note" rows="3"
              class="w-full rounded-lg border-0 ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-gray-900/40 p-3"
              placeholder="Additional note...">{{ old('final_note') }}</textarea>
            @error('final_note') <div class="text-sm text-rose-600 mt-1">• {{ $message }}</div> @enderror
          </div>

          <div class="flex items-center justify-end gap-2 no-print">
            <button type="submit"
              class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
              Save Diagnosis
            </button>
          </div>
        </form>
      @else
        <div class="bg-white dark:bg-gray-900/40 rounded-lg p-3">
          <textarea rows="3" class="w-full rounded-md border-0 ring-0 bg-transparent" disabled
            placeholder="Available after the appointment is marked Completed."></textarea>
          <div class="text-xs text-slate-500 mt-2">You can add the final diagnosis once this appointment is <b>Completed</b>.</div>
        </div>
      @endif
    </div>
  </div>
</div>


    {{-- Footer: Close only (actions + print are already on top) --}}
    <div class="px-6 pb-6 border-t border-gray-100 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <a href="{{ route('admin.appointments.index') }}"
           class="px-4 py-2 rounded-lg bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-100 no-print">
          Close
        </a>
        <div class="text-xs text-gray-500">
          Status: <span class="font-medium">{{ ucfirst($appointment->status) }}</span>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  function askAction(e, form, action) {
    e.preventDefault();

    const cfg = {
      confirm: {
        title: 'Confirm Appointment?',
        text: 'Are you sure you want to confirm this appointment?',
        icon: 'question',
        confirmButtonColor: '#2563eb',
      },
      done: {
        title: 'Mark as Completed?',
        text: 'This will mark the appointment as done.',
        icon: 'success',
        confirmButtonColor: '#059669',
      }
    }[action] || {
      title: 'Are you sure?',
      text: '',
      icon: 'info',
      confirmButtonColor: '#2563eb'
    };

    Swal.fire({
      title: cfg.title,
      text: cfg.text,
      icon: cfg.icon,
      showCancelButton: true,
      confirmButtonText: 'Yes, proceed',
      cancelButtonText: 'No, keep it',
      confirmButtonColor: cfg.confirmButtonColor,
      cancelButtonColor: '#6b7280',
      reverseButtons: true,
      focusCancel: true
    }).then(res => {
      if (res.isConfirmed) form.submit();
    });
    return false;
  }

  @if (session('swal'))
    Swal.fire(@json(session('swal')));
  @endif
</script>
@endpush
@push('styles')
<style>
@media print {
  /* Hide obvious UI controls */
  .no-print { display: none !important; }

  /* Keep your card clean on paper */
  .print-container {
    box-shadow: none !important;
    border: 0 !important;
    background: #fff !important;
  }

  /* Keep the two-column layout */
  .meta-grid { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 16px !important; }
  .meta-left  { grid-column: 1 / span 1 !important; }
  .meta-right { grid-column: 2 / span 1 !important; }

  /* Status chip readable in print */
  .print-badge {
    background: transparent !important;
    color: #111 !important;
    border: 1px solid #999 !important;
    padding: 2px 8px !important;
    border-radius: 9999px !important;
  }

  /* Diagnosis card tint softened */
  .card-muted { background: transparent !important; border: 1px solid #e5e7eb !important; }

  /* (Optional) margins */
  @page { size: A4; margin: 12mm 14mm; }
}
</style>
@endpush
<script>
  function printNode(selector, title) {
    const node = document.querySelector(selector);
    if (!node) {
      console.warn('printNode: selector not found →', selector);
      return;
    }

    // Hidden iframe for clean, isolated print
    const iframe = document.createElement('iframe');
    Object.assign(iframe.style, {
      position: 'fixed', right: '0', bottom: '0',
      width: '0', height: '0', border: '0', visibility: 'hidden'
    });
    document.body.appendChild(iframe);

    const doc = iframe.contentDocument || iframe.contentWindow.document;

    // Copy page <link rel="stylesheet"> and <style> so Tailwind/app CSS is available
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
              @page { margin: 14mm; } /* adjust if you want tighter/looser margins */
            }
            html, body { background: #fff; margin: 0; padding: 0; }
            .print-wrap { padding: 24px; } /* small inner padding for aesthetics */
          </style>
        </head>
        <body>
          <div class="print-wrap">${node.outerHTML}</div>
        </body>
      </html>
    `);
    doc.close();

    const win = iframe.contentWindow;
    // Give the iframe a moment to apply CSS before printing
    setTimeout(() => {
      win.focus();
      win.print();
      // Cleanup
      setTimeout(() => document.body.removeChild(iframe), 100);
    }, 200);
  }
</script>
