@php
  // Days used by both Quick Planner and slot rows
  $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

  // Rehydrate old input or prefill when editing
  $existing = old('availability', isset($counselor)
      ? $counselor->availabilities->map(fn($s)=>[
          'weekday'=>$s->weekday,
          'start_time'=>substr($s->start_time,0,5),
          'end_time'=>substr($s->end_time,0,5),
        ])->values()->toArray()
      : []);
@endphp

<div class="max-w-3xl mx-auto p-6 space-y-6">
  <a href="{{ route('admin.counselors.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Back</a>

  {{-- Validation errors --}}
  @if ($errors->any())
    <div class="p-3 rounded-lg bg-red-50 text-red-700 border border-red-200">
      <ul class="list-disc ml-5 text-sm">
        @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
      </ul>
    </div>
  @endif

  <form action="{{ $route }}" method="POST" class="space-y-6">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    {{-- Counselor Info --}}
    <div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm text-gray-600 mb-1">Full Name *</label>
          <input type="text" name="name"
                 value="{{ old('name', $counselor->name ?? '') }}"
                 class="w-full rounded-lg border-gray-300" required>
        </div>
        <div>
          <label class="block text-sm text-gray-600 mb-1">Email *</label>
          <input type="email" name="email"
                 value="{{ old('email', $counselor->email ?? '') }}"
                 class="w-full rounded-lg border-gray-300" required>
        </div>
        <div>
          <label class="block text-sm text-gray-600 mb-1">Contact No.</label>
          <input type="text" name="phone"
                 value="{{ old('phone', $counselor->phone ?? '') }}"
                 class="w-full rounded-lg border-gray-300">
        </div>
        <div>
          <label class="block text-sm text-gray-600 mb-1">Status</label>
          <select name="is_active" class="w-full rounded-lg border-gray-300">
            <option value="1" {{ old('is_active', $counselor->is_active ?? 1) ? 'selected':'' }}>Available</option>
            <option value="0" {{ old('is_active', $counselor->is_active ?? 1) ? '' : 'selected' }}>Not Available</option>
          </select>
        </div>
      </div>
    </div>

    {{-- Availability builder (Quick Planner) --}}
    <div class="bg-white rounded-xl shadow-sm border p-6 space-y-5">
      <div class="flex items-center justify-between">
        <h3 class="font-semibold">Weekly Availability</h3>
        <div class="flex items-center gap-2">
          <button type="button" id="presetWeekdays"
                  class="px-3 py-1.5 rounded-lg border hover:bg-gray-50">Mon–Fri</button>
          <button type="button" id="presetAllDays"
                  class="px-3 py-1.5 rounded-lg border hover:bg-gray-50">All days</button>
          <button type="button" id="clearAllBtn"
                  class="px-3 py-1.5 rounded-lg border border-red-200 text-red-700 hover:bg-red-50">Clear all</button>
        </div>
      </div>

      {{-- Quick planner controls --}}
      <div class="rounded-lg border bg-gray-50 p-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div>
            <p class="text-sm font-medium mb-2">Select days</p>
            <div class="grid grid-cols-3 gap-2 text-sm">
              @foreach($days as $idx => $label)
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" class="day-check rounded" value="{{ $idx }}">
                  <span>{{ $label }}</span>
                </label>
              @endforeach
            </div>
          </div>

          <div>
            <p class="text-sm font-medium mb-2">Time range</p>
            <div class="grid grid-cols-2 gap-2">
              <input type="time" id="qpStart" class="w-full rounded-lg border-gray-300" />
              <input type="time" id="qpEnd" class="w-full rounded-lg border-gray-300" />
            </div>
            <p class="text-xs text-gray-500 mt-1">Example: 09:00 → 12:00</p>
          </div>

          <div class="flex items-end">
            <button type="button" id="addQuickBtn"
                    class="w-full px-3 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
              Add to selected days
            </button>
          </div>
        </div>
      </div>

      {{-- Existing slot rows --}}
      <div id="slots" class="space-y-3">
        @foreach ($existing as $i => $slot)
          <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end slot-row">
            <div>
              <label class="block text-sm text-gray-600 mb-1">Day</label>
              <select name="availability[{{ $i }}][weekday]" class="w-full rounded-lg border-gray-300">
                @foreach($days as $idx=>$label)
                  <option value="{{ $idx }}" {{ $slot['weekday']==$idx?'selected':'' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-sm text-gray-600 mb-1">Start</label>
              <input type="time" name="availability[{{ $i }}][start_time]"
                     value="{{ $slot['start_time'] }}" class="w-full rounded-lg border-gray-300" required>
            </div>
            <div>
              <label class="block text-sm text-gray-600 mb-1">End</label>
              <input type="time" name="availability[{{ $i }}][end_time]"
                     value="{{ $slot['end_time'] }}" class="w-full rounded-lg border-gray-300" required>
            </div>
            <button type="button"
                    class="remove-slot px-3 py-2 rounded-lg border border-red-200 text-red-700 hover:bg-red-50">
              Remove
            </button>
          </div>
        @endforeach
      </div>

      {{-- Template for new rows --}}
      <template id="slotTemplate">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end slot-row">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Day</label>
            <select name="availability[__i__][weekday]" class="w-full rounded-lg border-gray-300">
              @foreach($days as $idx=>$label)
                <option value="{{ $idx }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Start</label>
            <input type="time" name="availability[__i__][start_time]" class="w-full rounded-lg border-gray-300" required>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">End</label>
            <input type="time" name="availability[__i__][end_time]" class="w-full rounded-lg border-gray-300" required>
          </div>
          <button type="button"
                  class="remove-slot px-3 py-2 rounded-lg border border-red-200 text-red-700 hover:bg-red-50">
            Remove
          </button>
        </div>
      </template>

      {{-- Planner & rows script --}}
      <script>
        (() => {
          const slots = document.getElementById('slots');
          let i = document.querySelectorAll('#slots .slot-row').length || 0;

          function addSlot(dayIdx, start, end) {
            if (!start || !end) return;
            const tpl = document.getElementById('slotTemplate').innerHTML.replaceAll('__i__', i++);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = tpl.trim();
            const row = wrapper.firstChild;

            row.querySelector('select[name^="availability"]').value = dayIdx;
            row.querySelector('input[name$="[start_time]"]').value = start;
            row.querySelector('input[name$="[end_time]"]').value = end;

            slots.appendChild(row);
          }

          // Remove row
          slots?.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-slot')) {
              e.target.closest('.slot-row').remove();
            }
          });

          // Quick planner
          const checks = Array.from(document.querySelectorAll('.day-check'));
          const qpStart = document.getElementById('qpStart');
          const qpEnd   = document.getElementById('qpEnd');

          document.getElementById('addQuickBtn')?.addEventListener('click', () => {
            const selectedDays = checks.filter(c => c.checked).map(c => parseInt(c.value, 10));
            if (selectedDays.length === 0) { alert('Select at least one day.'); return; }
            if (!qpStart.value || !qpEnd.value) { alert('Provide both start and end time.'); return; }
            selectedDays.forEach(d => addSlot(d, qpStart.value, qpEnd.value));
          });

          document.getElementById('presetWeekdays')?.addEventListener('click', () => {
            checks.forEach((c, idx) => c.checked = (idx>=1 && idx<=5)); // Mon..Fri
          });

          document.getElementById('presetAllDays')?.addEventListener('click', () => {
            checks.forEach(c => c.checked = true);
          });

          document.getElementById('clearAllBtn')?.addEventListener('click', () => {
            document.querySelectorAll('#slots .slot-row').forEach(r => r.remove());
          });
        })();
      </script>
    </div>

    <div class="flex justify-end">
      <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
        Save
      </button>
    </div>
  </form>
</div>
