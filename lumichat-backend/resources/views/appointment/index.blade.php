@extends('layouts.app')
@section('title','Appointment')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Book Appointment</h2>
    <a href="{{ route('appointment.history') }}"
       class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
      View Appointment
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
    </a>
  </div>

  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    {{-- ======= custom checkbox styles (scoped) ======= --}}
    <style>
      .checkbox-wrapper-46 input[type="checkbox"]{display:none;visibility:hidden}
      .checkbox-wrapper-46 .cbx{margin:auto;-webkit-user-select:none;user-select:none;cursor:pointer;display:flex;align-items:center}
      .checkbox-wrapper-46 .cbx span{display:inline-block;vertical-align:middle;transform:translate3d(0,0,0)}
      .checkbox-wrapper-46 .cbx span:first-child{
        position:relative;width:18px;height:18px;border-radius:3px;transform:scale(1);vertical-align:middle;
        border:1px solid #9098a9;transition:all .2s ease;background:transparent
      }
      .dark .checkbox-wrapper-46 .cbx span:first-child{border-color:#6b7280}
      .checkbox-wrapper-46 .cbx span:first-child svg{
        position:absolute;top:3px;left:2px;fill:none;stroke:#fff;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;
        stroke-dasharray:16px;stroke-dashoffset:16px;transition:all .3s ease .1s;transform:translate3d(0,0,0)
      }
      .checkbox-wrapper-46 .cbx span:first-child:before{content:"";width:100%;height:100%;background:#6366f1;display:block;transform:scale(0);opacity:1;border-radius:50%}
      .checkbox-wrapper-46 .cbx span:last-child{padding-left:10px}
      .dark .checkbox-wrapper-46 .cbx:hover span:first-child{border-color:#818cf8}
      .checkbox-wrapper-46 .inp-cbx:checked + .cbx span:first-child{background:#6366f1;border-color:#6366f1;animation:wave-46 .4s ease}
      .checkbox-wrapper-46 .inp-cbx:checked + .cbx span:first-child svg{stroke-dashoffset:0}
      .checkbox-wrapper-46 .inp-cbx:checked + .cbx span:first-child:before{transform:scale(3.5);opacity:0;transition:all .6s ease}
      @keyframes wave-46{50%{transform:scale(.9)}}
      /* utility to hide server errors without shifting layout too much */
      .hidden-error{display:none !important}
    </style>

    <form method="POST" action="{{ route('appointment.store') }}" class="space-y-6">
      @csrf

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Select a counselor and time slot *
        </label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <select id="counselorSelect" name="counselor_id"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
              <option value="">Select a counselor</option>
              @foreach($counselors as $c)
                <option value="{{ $c->id }}" @selected(old('counselor_id')==$c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
            @error('counselor_id')
              <p data-error-for="counselor_id" class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">Available time slots load after picking a counselor and date.</p>
          </div>

          <div>
            <select id="timeSelect" name="time" disabled
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
              <option value="">No available slots</option>
            </select>
            <div id="timeLoading" class="hidden mt-2 text-xs text-gray-500">Loading available times…</div>
            @error('time')
              <p data-error-for="time" class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Choose a preferred date *</label>
          <input id="dateInput" type="date" name="date" value="{{ old('date') }}"
                 min="{{ now()->toDateString() }}"
                 class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
          @error('date')
            <p data-error-for="date" class="mt-1 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>
        <div></div>
      </div>

      {{-- ======= CUSTOM CONSENT CHECKBOX ======= --}}
      <div>
        <div class="checkbox-wrapper-46">
          <input type="checkbox" id="consent-cbx" class="inp-cbx" name="consent" value="1" {{ old('consent') ? 'checked' : '' }}/>
          <label for="consent-cbx" class="cbx">
            <span>
              <svg viewBox="0 0 12 10" height="10px" width="12px"><polyline points="1.5 6 4.5 9 10.5 1"></polyline></svg>
            </span>
            <span class="text-sm text-gray-600 dark:text-gray-300">
              I understand that my information will be handled according to LumiCHAT’s privacy policy.
            </span>
          </label>
        </div>
        @error('consent')
          <p data-error-for="consent" class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
      </div>
      {{-- ====================================== --}}

      <div class="flex items-center gap-4 pt-2">
        <a href="{{ route('chat.index') }}"
           class="inline-flex justify-center rounded-lg bg-gray-100 px-6 py-3 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
          Cancel
        </a>
        <button type="submit"
                class="inline-flex justify-center rounded-lg bg-indigo-600 px-6 py-3 font-medium text-white hover:bg-indigo-700">
          Confirm Appointment
        </button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const counselorSel = document.getElementById('counselorSelect');
  const dateInput    = document.getElementById('dateInput');
  const timeSel      = document.getElementById('timeSelect');
  const loadingEl    = document.getElementById('timeLoading');
  const consentCbx   = document.getElementById('consent-cbx');

  // GUARANTEED trailing slash (prevents /appointment/slots8 404)
  const slotsBase = (@json(url('/appointment/slots')) + '/');

  /* ========== SweetAlert helpers ========== */
  const toast = (title, icon='info', timer=2500) => {
    Swal.fire({ toast:true, position:'top-end', showConfirmButton:false, timer, icon, title });
  };
  const alertHtmlList = (title, items, icon='error') => {
    const html = '<ul style="text-align:left;margin:0;padding-left:1rem">' + items.map(i => `<li>• ${i}</li>`).join('') + '</ul>';
    Swal.fire({ icon, title, html });
  };

  /* --- Show success or validation errors after POST redirect --- */
  const successMsg = @json(session('status'));
  const pageErrors = @json($errors->all());
  if (successMsg) Swal.fire({ icon:'success', title:'Success', text:successMsg, timer:2200, showConfirmButton:false });
  if (Array.isArray(pageErrors) && pageErrors.length) alertHtmlList('Please fix the following', pageErrors, 'error');

  /* ===== inline error hide helpers ===== */
  const hideError = (field) => {
    document.querySelectorAll(`[data-error-for="${field}"]`).forEach(el => el.classList.add('hidden-error'));
    if (Swal.isVisible()) Swal.close(); // also close the modal when user starts fixing
  };

  // Bind field listeners to clear their own errors
  counselorSel.addEventListener('change', () => hideError('counselor_id'));
  timeSel.addEventListener('change',     () => hideError('time'));
  dateInput.addEventListener('input',    () => hideError('date'));
  dateInput.addEventListener('change',   () => hideError('date'));
  consentCbx.addEventListener('change',  () => hideError('consent'));

  /* ========== Slots loader ========== */
  function clearTimes(placeholder='Choose a preferred time *'){
    timeSel.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder;
    timeSel.appendChild(opt);
    timeSel.disabled = true;
  }

  function isWeekend(dateStr){
    const d = new Date(dateStr + 'T00:00:00');
    const day = d.getDay(); // 0 Sun .. 6 Sat
    return day === 0 || day === 6;
  }

  async function loadSlots(){
    const cid = counselorSel.value;
    const date = dateInput.value;

    // As user is interacting, clear field-level errors proactively
    if (cid)  hideError('counselor_id');
    if (date) hideError('date');

    if(!cid || !date){ clearTimes('No available slots'); return; }

    if(isWeekend(date)){
      clearTimes('No counselor availability on weekends (Mon–Fri)');
      toast('Counselors are available Mon–Fri only.','info');
      return;
    }

    loadingEl.classList.remove('hidden');
    clearTimes('Loading…');

    try{
      const url = slotsBase + encodeURIComponent(cid) + '?date=' + encodeURIComponent(date);
      const res = await fetch(url, { headers:{'X-Requested-With':'XMLHttpRequest'} });
      if(!res.ok){ clearTimes('Unable to load slots'); toast('Failed to load time slots.','error'); return; }

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
        timeSel.disabled = false;

        // user can now select a time; if they do, clear the time error
        timeSel.addEventListener('change', () => hideError('time'), { once: true });
      }else{
        const reason = data.reason || '';
        const message = data.message || '';
        if(reason === 'weekend') clearTimes('No counselor availability on weekends (Mon–Fri)');
        else if(reason === 'no_availability') clearTimes('No availability for that day');
        else if(reason === 'fully_booked') clearTimes('All slots are booked for that date');
        else if(reason === 'no_slots') clearTimes('No available slots within working hours');
        else clearTimes('No available slots');
        if(message) toast(message,'info');
      }
    }catch(e){
      console.error('Failed to load slots', e);
      clearTimes('Unable to load slots');
      toast('Something went wrong while loading slots.','error');
    }finally{
      loadingEl.classList.add('hidden');
    }
  }

  counselorSel.addEventListener('change', loadSlots);
  dateInput.addEventListener('change', loadSlots);

  // Pre-load on page open if values are preselected
  if(counselorSel.value && dateInput.value) loadSlots();
});
</script>
@endpush
@endsection
