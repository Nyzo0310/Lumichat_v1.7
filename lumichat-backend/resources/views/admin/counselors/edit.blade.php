{{-- resources/views/admin/counselors/edit.blade.php --}}
@extends('layouts.admin')
@section('title','Edit Counselor')

@section('content')
<div class="max-w-5xl mx-auto p-6 space-y-6">

  {{-- Top bar --}}
  <div class="flex items-center justify-between animate-fadeup">
    <a href="{{ route('admin.counselors.index') }}"
       class="text-slate-600 hover:text-slate-800 inline-flex items-center gap-2">
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
      Back
    </a>
    <h1 class="sr-only">Edit Counselor</h1>
  </div>

  @php
    // Build initial availability payload for Alpine (weekday, start_time, end_time)
    $initialSlots = old('availability',
      optional($counselor->availabilities)->map(function($a){
        return [
          'weekday'    => (int) $a->weekday,   // 0=Sun..6=Sat
          'start_time' => $a->start_time,      // may be HH:MM or HH:MM:SS
          'end_time'   => $a->end_time,
        ];
      })->values() ?? []
    );
  @endphp

  {{-- Form Card --}}
  <div x-data="CounselorForm()"
       x-init="init(@js($initialSlots))"
       class="bg-white rounded-2xl shadow-sm border border-slate-200/70 overflow-hidden animate-fadeup">

    <form method="POST" action="{{ route('admin.counselors.update', $counselor) }}" novalidate>
      @csrf
      @method('PUT')

      {{-- ===== Counselor Details ===== --}}
      <div class="p-6 sm:p-8 border-b border-slate-200/70">
        <h2 class="text-lg font-semibold text-slate-800">Counselor Details</h2>
        <p class="text-sm text-slate-500">Edit the counselor’s basic info and status.</p>

        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Full Name <span class="text-rose-600">*</span></label>
            <input name="name" value="{{ old('name', $counselor->name) }}" required
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                   type="text" placeholder="e.g., Juan Dela Cruz">
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Email <span class="text-rose-600">*</span></label>
            <input name="email" value="{{ old('email', $counselor->email) }}" required
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                   type="email" placeholder="name@school.edu">
            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Contact No.</label>
            <input name="phone" value="{{ old('phone', $counselor->phone) }}"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                   type="text" placeholder="09XXXXXXXXX">
            @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Status</label>
            <select name="is_active"
                    class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
              <option value="1" @selected(old('is_active', $counselor->is_active)==1)>Available</option>
              <option value="0" @selected(old('is_active', $counselor->is_active)==0)>Not Available</option>
            </select>
            @error('is_active') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
          </div>
        </div>
      </div>

      {{-- ===== Weekly Availability (Weekdays only) ===== --}}
      <div class="p-6 sm:p-8">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-slate-800">Weekly Availability</h2>
            <p class="text-sm text-slate-500">Weekdays only (Mon–Fri). Pick days, set a time range, then add.</p>
          </div>

          {{-- Shortcuts --}}
          <div class="inline-flex rounded-xl ring-1 ring-slate-200 bg-white overflow-hidden">
            <button type="button" @click="preset('monfri')" class="px-3 py-1.5 text-sm hover:bg-slate-50">Mon–Fri</button>
            <div class="w-px bg-slate-200/80"></div>
            <button type="button" @click="clearSelection()" class="px-3 py-1.5 text-sm hover:bg-rose-50 text-rose-700">Clear</button>
          </div>
        </div>

        <div class="mt-4 rounded-xl border border-slate-200/70 bg-white">
          {{-- Controls --}}
          <div class="p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            {{-- Day chips (Mon–Fri only) --}}
            <div class="flex flex-wrap gap-1.5">
              <template x-for="(d, idx) in days" :key="idx">
                <button type="button"
                        @click="toggleDay(d.value)"
                        class="h-9 px-3 rounded-lg ring-1 text-sm transition"
                        :class="isSelected(d.value)
                                ? 'bg-indigo-600 text-white ring-indigo-600'
                                : 'bg-white text-slate-700 hover:bg-slate-50 ring-slate-200'">
                  <span x-text="d.short"></span>
                </button>
              </template>
            </div>

            {{-- Time + Add --}}
            <div class="flex items-center gap-2 w-full md:w-auto">
              <span class="text-xs font-medium text-slate-600 mr-1 hidden md:inline-block">Time</span>

              <input x-model="range.start" type="time"
                     class="h-10 min-w-[150px] w-[150px] text-center rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"/>

              <span class="text-slate-500">to</span>

              <input x-model="range.end" type="time"
                     class="h-10 min-w-[150px] w-[150px] text-center rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"/>

              <button type="button" @click="bulkAdd()"
                      class="inline-flex items-center gap-1.5 px-3.5 py-2 h-10 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                + Add
              </button>
            </div>
          </div>

          <div class="h-px bg-slate-200/70"></div>

          {{-- Slots list --}}
          <div class="p-4">
            <template x-if="!slots.length">
              <div class="px-4 py-8 text-center text-slate-500">
                No availability added yet.
              </div>
            </template>

            <div class="grid gap-2.5" x-show="slots.length">
              <template x-for="(row, i) in slots" :key="i">
                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2.5">
                  <div class="grid grid-cols-12 gap-2 items-center">
                    {{-- Day --}}
                    <div class="col-span-12 sm:col-span-3 lg:col-span-2">
                      <span class="inline-flex items-center h-8 px-3 rounded-full text-xs font-semibold
                                   bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200 whitespace-nowrap"
                            x-text="dayLabel(row.weekday)"></span>
                    </div>

                    {{-- Times --}}
                    <div class="col-span-12 sm:col-span-6 lg:col-span-7 grid grid-cols-9 gap-2 items-center">
                      <div class="col-span-4">
                        <label class="text-[11px] text-slate-500">Start</label>
                        <input type="time" x-model="row.start_time"
                               :name="`availability[${i}][start_time]`"
                               class="mt-0.5 h-9 w-full min-w-[150px] text-center rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                      </div>
                      <div class="col-span-1 text-center text-slate-500 mt-4">–</div>
                      <div class="col-span-4">
                        <label class="text-[11px] text-slate-500">End</label>
                        <input type="time" x-model="row.end_time"
                               :name="`availability[${i}][end_time]`"
                               class="mt-0.5 h-9 w-full min-w-[150px] text-center rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                      </div>
                      <input type="hidden" :name="`availability[${i}][weekday]`" :value="row.weekday">
                    </div>

                    {{-- Actions --}}
                    <div class="col-span-12 sm:col-span-3 lg:col-span-3 flex justify-start sm:justify-end gap-2">
                      <button type="button" @click="duplicate(i)"
                              class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <rect x="9" y="9" width="10" height="10" rx="2" stroke-width="1.5"/>
                          <rect x="5" y="5" width="10" height="10" rx="2" stroke-width="1.5"/>
                        </svg>
                        Duplicate
                      </button>
                      <button type="button" @click="remove(i)"
                              class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 ring-1 ring-rose-200 text-xs">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path d="M19 7l-1 12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 7m3 0V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M4 7h16"
                                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Remove
                      </button>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            @if($errors->has('availability') || $errors->has('availability.*.start_time') || $errors->has('availability.*.end_time'))
              <p class="mt-3 text-xs text-rose-600">Please check your availability entries and time order.</p>
            @endif
          </div>
        </div>
      </div>

      {{-- Footer actions --}}
      <div class="px-6 sm:px-8 py-4 bg-slate-50 border-t border-slate-200/70 flex items-center justify-end gap-3">
        <a href="{{ route('admin.counselors.index') }}"
           class="px-4 py-2 rounded-xl bg-white ring-1 ring-slate-200 text-slate-700 hover:bg-slate-100">Cancel</a>
        <button type="submit"
                class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-medium">
          Update
        </button>
      </div>
    </form>
  </div>
</div>

{{-- Alpine.js (if not loaded globally) --}}
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  // ===== Server feedback (SweetAlert) =====
  @if (session('success'))
    Swal.fire({ icon: 'success', title: 'Updated', text: @json(session('success')), confirmButtonColor: '#4f46e5' });
  @endif
  @if (session('error'))
    Swal.fire({ icon: 'error', title: 'Error', text: @json(session('error')), confirmButtonColor: '#ef4444' });
  @endif
  @if ($errors->any())
    Swal.fire({
      icon: 'error',
      title: 'Please fix the following',
      html: `{!! implode('<br>', $errors->all()) !!}`,
      confirmButtonColor: '#ef4444'
    });
  @endif

  // ===== Alpine component (Weekdays only, robust time parsing) =====
  function CounselorForm() {
    return {
      // Only weekdays (Mon..Fri)
      days: [
        { value:1, short:'Mon', long:'Monday' },
        { value:2, short:'Tue', long:'Tuesday' },
        { value:3, short:'Wed', long:'Wednesday' },
        { value:4, short:'Thu', long:'Thursday' },
        { value:5, short:'Fri', long:'Friday' },
      ],
      selectedDays: [1,2,3,4,5],
      range: { start: '09:00', end: '12:00' },
      slots: [],

      // Convert "10:00 AM", "17:30", or "09:00:00" => "10:00" / "17:30" / "09:00"
      to24(t) {
        if (!t) return '';
        t = (''+t).trim();

        // Already 24h, with or without seconds
        const mmss = t.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/);
        if (mmss) {
          const hh = String(mmss[1]).padStart(2,'0');
          const mm = mmss[2];
          return `${hh}:${mm}`;
        }

        // h:mm AM/PM
        const ampm = t.match(/^(\d{1,2}):(\d{2})\s*([ap]m)$/i);
        if (ampm) {
          let hh = parseInt(ampm[1],10), mm = ampm[2], ap = ampm[3].toLowerCase();
          if (ap==='pm' && hh !== 12) hh += 12;
          if (ap==='am' && hh === 12) hh = 0;
          return `${String(hh).padStart(2,'0')}:${mm}`;
        }

        return '';
      },

      init(oldOrExistingSlots) {
        const allowed = new Set([1,2,3,4,5]); // Mon..Fri
        if (Array.isArray(oldOrExistingSlots) && oldOrExistingSlots.length) {
          this.slots = oldOrExistingSlots
            .filter(s => allowed.has(Number(s.weekday))) // ignore weekends in UI
            .map(s => ({
              weekday: Number(s.weekday),
              start_time: this.to24(s.start_time),
              end_time: this.to24(s.end_time),
            }))
            .filter(s => s.start_time && s.end_time);
          this.sortSlots();
        }
        // Normalize default range
        this.range.start = this.to24(this.range.start) || '09:00';
        this.range.end   = this.to24(this.range.end)   || '12:00';
      },

      dayLabel(wd) {
        const map = {1:'Monday',2:'Tuesday',3:'Wednesday',4:'Thursday',5:'Friday'};
        return map[wd] || '';
      },
      isSelected(d) { return this.selectedDays.includes(d); },
      toggleDay(d) {
        this.isSelected(d)
          ? this.selectedDays = this.selectedDays.filter(x=>x!==d)
          : this.selectedDays = [...this.selectedDays, d];
        this.selectedDays.sort((a,b)=>a-b);
      },
      preset(type) { if (type==='monfri') this.selectedDays = [1,2,3,4,5]; },
      clearSelection() { this.selectedDays = []; },
      sortSlots() { this.slots.sort((a,b)=> a.weekday - b.weekday || a.start_time.localeCompare(b.start_time)); },

      // Add slots with client validation
      bulkAdd() {
        const start = this.to24(this.range.start), end = this.to24(this.range.end);
        if (!this.selectedDays.length || !start || !end) {
          Swal.fire({icon:'warning', title:'Incomplete', text:'Pick weekday(s) and set a time range first.', confirmButtonColor:'#4f46e5'});
          return;
        }
        if (end <= start) {
          Swal.fire({icon:'error', title:'Time invalid', text:'End time must be after start time.', confirmButtonColor:'#ef4444'});
          return;
        }
        this.selectedDays.forEach(d => {
          const exists = this.slots.some(s => s.weekday===d && s.start_time===start && s.end_time===end);
          if (!exists) this.slots.push({ weekday:d, start_time:start, end_time:end });
        });
        this.sortSlots();
      },

      remove(i) { this.slots.splice(i,1); },
      duplicate(i) { const item = this.slots[i]; this.slots.splice(i+1, 0, { ...item }); },
    }
  }
</script>
@endsection
