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

  $canConfirm = $appointment->status === 'pending';
  $canDone    = $appointment->status === 'confirmed';
@endphp

{{-- Print stylesheet: hide .no-print in print; remove shadows/borders for a clean output --}}
@push('styles')
<style>
  @media print {
    .no-print { display: none !important; }
    .print-container { box-shadow: none !important; border: 0 !important; }
    body { background: #fff !important; }
  }
</style>
@endpush

<div class="max-w-5xl mx-auto p-6">
  <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 print-container">

    {{-- Header with status + actions (no back link) --}}
    <div class="px-6 pt-6 pb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
      <div>
        <div class="flex items-center gap-3">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Appointment #{{ $appointment->id }}
          </h2>
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $cls }}">
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

      {{-- Actions up top --}}
      <div class="flex items-center gap-2 no-print">
        {{-- Print --}}
        <button type="button" onclick="window.print()"
                class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-100">
          Print
        </button>

        {{-- Confirm --}}
        <form method="POST" action="{{ route('admin.appointments.status', $appointment->id) }}"
              onsubmit="return askAction(event, this, 'confirm')">
          @csrf @method('PATCH')
          <input type="hidden" name="action" value="confirm">
          <button type="submit"
                  title="{{ $canConfirm ? 'Confirm this appointment' : 'Only pending appointments can be confirmed' }}"
                  class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  {{ $canConfirm ? '' : 'disabled' }}>
            Confirm
          </button>
        </form>

        {{-- Done --}}
        <form method="POST" action="{{ route('admin.appointments.status', $appointment->id) }}"
              onsubmit="return askAction(event, this, 'done')">
          @csrf @method('PATCH')
          <input type="hidden" name="action" value="done">
          <button type="submit"
                  title="{{ $canDone ? 'Mark as completed' : 'You can only mark confirmed appointments as done' }}"
                  class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  {{ $canDone ? '' : 'disabled' }}>
            Done
          </button>
        </form>
      </div>
    </div>

    {{-- Meta --}}
    <div class="px-6 pb-2 grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="space-y-3">
        <div class="text-xs uppercase tracking-wide text-gray-500">Student</div>
        <div class="text-gray-900 dark:text-gray-100 font-medium">{{ $appointment->student_name }}</div>
        @if(!empty($appointment->student_email))
          <div class="text-gray-600 dark:text-gray-300 text-sm">{{ $appointment->student_email }}</div>
        @endif
      </div>

      <div class="space-y-3">
        <div class="text-xs uppercase tracking-wide text-gray-500">Scheduled</div>
        <div class="text-gray-900 dark:text-gray-100 font-medium">
          {{ $dt->format('l, M d, Y · g:i A') }}
        </div>
      </div>

      <div class="space-y-3 md:col-span-2 md:grid md:grid-cols-2 md:gap-6">
        <div>
          <div class="text-xs uppercase tracking-wide text-gray-500">Counselor</div>
          <div class="text-gray-900 dark:text-gray-100 font-medium">{{ $appointment->counselor_name }}</div>
          <div class="text-gray-600 dark:text-gray-300 text-sm">
            {{ $appointment->counselor_email }}
            @if(!empty($appointment->counselor_phone)) · {{ $appointment->counselor_phone }} @endif
          </div>
        </div>
      </div>
    </div>

    {{-- Final Diagnosis / Counselor Note (clean look: no heavy borders) --}}
    <div class="px-6 pb-6 mt-2">
      <div class="rounded-xl bg-indigo-50/40 dark:bg-indigo-900/20">
        <div class="flex items-center justify-between px-4 py-3">
          <div class="text-xs font-semibold tracking-wide uppercase text-gray-700 dark:text-gray-200">
            Final Diagnosis / Counselor Note
          </div>
          @if($appointment->finalized_at)
            <div class="text-xs text-gray-500">
              Saved {{ \Carbon\Carbon::parse($appointment->finalized_at)->format('M d, Y g:i A') }}
              @if(!empty($appointment->finalized_by_name)) by <span class="font-medium">{{ $appointment->finalized_by_name }}</span>@endif
            </div>
          @endif
        </div>

        <div class="px-4 pb-4">
          @if($appointment->status === 'completed')
            <form method="POST" action="{{ route('admin.appointments.saveNote', $appointment->id) }}" class="space-y-3">
              @csrf
              @method('PATCH')
              <textarea name="final_note" rows="6"
                        class="w-full rounded-lg border-0 ring-0 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-900/40 p-3"
                        placeholder="Write the final diagnosis / counselor note...">{{ old('final_note', $appointment->final_note) }}</textarea>
              <div class="flex items-center justify-between no-print">
                @if ($errors->any())
                  <div class="text-sm text-rose-600">
                    @foreach ($errors->all() as $err)
                      • {{ $err }}
                    @endforeach
                  </div>
                @else
                  <span class="text-xs text-gray-500">This note is visible to the student.</span>
                @endif

                <button type="submit"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                  Save Final Note
                </button>
              </div>
            </form>
          @else
            <div class="bg-white dark:bg-gray-900/40 rounded-lg p-3">
              <textarea rows="5" class="w-full rounded-md border-0 ring-0 bg-transparent" disabled
                        placeholder="Available after the appointment is marked Completed.">@if(!empty($appointment->final_note)){{ $appointment->final_note }}@endif</textarea>
              <div class="text-xs text-slate-500 mt-2">You can add or edit the final note once this appointment is <b>Completed</b>.</div>
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
