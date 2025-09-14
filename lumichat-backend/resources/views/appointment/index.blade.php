@extends('layouts.app')
@section('title','Appointment')

@section('content')
<div class="mx-auto max-w-6xl px-4 pt-0 pb-8">
  <div class="mb-2">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 p-6 shadow-sm mb-4">
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="rounded-2xl bg-white/15 p-2 text-white">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7 2a1 1 0 0 0-1 1v1H5a3 3 0 0 0-3 3v11a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-1V3a1 1 0 1 0-2 0v1H8V3a1 1 0 0 0-1-1Zm12 7H5v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9Z"/>
          </svg>
        </div>
        <div class="-mt-0.5">
          {{-- smaller, normal-sized title --}}
          <h1 class="text-lg font-semibold tracking-tight text-white">Book Appointment</h1>
          <p class="text-white/80 text-sm">Pick a counselor, date, and time.</p>
        </div>
      </div>

      <a href="{{ route('appointment.history') }}"
        class="self-center inline-flex items-center gap-2 rounded-xl bg-white/20 px-4 py-2.5 text-sm font-medium text-white backdrop-blur transition hover:bg-white/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
        View Appointment
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </a>
    </div>
  </div>

  {{-- ======= Two-column layout ======= --}}
  <div class="grid grid-cols-1 gap-6 md:grid-cols-5">
    {{-- Left: How it works --}}
    <aside class="md:col-span-2">
      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">How it works</h3>
        <ol class="space-y-3">
          <li class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">1</span>
            <div>
              <p class="font-medium text-gray-900 dark:text-gray-100">Choose counselor</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Availability varies by counselor and weekday.</p>
            </div>
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">2</span>
            <div>
              <p class="font-medium text-gray-900 dark:text-gray-100">Pick date</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Weekends are closed (Mon–Fri only).</p>
            </div>
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">3</span>
            <div>
              <p class="font-medium text-gray-900 dark:text-gray-100">Select time</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Times load after you choose counselor & date.</p>
            </div>
          </li>
        </ol>

        <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-300">
          <p class="mb-2 font-medium">Tips</p>
          <ul class="list-inside list-disc space-y-1">
            <li>Arrive 15 minutes early.</li>
            <li>Bring student ID for verification.</li>
            <li>Reschedule via Appointment History.</li>
          </ul>
        </div>
      </div>
    </aside>

    {{-- Right: Form --}}
    <section class="md:col-span-3">
      <div class="rounded-2xl border border-gray-200 bg-white/80 p-8 shadow-lg backdrop-blur-sm dark:border-gray-700 dark:bg-gray-800/70">

        <h2 class="mb-6 text-lg font-semibold text-gray-900 dark:text-gray-100">Fill Appointment Details</h2>

        {{-- Scoped style to remove any built-in/previous date icon and make our button the only icon --}}
        <style>
          #dateInput{ background-image:none!important; padding-right:3rem; }
          #dateInput::-webkit-calendar-picker-indicator{ display:none!important; }
        </style>

        <form method="POST" action="{{ route('appointment.store') }}" class="space-y-7">
          @csrf

          {{-- STEP 1 --}}
          <div class="space-y-2">
            <label for="counselorSelect" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              1. Select a counselor *
            </label>
            <select id="counselorSelect" name="counselor_id" class="select-ui">
              <option value="">Select a counselor</option>
              @foreach($counselors as $c)
                <option value="{{ $c->id }}" @selected(old('counselor_id')==$c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
            <p class="text-xs text-gray-500 dark:text-gray-400">After choosing a counselor, pick a date to see available times.</p>
            @error('counselor_id')<p data-error-for="counselor_id" class="text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- STEP 2 (Custom calendar icon that opens the picker) --}}
          <div class="space-y-2">
            <label for="dateInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              2. Choose a preferred date *
            </label>
            <div class="relative">
              <input id="dateInput" type="date" name="date" value="{{ old('date') }}"
                     min="{{ now()->toDateString() }}" class="input-ui pr-12">
              <button type="button" id="openDateBtn"
                      class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                      aria-label="Open calendar">
                {{-- Flaticon-style calendar (clean SVG) --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M7 2a1 1 0 0 0-1 1v1H5a3 3 0 0 0-3 3v11a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-1V3a1 1 0 1 0-2 0v1H8V3a1 1 0 0 0-1-1ZM5 9h14v9a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V9Z"/>
                </svg>
              </button>
            </div>
            @error('date')<p data-error-for="date" class="text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- STEP 3 (Modern time grid + hidden select for submission/logic) --}}
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              3. Select a time *
            </label>

            {{-- Hidden native select (kept for accessibility + submission + your logic) --}}
            <select id="timeSelect" name="time" class="sr-only" aria-hidden="true" tabindex="-1">
              <option value="">available slots</option>
            </select>

            {{-- Pretty grid that mirrors #timeSelect --}}
            <div id="timeGrid" class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4"></div>

            {{-- Loading / empty states --}}
            <div id="timeLoading" class="hidden text-xs text-gray-500 dark:text-gray-400">Loading available times…</div>
            <p id="timeEmpty" class="text-xs text-gray-500 dark:text-gray-400 hidden">No available slots.</p>

            @error('time')<p data-error-for="time" class="text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- CONSENT --}}
          <div class="flex items-start gap-3">
            <input type="checkbox" id="consent-cbx"
                   class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-500"
                   name="consent" value="1" {{ old('consent') ? 'checked' : '' }}/>
            <label for="consent-cbx" class="text-sm text-gray-700 dark:text-gray-300">
              I understand that my information will be handled according to LumiCHAT’s privacy policy.
            </label>
          </div>
          @error('consent')<p data-error-for="consent" class="text-sm text-red-600">{{ $message }}</p>@enderror

          {{-- ACTIONS --}}
          <div class="flex items-center gap-4 pt-2">
            <a href="{{ route('chat.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md transition hover:shadow-lg hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-indigo-500">
              Confirm Appointment
            </button>
          </div>
        </form>
      </div>
    </section>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const counselorSel = document.getElementById('counselorSelect');
  const dateInput    = document.getElementById('dateInput');
  const openDateBtn  = document.getElementById('openDateBtn');

  const timeSel      = document.getElementById('timeSelect'); // kept for form submit/validation
  const timeGrid     = document.getElementById('timeGrid');   // pretty UI
  const loadingEl    = document.getElementById('timeLoading');
  const emptyEl      = document.getElementById('timeEmpty');

  const consentCbx   = document.getElementById('consent-cbx');

  // GUARANTEED trailing slash
  const slotsBase = (@json(url('/appointment/slots')) + '/');

  // SweetAlert helpers
  const toast = (title, icon='info', timer=2500) =>
    Swal.fire({ toast:true, position:'top-end', showConfirmButton:false, timer, icon, title });
  const alertHtmlList = (title, items, icon='error') => {
    const html = '<ul style="text-align:left;margin:0;padding-left:1rem">' + items.map(i => `<li>• ${i}</li>`).join('') + '</ul>';
    Swal.fire({ icon, title, html });
  };

  const successMsg = @json(session('status'));
  const pageErrors = @json($errors->all());
  if (successMsg) Swal.fire({ icon:'success', title:'Success', text:successMsg, timer:2200, showConfirmButton:false });
  if (Array.isArray(pageErrors) && pageErrors.length) alertHtmlList('Please fix the following', pageErrors, 'error');

  const hideError = (field) => {
    document.querySelectorAll(`[data-error-for="${field}"]`).forEach(el => el.classList.add('hidden-error'));
    if (Swal.isVisible()) Swal.close();
  };

  // Open the native date picker when clicking our custom icon
  openDateBtn.addEventListener('click', () => {
    if (dateInput.showPicker) { dateInput.showPicker(); } // modern browsers
    else { dateInput.focus(); dateInput.click(); }        // fallback
  });

  counselorSel.addEventListener('change', () => { hideError('counselor_id'); loadSlots(); });
  dateInput.addEventListener('change',   () => { hideError('date');        loadSlots(); });
  consentCbx.addEventListener('change',  () => hideError('consent'));

  // helpers for time UI
  function clearTimeUI(placeholder='available slots'){
    timeSel.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder;
    timeSel.appendChild(opt);
    timeGrid.innerHTML = '';
    emptyEl.classList.add('hidden');
  }

  function buildTimeGridFromSelect(){
    timeGrid.innerHTML = '';
    const current = timeSel.value;
    [...timeSel.options].forEach(o => {
      if (!o.value) return;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'time-pill';
      btn.dataset.value = o.value;
      btn.textContent = o.textContent;
      if (o.value === current) btn.classList.add('time-pill--selected');
      btn.addEventListener('click', () => {
        timeSel.value = o.value;
        hideError('time');
        document.querySelectorAll('.time-pill--selected').forEach(el => el.classList.remove('time-pill--selected'));
        btn.classList.add('time-pill--selected');
      });
      timeGrid.appendChild(btn);
    });
    if (timeGrid.children.length === 0){ emptyEl.classList.remove('hidden'); }
  }

  function isWeekend(dateStr){
    const d = new Date(dateStr + 'T00:00:00');
    const day = d.getDay(); // 0 Sun .. 6 Sat
    return day === 0 || day === 6;
  }

  async function loadSlots(){
    const cid  = counselorSel.value;
    const date = dateInput.value;

    if (!cid){ clearTimeUI('select counselor first'); return; }
    if (!date){ clearTimeUI('pick a date'); return; }
    if (isWeekend(date)){
      clearTimeUI('closed (Mon–Fri only)'); toast('Counselors are available Mon–Fri only.','info'); return;
    }

    loadingEl.classList.remove('hidden');
    clearTimeUI('loading…');

    try{
      const url = slotsBase + encodeURIComponent(cid) + '?date=' + encodeURIComponent(date);
      const res = await fetch(url, { headers:{'X-Requested-With':'XMLHttpRequest'} });
      if(!res.ok){ clearTimeUI('unable to load'); toast('Failed to load time slots.','error'); return; }

      const data = await res.json();
      timeSel.innerHTML = '';

      if(Array.isArray(data.slots) && data.slots.length){
        const ph = document.createElement('option');
        ph.value = ''; ph.textContent = 'Choose a preferred time *';
        timeSel.appendChild(ph);

        data.slots.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s.value;
          opt.textContent = s.label;
          timeSel.appendChild(opt);
        });

        const oldVal = @json(old('time'));
        if(oldVal){ [...timeSel.options].forEach(o => { if(o.value === oldVal) o.selected = true; }); }

        buildTimeGridFromSelect();
      }else{
        const reason = data.reason || '';
        const message = data.message || '';
        if(reason === 'weekend')             clearTimeUI('Mon–Fri only');
        else if(reason === 'no_availability') clearTimeUI('no availability on this day');
        else if(reason === 'fully_booked')    clearTimeUI('fully booked');
        else if(reason === 'no_slots')        clearTimeUI('no working-hour slots');
        else clearTimeUI('no available slots');
        if(message) toast(message,'info');
        buildTimeGridFromSelect();
      }
    }catch(e){
      console.error('Failed to load slots', e);
      clearTimeUI('unable to load');
      toast('Something went wrong while loading slots.','error');
      buildTimeGridFromSelect();
    }finally{
      loadingEl.classList.add('hidden');
    }
  }

  if(counselorSel.value && dateInput.value) loadSlots();
});
</script>

@include('profile.partials.alerts')
@endpush
@endsection
