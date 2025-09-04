{{-- resources/views/admin/counselors/create.blade.php --}}
@extends('layouts.admin')
@section('title','Add Counselor')

@section('content')
<div class="max-w-5xl mx-auto p-6 space-y-6">

  {{-- Top bar --}}
  <div class="flex items-center justify-between">
    <a href="{{ route('admin.counselors.index') }}"
       class="text-slate-600 hover:text-slate-800 inline-flex items-center gap-2">
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
      Back
    </a>
    <h1 class="sr-only">Add Counselor</h1>
  </div>

  {{-- Form Card --}}
  <div x-data="CounselorForm()"
       x-init="init({{ json_encode(old('availability', [])) }})"
       class="bg-white rounded-2xl shadow-sm border border-slate-200/70 overflow-hidden">

    <form method="POST" action="{{ route('admin.counselors.store') }}" novalidate>
      @csrf

      {{-- ========== Counselor Details ========== --}}
      <div class="p-6 sm:p-8 border-b border-slate-200/70">
        <h2 class="text-lg font-semibold text-slate-800">Counselor Details</h2>
        <p class="text-sm text-slate-500">Add the counselor’s basic info and status.</p>

        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Full Name <span class="text-rose-600">*</span></label>
            <input name="name" value="{{ old('name') }}" required
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                   type="text" placeholder="e.g., Juan Dela Cruz">
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Email <span class="text-rose-600">*</span></label>
            <input name="email" value="{{ old('email') }}" required
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                   type="email" placeholder="name@school.edu">
            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Contact No.</label>
            <input name="phone" value="{{ old('phone') }}"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                   type="text" placeholder="09XXXXXXXXX">
            @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Status</label>
            <select name="is_active"
                    class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
              <option value="1" @selected(old('is_active',1)==1)>Available</option>
              <option value="0" @selected(old('is_active',1)==0)>Not Available</option>
            </select>
            @error('is_active') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
          </div>
        </div>
      </div>

      {{-- ========== Weekly Availability (clean + aligned + wider inputs) ========== --}}
      <div class="p-6 sm:p-8">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-slate-800">Weekly Availability</h2>
            <p class="text-sm text-slate-500">Pick days, set a time range, and add to multiple days in one click.</p>
          </div>

          {{-- Shortcuts --}}
          <div class="inline-flex rounded-xl ring-1 ring-slate-200 bg-white overflow-hidden">
            <button type="button" @click="preset('monfri')" class="px-3 py-1.5 text-sm hover:bg-slate-50">Mon–Fri</button>
            <div class="w-px bg-slate-200/80"></div>
            <button type="button" @click="preset('alldays')" class="px-3 py-1.5 text-sm hover:bg-slate-50">All days</button>
            <div class="w-px bg-slate-200/80"></div>
            <button type="button" @click="clearSelection()" class="px-3 py-1.5 text-sm hover:bg-rose-50 text-rose-700">Clear</button>
          </div>
        </div>

        <div class="mt-4 rounded-xl border border-slate-200/70 bg-white">
          {{-- Controls row --}}
          <div class="p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            {{-- Day chips (left) --}}
            <div class="flex flex-wrap gap-1.5">
              <template x-for="(d, idx) in days" :key="idx">
                <button type="button"
                        @click="toggleDay(idx)"
                        class="h-9 px-3 rounded-lg ring-1 text-sm transition"
                        :class="isSelected(idx)
                                ? 'bg-indigo-600 text-white ring-indigo-600'
                                : 'bg-white text-slate-700 hover:bg-slate-50 ring-slate-200'">
                  <span x-text="d.short"></span>
                </button>
              </template>
            </div>

            {{-- Time + Add (right) --}}
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
                            x-text="days[row.weekday].long"></span>
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
          Save
        </button>
      </div>
    </form>
  </div>
</div>

{{-- Alpine.js (if not already included globally) --}}
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<script>
  function CounselorForm() {
    return {
      days: [
        { short:'Su', long:'Sunday' },
        { short:'Mon', long:'Monday' },
        { short:'Tue', long:'Tuesday' },
        { short:'Wed', long:'Wednesday' },
        { short:'Thu', long:'Thursday' },
        { short:'Fri', long:'Friday' },
        { short:'Sat', long:'Saturday' },
      ],
      selectedDays: [1,2,3,4,5],  // default Mon–Fri
      range: { start: '09:00', end: '12:00' },
      slots: [],

      init(oldSlots) {
        if (Array.isArray(oldSlots) && oldSlots.length) {
          this.slots = oldSlots.map(s => ({
            weekday: Number(s.weekday),
            start_time: s.start_time,
            end_time: s.end_time
          }));
        }
      },

      // Helpers for day chips
      isSelected(d) { return this.selectedDays.includes(d); },
      toggleDay(d) {
        this.isSelected(d)
          ? this.selectedDays = this.selectedDays.filter(x => x !== d)
          : this.selectedDays.push(d);
        this.selectedDays.sort((a,b)=>a-b);
      },
      preset(type) {
        if (type === 'monfri') this.selectedDays = [1,2,3,4,5];
        if (type === 'alldays') this.selectedDays = [0,1,2,3,4,5,6];
      },
      clearSelection() { this.selectedDays = []; },

      // Add slots
      bulkAdd() {
        if (!this.selectedDays.length || !this.range.start || !this.range.end) return;
        if (this.range.end <= this.range.start) { alert('End time must be after start time.'); return; }

        const start = this.range.start, end = this.range.end;
        this.selectedDays.forEach(d => {
          const exists = this.slots.some(s => s.weekday===d && s.start_time===start && s.end_time===end);
          if (!exists) this.slots.push({ weekday:d, start_time:start, end_time:end });
        });
        this.slots.sort((a,b)=> a.weekday - b.weekday || a.start_time.localeCompare(b.start_time));
      },

      // Row actions
      remove(i) { this.slots.splice(i,1); },
      duplicate(i) {
        const item = this.slots[i];
        this.slots.splice(i+1, 0, { ...item });
      },
    }
  }
</script>
@endsection
