@extends('layouts.student-registration')

@section('content')
{{-- ===== Compact utilities for short viewports ===== --}}
<style>
@layer utilities {
  .compact .page-pad   { @apply py-6 sm:py-8; }
  .compact .banner     { @apply px-5 py-4 rounded-xl; }
  .compact .grid-gap   { @apply gap-4; }
  .compact .card       { @apply p-4 rounded-lg; }
  .compact .input-h    { @apply h-10; }
  .compact .footer-pad { @apply p-4; }
  .compact .title-sm   { @apply text-lg; }
  .compact .sub-sm     { @apply text-[13px]; }
}
</style>

<main class="bg-gray-100">
  <div class="mx-auto max-w-[1200px] px-4 sm:px-6 lg:px-8 py-12 page-pad">
    {{-- Banner --}}
    <div class="rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-5 sm:px-8 sm:py-6 text-white shadow-sm banner">
      <h1 class="title-sm text-xl sm:text-2xl font-semibold leading-tight">Registration Form</h1>
      <p class="mt-1 sub-sm text-xs sm:text-sm text-white/85">Complete the sections left → right.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-6" novalidate>
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 grid-gap">
        {{-- ===================== Card 1: Personal ===================== --}}
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 card">
          <div class="mb-3 flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">1</span>
            <h2 class="text-base font-semibold text-gray-900">Personal Information</h2>
          </div>
          <p class="mb-4 text-xs text-gray-500">Share your name, email, and contact so we can stay in touch.</p>

          {{-- Full name --}}
          <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
          <div class="mt-1 rounded-lg border border-gray-300 focus-within:ring-2 focus-within:ring-indigo-500 px-3">
            <input id="full_name" name="full_name" type="text" autocomplete="name" required minlength="2" maxlength="80"
                   value="{{ old('full_name') }}"
                   class="w-full h-11 input-h border-0 bg-transparent text-sm placeholder-gray-400 focus:outline-none focus:ring-0"
                   placeholder="Enter your full name">
          </div>
          @error('full_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

          {{-- Email --}}
          <label for="email" class="mt-4 block text-sm font-medium text-gray-700">Email</label>
          <div class="mt-1 rounded-lg border border-gray-300 focus-within:ring-2 focus-within:ring-indigo-500 px-3">
            <input id="email" name="email" type="email" autocomplete="email" required maxlength="255"
                   value="{{ old('email') }}"
                   class="w-full h-11 input-h border-0 bg-transparent text-sm placeholder-gray-400 focus:outline-none focus:ring-0"
                   placeholder="your.email@example.com">
          </div>
          @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

          {{-- Contact --}}
          <label for="contact_number" class="mt-4 block text-sm font-medium text-gray-700">Contact Number</label>
          <div class="mt-1 rounded-lg border border-gray-300 focus-within:ring-2 focus-within:ring-indigo-500 px-3">
            <input id="contact_number" name="contact_number" type="text" autocomplete="tel" required minlength="7" maxlength="20"
                   value="{{ old('contact_number') }}"
                   class="w-full h-11 input-h border-0 bg-transparent text-sm placeholder-gray-400 focus:outline-none focus:ring-0"
                   placeholder="+63 900 000 0000">
          </div>
          @error('contact_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- ===================== Card 2: Academic ===================== --}}
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 card">
          <div class="mb-3 flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">2</span>
            <h2 class="text-base font-semibold text-gray-900">Academic Information</h2>
          </div>
          <p class="mb-4 text-xs text-gray-500">Tell us your course and year level to access tailored support.</p>

          {{-- Course --}}
          <label for="course" class="block text-sm font-medium text-gray-700">Course</label>
          <div class="mt-1 rounded-lg border border-gray-300 focus-within:ring-2 focus-within:ring-indigo-500 px-3">
            <select id="course" name="course" required
                    class="w-full h-11 input-h border-0 bg-transparent text-sm text-gray-900 focus:outline-none focus:ring-0">
              <option disabled {{ old('course') ? '' : 'selected' }}>Select your course</option>
              <option value="BSIT"      {{ old('course') == 'BSIT' ? 'selected' : '' }}>College of Information Technology</option>
              <option value="EDUC"      {{ old('course') == 'EDUC' ? 'selected' : '' }}>College of Education</option>
              <option value="CAS"       {{ old('course') == 'CAS' ? 'selected' : '' }}>College of Arts and Sciences</option>
              <option value="CRIM"      {{ old('course') == 'CRIM' ? 'selected' : '' }}>College of Criminal Justice and Public Safety</option>
              <option value="BLIS"      {{ old('course') == 'BLIS' ? 'selected' : '' }}>College of Library Information Science</option>
              <option value="MIDWIFERY" {{ old('course') == 'MIDWIFERY' ? 'selected' : '' }}>College of Midwifery</option>
              <option value="BSHM"      {{ old('course') == 'BSHM' ? 'selected' : '' }}>College of Hospitality Management</option>
              <option value="BSBA"      {{ old('course') == 'BSBA' ? 'selected' : '' }}>College of Business</option>
            </select>
          </div>
          @error('course') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

          {{-- Year level --}}
          <label for="year_level" class="mt-4 block text-sm font-medium text-gray-700">Year Level</label>
          <div class="mt-1 rounded-lg border border-gray-300 focus-within:ring-2 focus-within:ring-indigo-500 px-3">
            <select id="year_level" name="year_level" required
                    class="w-full h-11 input-h border-0 bg-transparent text-sm text-gray-900 focus:outline-none focus:ring-0">
              <option disabled {{ old('year_level') ? '' : 'selected' }}>Select your year level</option>
              <option value="1st year" {{ old('year_level') == '1st year' ? 'selected' : '' }}>1st year</option>
              <option value="2nd year" {{ old('year_level') == '2nd year' ? 'selected' : '' }}>2nd year</option>
              <option value="3rd year" {{ old('year_level') == '3rd year' ? 'selected' : '' }}>3rd year</option>
              <option value="4th year" {{ old('year_level') == '4th year' ? 'selected' : '' }}>4th year</option>
            </select>
          </div>
          @error('year_level') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- ===================== Card 3: Security ===================== --}}
        <section class="md:col-span-2 lg:col-span-1 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 card">
          <div class="mb-3 flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">3</span>
            <h2 class="text-base font-semibold text-gray-900">Security</h2>
          </div>
          <p class="mb-4 text-xs text-gray-500">Create a strong password (min 12 characters, use upper/lower, number, and symbol).</p>

          {{-- Password --}}
          <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
          <div class="mt-1 relative rounded-lg border border-gray-300 focus-within:ring-2 focus-within:ring-indigo-500 px-3">
            <input id="password" name="password" type="password" autocomplete="new-password" required minlength="12"
                  class="w-full h-11 input-h border-0 bg-transparent text-sm placeholder-gray-400 focus:outline-none focus:ring-0 pr-10"
                  placeholder="Create a strong password"
                  aria-describedby="passwordHelp meterText">
            {{-- Eye toggle (PNG icon) --}}
            @php
              $eye    = asset('images/icons/eye.png');      // <-- change if your icons live elsewhere
              $eyeOff = asset('images/icons/eye-off.png');  // <-- change if your icons live elsewhere
            @endphp
            <button type="button"
                    class="absolute inset-y-0 right-2 inline-flex items-center justify-center px-2 text-gray-500"
                    aria-pressed="false" aria-label="Show password" data-toggle="password">
              <img data-show src="{{ $eye }}"    alt="" class="h-5 w-5">
              <img data-hide src="{{ $eyeOff }}" alt="" class="h-5 w-5 hidden">
            </button>
          </div>
          @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

          {{-- Strength meter (unchanged) --}}
          <div class="mt-2" id="passwordHelp">
            <div class="flex gap-1" aria-hidden="true">
              <span data-meter class="h-1.5 flex-1 rounded bg-gray-200"></span>
              <span data-meter class="h-1.5 flex-1 rounded bg-gray-200"></span>
              <span data-meter class="h-1.5 flex-1 rounded bg-gray-200"></span>
              <span data-meter class="h-1.5 flex-1 rounded bg-gray-200"></span>
            </div>
            <p id="meterText" class="mt-1 text-xs text-gray-600" aria-live="polite">Strength: —</p>
            <p id="lengthHint" class="mt-1 text-xs text-red-500 hidden">Minimum 12 characters</p>
          </div>

          {{-- Confirm --}}
          <label for="password_confirmation" class="mt-4 block text-sm font-medium text-gray-700">Confirm Password</label>
          <div class="mt-1 relative rounded-lg border border-gray-300 focus-within:ring-2 focus-within:ring-indigo-500 px-3">
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="12"
                  class="w-full h-11 input-h border-0 bg-transparent text-sm placeholder-gray-400 focus:outline-none focus:ring-0 pr-10"
                  placeholder="Confirm your password" aria-describedby="confirmErr">
            {{-- Eye toggle (PNG icon) --}}
            <button type="button"
                    class="absolute inset-y-0 right-2 inline-flex items-center justify-center px-2 text-gray-500"
                    aria-pressed="false" aria-label="Show confirm password" data-toggle="password_confirmation">
              <img data-show src="{{ $eye }}"    alt="" class="h-5 w-5">
              <img data-hide src="{{ $eyeOff }}" alt="" class="h-5 w-5 hidden">
            </button>
          </div>
          <p id="confirmErr" class="mt-1 text-xs text-red-600 hidden">Passwords do not match.</p>
        </section>
      </div>

     {{-- ===== Footer Bar (polished baseline + divider) ===== --}}
    <div class="mt-6 border-t border-gray-200 pt-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        {{-- Left: consent + inline login on one baseline --}}
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:gap-4 text-sm text-gray-700">
        <label class="inline-flex items-start gap-2">
            <input type="checkbox" id="agree" class="mt-1 rounded border-gray-300 focus:outline-none focus:ring-0">
            <span class="leading-5">
              I agree to
              <a href="{{ route('privacy.policy') }}" class="text-indigo-600 underline">LumiCHAT’s Privacy Policy</a>
              and understand how my data will be used.
            </span>
        </label>

        <span class="hidden md:inline text-gray-300">•</span>

        <a href="{{ route('login') }}" class="text-indigo-600 hover:underline font-medium">
            Already have an account? Return to Login
        </a>
        </div>

        {{-- Right: primary action --}}
        <button type="submit" id="registerBtn"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 font-medium text-white shadow-sm transition
                    hover:bg-indigo-700 active:bg-indigo-800 disabled:cursor-not-allowed disabled:opacity-50">
          <span data-btn-label>Register Account</span>
          <svg class="hidden h-4 w-4 animate-spin" data-spinner viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
          </svg>
        </button>
    </div>
    </div>

        </div>
      </div>
    </form>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* ===== Auto-compact when viewport height is short ===== */
(function () {
  const SHORT_VH = 760;
  const root = document.documentElement;
  function setCompact() { 
    if (window.innerHeight <= SHORT_VH) root.classList.add('compact'); 
    else root.classList.remove('compact'); 
  }
  setCompact(); 
  window.addEventListener('resize', setCompact);
})();

document.addEventListener('DOMContentLoaded', () => {
  // Show server-side validation errors (if any)
  @if ($errors->any())
    const errs = @json($errors->all());
    Swal.fire({ icon:'error', title:'Registration Failed', html: errs.map(e => `• ${e}`).join('<br>'), confirmButtonColor:'#4f46e5' });
  @endif

  // Enable/disable submit based on Terms
  const agree = document.getElementById('agree');
  const btn   = document.getElementById('registerBtn');
  const spinner = document.querySelector('[data-spinner]');
  const btnLabel = document.querySelector('[data-btn-label]');
  const setBtn = () => btn.disabled = !agree?.checked;
  if (agree) { setBtn(); agree.addEventListener('change', setBtn); }

  // Submit micro-interaction
  btn?.addEventListener('click', () => {
    if (btn.disabled) return;
    btn.setAttribute('aria-busy', 'true');
    spinner?.classList.remove('hidden');
    if (btnLabel) btnLabel.textContent = 'Submitting...';
  });

  // ===== Elements for strength/mismatch logic =====
  const pwd       = document.getElementById('password');
  const confirm   = document.getElementById('password_confirmation');
  const fullName  = document.getElementById('full_name');
  const email     = document.getElementById('email');
  const bars      = [...document.querySelectorAll('[data-meter]')];
  const meterText = document.getElementById('meterText');
  const lengthHint= document.getElementById('lengthHint');
  const confirmErr= document.getElementById('confirmErr');

  // ===== Helpers =====
  function clamp(n, min, max){ return Math.max(min, Math.min(max, n)); }
  function uniqTokens(str){
    return (str || '')
      .toLowerCase()
      .split(/[^a-z0-9]+/i)
      .filter(t => t.length >= 3);
  }
  function hasSequentialRun(s){
    const t = (s || '').toLowerCase();
    if (t.length < 3) return false;
    for (let i=0;i<t.length-2;i++){
      const a=t.charCodeAt(i), b=t.charCodeAt(i+1), c=t.charCodeAt(i+2);
      if ((b===a+1&&c===b+1)||(b===a-1&&c===b-1)) return true;
    }
    return false;
  }
  function hasRepeatedGroup(s){ return /(.)\1{3,}/.test(s || ''); }
  function containsAny(hay, arr){
    const t = (hay || '').toLowerCase();
    return arr.some(x => t.includes(x));
  }

  // ===== Scoring (0–100) =====
  function computeScore(pass, ctx){
    if (!pass) return { score:0, bucket:0, label:'—', color:'text-gray-600', bar:'bg-gray-200', fill:0, lengthTooShort:false };
    const len = pass.length;

    let lengthPts = 0;
    if (len >= 12 && len <= 15) lengthPts = 20;
    else if (len >= 16 && len <= 19) lengthPts = 30;
    else if (len >= 20) lengthPts = 40;

    let classes = 0;
    if (/[a-z]/.test(pass)) classes++;
    if (/[A-Z]/.test(pass)) classes++;
    if (/\d/.test(pass))    classes++;
    if (/[^A-Za-z0-9]/.test(pass)) classes++;
    const varietyPts = [0,0,10,20,30][classes];

    const emailLocal = (ctx.email || '').split('@')[0] || '';
    const nameTokens = uniqTokens(ctx.fullName);
    const containsLocal = emailLocal && pass.toLowerCase().includes(emailLocal.toLowerCase());
    const containsNameToken = nameTokens.some(t => pass.toLowerCase().includes(t));
    const containsPersonal = containsLocal || containsNameToken;

    const bonus = containsPersonal ? 0 : 10;

    let penalties = 0;
    const commonList = ["password","123456","qwerty","letmein","welcome","admin","abc123"];
    if (containsAny(pass, commonList)) penalties -= 30;
    if (hasSequentialRun(pass)) penalties -= 15;
    if (hasRepeatedGroup(pass)) penalties -= 15;
    if (containsAny(pass, ["qwerty","asdf","zxcv"])) penalties -= 15;
    if (containsPersonal) penalties -= 20;
    penalties = Math.max(penalties, -40);

    let score = clamp(lengthPts + varietyPts + bonus + penalties, 0, 100);

    let bucket=0, label='Very weak', color='text-red-600', bar='bg-red-500', fill=1;
    if (score <= 24){ bucket=0; label='Very weak'; color='text-red-600'; bar='bg-red-500'; fill=1; }
    else if (score <= 49){ bucket=1; label='Weak'; color='text-orange-600'; bar='bg-orange-500'; fill=2; }
    else if (score <= 64){ bucket=2; label='Okay'; color='text-amber-600'; bar='bg-amber-500'; fill=3; }
    else if (score <= 79){ bucket=3; label='Good'; color='text-blue-600'; bar='bg-blue-500'; fill=3; }
    else { bucket=4; label='Strong'; color='text-emerald-600'; bar='bg-emerald-500'; fill=4; }

    return { score, bucket, label, color, bar, fill, lengthTooShort: len < 12 };
  }

  function paintStrength(info){
    // bars
    bars.forEach((b,i) => {
      b.classList.remove('bg-red-500','bg-orange-500','bg-amber-500','bg-blue-500','bg-emerald-500','bg-gray-200');
      b.classList.add(i < info.fill ? info.bar : 'bg-gray-200');
    });
    // label
    if (meterText){
      meterText.classList.remove('text-gray-600','text-red-600','text-orange-600','text-amber-600','text-blue-600','text-emerald-600');
      meterText.classList.add(info.color);
      meterText.textContent = `Strength: ${info.label}`;
    }
    // min length hint
    if (lengthHint) lengthHint.classList.toggle('hidden', !info.lengthTooShort);
  }

  function checkConfirm(){
    const mismatch = !!confirm?.value && (confirm.value !== pwd?.value);
    if (confirmErr) confirmErr.classList.toggle('hidden', !mismatch);
    return !mismatch;
  }

  function recompute(){
    if (!pwd) return;
    const info = computeScore(pwd.value, { email: email?.value, fullName: fullName?.value });
    paintStrength(info);
    checkConfirm();
  }

  // Listen broadly so “sanitation” always updates
  [pwd, confirm, email, fullName].forEach(el => {
    if (!el) return;
    ['input','change','keyup','blur'].forEach(evt => el.addEventListener(evt, recompute));
  });
  recompute();

  // === Eye toggles: robust (works even if HTML changes slightly) ===
  function setupToggle(button) {
    const targetId = button.getAttribute('data-toggle');
    const input = document.getElementById(targetId);
    const iconShow = button.querySelector('[data-show]');
    const iconHide = button.querySelector('[data-hide]');
    if (!input) return;

    button.addEventListener('click', (e) => {
      e.preventDefault();
      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      button.setAttribute('aria-pressed', String(!showing));
      // swap icons
      if (iconShow && iconHide) {
        iconShow.classList.toggle('hidden', !showing);
        iconHide.classList.toggle('hidden', showing);
      }
      input.focus({ preventScroll:true });
      // keep strength + mismatch in sync
      if (typeof recompute === 'function') recompute();
    });
  }
  document.querySelectorAll('[data-toggle]').forEach(setupToggle);

  });
</script>
@endsection
