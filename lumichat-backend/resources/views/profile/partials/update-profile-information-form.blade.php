@php
    $reg = $registration ?? null;

    $courses = [
        'BSIT' => 'College of Information Technology',
        'EDUC' => 'College of Education',
        'CAS' => 'College of Arts and Sciences',
        'CRIM' => 'College of Criminal Justice and Public Safety',
        'BLIS' => 'College of Library Information Science',
        'MIDWIFERY' => 'College of Midwifery',
        'BSHM' => 'College of Hospitality Management',
        'BSBA' => 'College of Business',
    ];

    $yearLevels = [
        '1st year' => '1st year',
        '2nd year' => '2nd year',
        '3rd year' => '3rd year',
        '4th year' => '4th year',
    ];
@endphp

<div class="space-y-4">
  {{-- READ-ONLY CARD --}}
  <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm p-6 relative">
    <div class="flex items-start justify-between mb-4">
      <div>
        <h3 class="title-dynamic text-lg font-semibold">Profile Information</h3>
        <p class="muted-dynamic text-sm">Update your account’s profile information and email address.</p>
      </div>

      <button type="button"
              data-edit-profile-btn
              class="btn-primary">
        Edit profile
      </button>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
      <div>
        <div class="muted-dynamic text-xs uppercase tracking-wide">Name</div>
        <div class="mt-1 font-medium title-dynamic">{{ $user->name }}</div>
      </div>
      <div>
        <div class="muted-dynamic text-xs uppercase tracking-wide">Email</div>
        <div class="mt-1 font-medium title-dynamic break-all">{{ $user->email }}</div>
      </div>
      <div>
        <div class="muted-dynamic text-xs uppercase tracking-wide">Course</div>
        <div class="mt-1 font-medium title-dynamic">{{ $reg->course ?? '—' }}</div>
      </div>
      <div>
        <div class="muted-dynamic text-xs uppercase tracking-wide">Year Level</div>
        <div class="mt-1 font-medium title-dynamic">{{ $reg->year_level ?? '—' }}</div>
      </div>
      <div class="sm:col-span-2">
        <div class="muted-dynamic text-xs uppercase tracking-wide">Contact Number</div>
        <div class="mt-1 font-medium title-dynamic">{{ $reg->contact_number ?? '—' }}</div>
      </div>
    </div>
  </div>

  {{-- EDIT FORM (HIDDEN UNTIL CLICK) --}}
  <div data-edit-profile-form class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm p-6 hidden">
    <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
      @csrf
      @method('PUT')

      <div class="grid gap-5 sm:grid-cols-2">
        {{-- Name --}}
        <div>
          <label class="block text-sm font-medium title-dynamic">Name</label>
          <input id="edit-name" name="name" type="text"
                 class="mt-1 w-full input-dynamic"
                 value="{{ old('name', $user->name) }}" required>
          @error('name') <p class="text-sm text-rose-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Email --}}
        <div>
          <label class="block text-sm font-medium title-dynamic">Email</label>
          <input name="email" type="email"
                 class="mt-1 w-full input-dynamic break-all"
                 value="{{ old('email', $user->email) }}" required>
          @error('email') <p class="text-sm text-rose-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Course (select) --}}
        <div>
          <label class="block text-sm font-medium title-dynamic">Course</label>
          <select name="course" class="mt-1 w-full input-dynamic">
            <option value="" disabled {{ old('course', $reg->course ?? '') === '' ? 'selected' : '' }}>
              Select your course
            </option>
            @foreach($courses as $value => $label)
              <option value="{{ $value }}" {{ old('course', $reg->course ?? '') === $value ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Year Level (select) --}}
        <div>
          <label class="block text-sm font-medium title-dynamic">Year Level</label>
          <select name="year_level" class="mt-1 w-full input-dynamic">
            <option value="" disabled {{ old('year_level', $reg->year_level ?? '') === '' ? 'selected' : '' }}>
              Select your year level
            </option>
            @foreach($yearLevels as $value => $label)
              <option value="{{ $value }}" {{ old('year_level', $reg->year_level ?? '') === $value ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Contact Number --}}
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium title-dynamic">Contact Number</label>
          <input name="contact_number" type="text"
                 class="mt-1 w-full input-dynamic"
                 value="{{ old('contact_number', $reg->contact_number ?? '') }}">
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="btn-primary">
          Save changes
        </button>

        <button type="button" data-edit-cancel class="btn-secondary">
          Cancel
        </button>
      </div>
    </form>
  </div>
</div>
