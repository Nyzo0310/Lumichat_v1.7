@php
    $reg = $registration ?? null;

    $courses = [
        'BSIT'      => 'College of Information Technology',
        'EDUC'      => 'College of Education',
        'CAS'       => 'College of Arts and Sciences',
        'CRIM'      => 'College of Criminal Justice and Public Safety',
        'BLIS'      => 'College of Library Information Science',
        'MIDWIFERY' => 'College of Midwifery',
        'BSHM'      => 'College of Hospitality Management',
        'BSBA'      => 'College of Business',
    ];

    $yearLevels = [
        '1st year' => '1st year',
        '2nd year' => '2nd year',
        '3rd year' => '3rd year',
        '4th year' => '4th year',
    ];
@endphp

<div class="space-y-6">

  {{-- ================= READ VIEW ================= --}}
  <section data-edit-profile-view>
    {{-- Header aligned with the right card --}}
    <div class="form-head">
      <div>
        <h3 class="title-dynamic text-lg font-semibold">Profile Information</h3>
        <p class="muted-dynamic text-sm">
          {{ __("Update your account’s profile information and email address.") }}
        </p>
      </div>

      <button type="button" data-edit-profile-btn class="btn-primary btn-size btn-press">
        Edit profile
      </button>
    </div>

    {{-- Nicely aligned label/value list --}}
    <dl class="meta-grid">
      <div class="row">
        <dt>Name</dt>
        <dd class="title-dynamic">{{ $user->name }}</dd>
      </div>

      <div class="row">
        <dt>Email</dt>
        <dd class="title-dynamic break-all">{{ $user->email }}</dd>
      </div>

      <div class="row">
        <dt>Course</dt>
        <dd class="title-dynamic">{{ $reg->course ?? '—' }}</dd>
      </div>

      <div class="row">
        <dt>Year level</dt>
        <dd class="title-dynamic">{{ $reg->year_level ?? '—' }}</dd>
      </div>

      <div class="row">
        <dt>Contact number</dt>
        <dd class="title-dynamic">{{ $reg->contact_number ?? '—' }}</dd>
      </div>
    </dl>
  </section>

  {{-- ================= EDIT FORM ================= --}}
  <section data-edit-profile-form class="hidden">
    <div class="form-head">
      <div>
        <h3 class="title-dynamic text-lg font-semibold">Edit Profile</h3>
        <p class="muted-dynamic text-sm">
          Make changes to your details, then save.
        </p>
      </div>
      {{-- Spacer keeps header height equal to READ view --}}
      <span class="btn-size invisible" aria-hidden="true"></span>
    </div>

    <form method="POST" action="{{ route('profile.update') }}" class="space-y-6" novalidate>
      @csrf
      @method('PUT')

      <div class="grid gap-5 sm:grid-cols-2">
        {{-- Name --}}
        <div>
          <label for="edit-name" class="block text-sm font-medium title-dynamic">Name</label>
          <input
            id="edit-name"
            name="name"
            type="text"
            class="mt-1 w-full input-dynamic"
            value="{{ old('name', $user->name) }}"
            required minlength="2" maxlength="100"
            autocomplete="name" autocapitalize="words"
            aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
          >
          @error('name') <p class="text-sm text-rose-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Email --}}
        <div>
          <label for="edit-email" class="block text-sm font-medium title-dynamic">Email</label>
          <input
            id="edit-email"
            name="email"
            type="email"
            class="mt-1 w-full input-dynamic break-all"
            value="{{ old('email', $user->email) }}"
            required maxlength="255"
            autocomplete="email" inputmode="email"
            aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
          >
          @error('email') <p class="text-sm text-rose-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Course --}}
        <div>
          <label for="edit-course" class="block text-sm font-medium title-dynamic">Course</label>
          <select
            id="edit-course"
            name="course"
            class="mt-1 w-full input-dynamic"
            aria-invalid="{{ $errors->has('course') ? 'true' : 'false' }}"
          >
            <option value="" disabled {{ old('course', $reg->course ?? '') === '' ? 'selected' : '' }}>
              Select your course
            </option>
            @foreach($courses as $value => $label)
              <option value="{{ $value }}" {{ old('course', $reg->course ?? '') === $value ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
          @error('course') <p class="text-sm text-rose-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Year level --}}
        <div>
          <label for="edit-year" class="block text-sm font-medium title-dynamic">Year level</label>
          <select
            id="edit-year"
            name="year_level"
            class="mt-1 w-full input-dynamic"
            aria-invalid="{{ $errors->has('year_level') ? 'true' : 'false' }}"
          >
            <option value="" disabled {{ old('year_level', $reg->year_level ?? '') === '' ? 'selected' : '' }}>
              Select your year level
            </option>
            @foreach($yearLevels as $value => $label)
              <option value="{{ $value }}" {{ old('year_level', $reg->year_level ?? '') === $value ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
          @error('year_level') <p class="text-sm text-rose-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Contact number --}}
        <div class="sm:col-span-2">
          <label for="edit-phone" class="block text-sm font-medium title-dynamic">Contact number</label>
          <input
            id="edit-phone"
            name="contact_number"
            type="text"
            class="mt-1 w-full input-dynamic"
            value="{{ old('contact_number', $reg->contact_number ?? '') }}"
            inputmode="numeric" pattern="\d*" minlength="10" maxlength="15"
            aria-describedby="phone-help"
            aria-invalid="{{ $errors->has('contact_number') ? 'true' : 'false' }}"
          >
          <p id="phone-help" class="muted-dynamic text-xs mt-1">
            Digits only (10–15). PH 09… will be stored as 639…
          </p>
          @error('contact_number') <p class="text-sm text-rose-500 mt-1">{{ $message }}</p> @enderror
        </div>
      </div>

      <div class="flex items-center gap-3">
        <button type="submit" class="btn-primary btn-press">Save changes</button>
        <button type="button" data-edit-cancel class="btn-secondary">Cancel</button>
      </div>
    </form>
  </section>
</div>

@push('styles')
<style>
  /* === Equal header heights (matches Update Password) === */
  .form-head{
    display:flex; align-items:center; justify-content:space-between;
    gap:.75rem; margin-bottom:.75rem; min-height:44px; /* ~ button height */
  }
  /* Make the edit button footprint stable (also used as spacer) */
  .btn-size{
    display:inline-flex; align-items:center; justify-content:center;
    height:40px; padding:0 1rem; min-width:116px; border-radius:.75rem;
  }
  .btn-press{ transition: transform .12s ease, box-shadow .12s ease; }
  .btn-press:active{ transform: translateY(1px) scale(.985); }

  /* === Beautiful label/value list === */
  .meta-grid{
    display:grid; gap:.25rem;
    padding:.25rem 0;
  }
  .meta-grid .row{
    display:grid; align-items:start;
    grid-template-columns: 1fr; /* mobile stack */
    row-gap:.125rem;
    padding:.75rem 0;
    border-top: 1px solid rgb(229 231 235 / .7); /* gray-200-ish */
  }
  .meta-grid .row:first-child{ border-top: 0; }

  .meta-grid dt{
    font-size:.72rem; letter-spacing:.04em; text-transform:uppercase;
    color: rgb(107 114 128); /* gray-500 */
  }
  .dark .meta-grid dt{ color: rgb(156 163 175); } /* gray-400 */

  .meta-grid dd{
    font-weight: 600;
  }

  /* Two-column alignment on sm+ */
  @media (min-width: 640px){
    .meta-grid .row{
      grid-template-columns: 200px minmax(0,1fr);
      column-gap: 1.25rem;
    }
    .meta-grid dt{ padding-top:.1rem; }
  }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const view    = document.querySelector('[data-edit-profile-view]');
  const form    = document.querySelector('[data-edit-profile-form]');
  const openBtn = document.querySelector('[data-edit-profile-btn]');
  const cancel  = document.querySelector('[data-edit-cancel]');
  const nameEl  = document.getElementById('edit-name');
  const phoneEl = document.getElementById('edit-phone');

  const openEdit = () => {
    view?.classList.add('hidden');
    form?.classList.remove('hidden');
    requestAnimationFrame(() => nameEl?.focus());
  };
  const closeEdit = () => {
    form?.classList.add('hidden');
    view?.classList.remove('hidden');
  };

  openBtn?.addEventListener('click', openEdit);
  cancel?.addEventListener('click', closeEdit);

  // Auto-open if validation failed
  if (@json($errors->any())) openEdit();

  // Gentle phone normalization for PH numbers (UX sugar)
  phoneEl?.addEventListener('blur', () => {
    if (!phoneEl.value) return;
    let digits = (phoneEl.value || '').replace(/\D+/g, '');
    if (digits.startsWith('09'))       digits = '63' + digits.slice(1);
    else if (digits.startsWith('9') && digits.length === 10) digits = '63' + digits;
    else if (digits.startsWith('00'))  digits = digits.slice(2);
    phoneEl.value = digits;
  });
});
</script>
@endpush
