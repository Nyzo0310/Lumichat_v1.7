@extends('layouts.admin')
@section('title','Chatbot Session')

@section('content')
@php
  // Pretty session code based on the session's own year
  $codeYear = $session->created_at?->format('Y') ?? now()->format('Y');
  $code     = 'LMC-' . $codeYear . '-' . str_pad($session->id, 4, '0', STR_PAD_LEFT);
@endphp

<div class="max-w-5xl mx-auto p-6 space-y-6">
  {{-- Page header --}}
  <div class="flex items-center justify-between">
    <h2 class="text-2xl font-bold tracking-tight text-slate-800">Chatbot Session</h2>
    <a href="{{ route('admin.chatbot-sessions.index') }}" class="text-sm text-indigo-600 hover:underline">← Back to list</a>
  </div>

  {{-- Summary card --}}
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <div class="text-xs text-slate-500 uppercase">Session ID</div>
        <div class="mt-1 font-semibold text-slate-900 flex items-center gap-2">
          <span id="sessionCode">{{ $code }}</span>
          <button type="button"
                  onclick="copyText('#sessionCode')"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50"
                  title="Copy Session ID">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <rect x="9" y="9" width="13" height="13" rx="2" ry="2" stroke-width="2"/>
              <rect x="3" y="3" width="13" height="13" rx="2" ry="2" stroke-width="2"/>
            </svg>
          </button>
        </div>
      </div>

      <div>
        <div class="text-xs text-slate-500 uppercase">Assessment Result</div>
        <div class="mt-1 font-medium text-slate-800">
          {{ $session->topic_summary ?? '—' }}
        </div>
      </div>

      <div>
        <div class="text-xs text-slate-500 uppercase">Student</div>
        <div class="mt-1 font-medium text-slate-800">{{ $session->user->name ?? '—' }}</div>
      </div>

      <div>
        <div class="text-xs text-slate-500 uppercase">Assessment Date</div>
        <div class="mt-1 font-medium text-slate-800">
          {{ $session->created_at?->format('F d, Y • h:i A') }}
        </div>
      </div>
    </div>

    {{-- Divider --}}
    <div class="mt-6 pt-6 border-t border-slate-200"></div>

    {{-- Actions (right-aligned) --}}
    <div class="flex items-center justify-end gap-2">
      @if(!empty($session->user?->email))
        <a href="mailto:{{ $session->user->email }}"
           class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white text-slate-800 ring-1 ring-slate-200 hover:bg-slate-50">
          Email Student
        </a>
      @endif

      <button type="button"
              onclick="copyText('#sessionCode')"
              class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white text-slate-800 ring-1 ring-slate-200 hover:bg-slate-50">
        Copy Session ID
      </button>
    </div>
  </div>
</div>

{{-- tiny helper to copy text from an element --}}
<script>
  function copyText(selector){
    const el = document.querySelector(selector);
    if(!el) return;
    const text = el.textContent.trim();
    navigator.clipboard.writeText(text).then(() => {
      // Optional toast if SweetAlert2 is loaded in your layout
      if (window.Swal) {
        Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Copied', showConfirmButton:false, timer:1400 });
      }
    });
  }
</script>
@endsection
