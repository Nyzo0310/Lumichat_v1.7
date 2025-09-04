@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="max-w-5xl mx-auto p-6 space-y-8">

  {{-- Page header --}}
  <div>
    <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Settings</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400">Manage notifications, chat session controls, and interface preferences.</p>
  </div>

  @if(session('success'))
    <div class="rounded-xl bg-green-50 border border-green-200 text-green-700 px-4 py-3">
      {{ session('success') }}
    </div>
  @endif

  <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
    @csrf

    {{-- ================= Notification Preferences ================= --}}
    <section class="card-shell p-6">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Notification Preferences</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Choose how you want to be notified about appointments and updates.</p>

      <div class="grid sm:grid-cols-2 gap-4">
        <label class="group flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 hover:bg-white dark:hover:bg-gray-900/70 transition px-4 py-3">
          <div>
            <div class="font-medium text-gray-900 dark:text-gray-100">Email Reminders</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Receive appointment & session reminders via email.</div>
          </div>
          <input type="checkbox" name="email_reminders" @checked($settings->email_reminders) class="pretty-toggle">
        </label>

        <label class="group flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 hover:bg-white dark:hover:bg-gray-900/70 transition px-4 py-3">
          <div>
            <div class="font-medium text-gray-900 dark:text-gray-100">SMS Alerts</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Get urgent updates via text message.</div>
          </div>
          <input type="checkbox" name="sms_alerts" @checked($settings->sms_alerts) class="pretty-toggle">
        </label>
      </div>
    </section>

    {{-- ================= Chat Session Controls ================= --}}
    <section class="card-shell p-6">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Chat Session Controls</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Control saving and cleanup of your chat conversations.</p>

      <div class="grid sm:grid-cols-2 gap-4">
        <label class="group flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 hover:bg-white dark:hover:bg-gray-900/70 transition px-4 py-3">
          <div>
            <div class="font-medium text-gray-900 dark:text-gray-100">Auto-save Chat Sessions</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Automatically save your conversations.</div>
          </div>
          <input type="checkbox" name="autosave_chats" @checked($settings->autosave_chats) class="pretty-toggle">
        </label>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 hover:bg-white dark:hover:bg-gray-900/70 transition px-4 py-3">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="font-medium text-gray-900 dark:text-gray-100">Auto-delete Old Chats</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">Delete chats older than X days (leave blank to disable).</div>
            </div>
            <input
              type="number"
              name="autodelete_days"
              value="{{ old('autodelete_days', $settings->autodelete_days) }}"
              placeholder="e.g., 30"
              min="0" max="365"
              class="w-28 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2"
            />
          </div>
        </div>
      </div>
    </section>

    {{-- ================= Interface Customization ================= --}}
    <section class="card-shell p-6">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Interface Customization</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Switch between light and dark themes.</p>

      <label class="group flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 hover:bg-white dark:hover:bg-gray-900/70 transition px-4 py-3">
        <div>
          <div class="font-medium text-gray-900 dark:text-gray-100">Dark Mode</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">Use a darker theme that’s easier on the eyes.</div>
        </div>
        {{-- Tailwind-only Uiverse-style switch (fixed) --}}
        <label class="relative inline-flex items-center cursor-pointer select-none">
          <input
            id="darkModeToggle"
            type="checkbox"
            name="dark_mode"
            @checked($settings->dark_mode)
            class="sr-only peer"
            aria-label="Toggle dark mode"
          >
          <span
            class="block w-[3.5em] h-[2em] rounded-full border border-[#414141]
                  bg-black dark:bg-transparent   {{-- 🔥 Black track in light mode, normal in dark --}}
                  transition-[box-shadow,border-color,background-color] duration-300 ease-[cubic-bezier(0.175,0.885,0.32,1.275)]
                  relative
                  peer-checked:border-[#0974f1]
                  peer-checked:shadow-[0_0_20px_rgba(9,117,241,0.8)]
                  peer-checked:bg-transparent    {{-- track clears when ON --}}
                  peer-checked:[&>span]:translate-x-[1.5em]"
          >
            <span
              aria-hidden="true"
              class="absolute left-[0.2em] top-[0.2em] w-[1.4em] h-[1.4em] rounded-full bg-white
                    transition-transform duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]"
            ></span>
          </span>
        </label>
    </section>

    {{-- ================= Support & Feedback ================= --}}
    <section class="card-shell p-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
        Support &amp; Feedback
    </h3>

    <div class="grid sm:grid-cols-2 gap-4">
        <a href="mailto:help@lumichat.local" class="sf-btn sf-blue">
        Contact Support
        </a>

        <a href="#" class="sf-btn sf-purple">
        Suggest a Feature
        </a>

        <a href="#" class="sf-btn sf-red">
        Report a Bug
        </a>

        <a href="#" class="sf-btn sf-gray">
        System Announcements
        </a>
    </div>
    </section>

    <div class="flex justify-end">
      <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium shadow-sm hover:bg-indigo-700">
        Save Changes
      </button>
    </div>
  </form>
</div>

{{-- ===== Toggles (pretty) ===== --}}
<style>
.pretty-toggle{
  -webkit-appearance:none; appearance:none;
  width:3rem; height:1.6rem; border-radius:9999px;
  background:#d4d4d8; position:relative; outline:none; cursor:pointer;
  transition:background .2s ease, box-shadow .2s ease;
  box-shadow: inset 0 0 0 1px rgba(0,0,0,.05);
}
.pretty-toggle::after{
  content:""; position:absolute; top:2px; left:2px;
  width:1.2rem; height:1.2rem; border-radius:9999px; background:#fff;
  transition:left .2s ease;
  box-shadow: 0 1px 2px rgba(0,0,0,.15);
}
.pretty-toggle:checked{ background:#4f46e5; } /* indigo-600 */
.pretty-toggle:checked::after{ left:calc(100% - 1.2rem - 2px); }
.pretty-toggle:focus-visible{ box-shadow: 0 0 0 3px rgba(79,70,229,.25); }
</style>

{{-- ===== Live Dark Mode Toggle ===== --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const darkToggle = document.getElementById('darkModeToggle');
  const htmlEl = document.documentElement;

  // 1) Load saved preference (client-side)
  if (localStorage.getItem('lumichat_dark') === '1') {
    htmlEl.classList.add('dark');
    if (darkToggle) darkToggle.checked = true;
  } else if (localStorage.getItem('lumichat_dark') === '0') {
    htmlEl.classList.remove('dark');
    if (darkToggle) darkToggle.checked = false;
  }
  // 2) Immediate apply + persist
  darkToggle?.addEventListener('change', () => {
    if (darkToggle.checked) {
      htmlEl.classList.add('dark');
      localStorage.setItem('lumichat_dark', '1');
    } else {
      htmlEl.classList.remove('dark');
      localStorage.setItem('lumichat_dark', '0');
    }
  });
});
</script>

@endsection
