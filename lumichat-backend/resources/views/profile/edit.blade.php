@extends('layouts.app')
@section('title', 'Profile')

@section('content')
@php
  $hour     = now()->hour;
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-3">

  {{-- Hero / Greeting --}}
  <header class="ui-appear rounded-2xl border border-gray-200/70 dark:border-gray-700
                 bg-gradient-to-r from-indigo-50/80 via-violet-50/70 to-fuchsia-50/60
                 dark:from-gray-800 dark:via-gray-800 dark:to-gray-800/70
                 shadow-sm p-6 md:p-8 text-center"
          style="animation-delay:.02s">
    <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold
                bg-white/80 dark:bg-gray-900/60 text-indigo-600 dark:text-indigo-300
                border border-indigo-100/60 dark:border-gray-700">
      <span>👋</span>
      <span>Welcome</span>
    </div>

    <h1 class="mt-3 text-2xl md:text-3xl font-semibold tracking-tight title-dynamic">
      {{ $greeting }}, {{ Auth::user()->name }}
    </h1>

    <p class="mt-1 text-sm md:text-[15px] muted-dynamic">
      You can manage your personal information and security settings below.
    </p>
  </header>

  {{-- Content grid --}}
  <div class="mt-6 md:mt-8 grid gap-6 md:gap-8 lg:grid-cols-12">

    {{-- Profile Information (wider) --}}
    <section class="ui-appear lg:col-span-7 card-shell p-5 sm:p-6 lg:p-7 h-full flex flex-col"
             style="animation-delay:.06s">
      @include('profile.partials.update-profile-information-form', [
        'user'         => $user,
        'registration' => $registration ?? null,
      ])
    </section>

    {{-- Update Password (narrower) --}}
    <section class="ui-appear lg:col-span-5 card-shell p-5 sm:p-6 lg:p-7 h-full flex flex-col"
             style="animation-delay:.09s">
      @include('profile.partials.update-password-form')
    </section>

    {{-- Delete Account (full width) --}}
    <section class="ui-appear lg:col-span-12 card-shell p-5 sm:p-6 lg:p-7"
             style="animation-delay:.12s">
      @include('profile.partials.delete-user-form')
    </section>
  </div>
</div>

{{-- Styles for animation + aligned headers (see B/C below) --}}
@push('styles')
<style>
/* ============ Smooth enter animation ============ */
@keyframes uiEnter {
  0%   { opacity:0; transform: translateY(10px) scale(.985); }
  60%  { opacity:1; transform: translateY(-2px) scale(1.005); }
  100% { opacity:1; transform: translateY(0)   scale(1); }
}
.ui-appear { animation: uiEnter .42s cubic-bezier(.2,.8,.2,1) both; }

/* ============ Uniform form header rows ============ */
.form-head{
  display:flex; align-items:center; justify-content:space-between; gap:.75rem;
  min-height: 44px;            /* same height for both cards’ header line */
  margin-bottom:.75rem;
}
@media (min-width: 640px){ .form-head{ min-height: 46px; } }

/* Button “size reference” so the other side can have an invisible spacer */
.btn-size{
  display:inline-flex; align-items:center; justify-content:center;
  height: 38px; padding: .5rem 1rem; border-radius:.75rem;
  font-weight:600;
}
/* Tiny press effect for call-to-actions */
.btn-press { transition: transform .1s ease, box-shadow .12s ease; }
.btn-press:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(99,102,241,.18); }
.btn-press:active{ transform: translateY(0);   box-shadow: 0 4px 10px rgba(99,102,241,.16); }
</style>
@endpush
@endsection
