@extends('layouts.app')
@section('title','Self-Assessment')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="max-w-5xl mx-auto py-8 px-4">
  <!-- Header band -->
  <div class="rounded-2xl bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-600 text-white p-8 shadow-lg">
    <h1 class="text-3xl font-extrabold">Quick Check-In</h1>
    <p class="opacity-90 mt-2">This takes less than a minute. You can also skip and start chatting.</p>
  </div>

  <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Tips -->
    <aside class="lg:col-span-1">
      <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h3 class="font-semibold mb-3">Tips</h3>
        <ul class="text-sm space-y-2 text-gray-600">
          <li>• Be honest — it helps us help you.</li>
          <li>• Your answers are private.</li>
          <li>• You can skip anytime.</li>
        </ul>
      </div>
    </aside>

    <!-- Form -->
    <section class="lg:col-span-2">
      <form id="saForm" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <label class="block text-sm font-medium text-gray-700 mb-2">Mood</label>
        <div class="flex flex-wrap gap-2 mb-4">
          @foreach($moods as $m)
            <button type="button"
              data-mood="{{ $m }}"
              class="sa-chip px-4 py-2 rounded-full border border-gray-200 text-gray-700 hover:border-indigo-300 hover:bg-indigo-50 transition">
              {{ $m }}
            </button>
          @endforeach
        </div>

        <input type="hidden" name="mood" id="mood" value="Happy" />

        <label for="note" class="block text-sm font-medium text-gray-700 mb-2">Anything you want to add? (optional)</label>
        <textarea id="note" name="note" rows="4"
          class="w-full rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Type here..."></textarea>

        <div class="mt-6 flex flex-wrap items-center gap-3">
          <a href="{{ route('student.self-assessment.skip') }}"
             class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
            Skip
          </a>

          <button type="submit"
            class="px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow">
            Continue to chat
          </button>
        </div>
      </form>
    </section>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const chips = document.querySelectorAll('.sa-chip');
  const moodInput = document.getElementById('mood');

  // default select first
  if (chips.length) chips[0].classList.add('ring-2','ring-indigo-500','bg-indigo-50');

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      chips.forEach(c => c.classList.remove('ring-2','ring-indigo-500','bg-indigo-50'));
      chip.classList.add('ring-2','ring-indigo-500','bg-indigo-50');
      moodInput.value = chip.dataset.mood;
    });
  });

  const form = document.getElementById('saForm');
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const payload = {
      mood: document.getElementById('mood').value,
      note: document.getElementById('note').value
    };

    try {
      const res = await fetch(@json(route('student.self-assessment.store')), {
        method: 'POST',
        headers: {
          'Content-Type':'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (data?.ok) {
        await Swal.fire({
          icon: 'success',
          title: 'Thanks!',
          text: 'Your check-in has been recorded.',
          confirmButtonText: 'Go to chat'
        });
        window.location.href = data.goto ?? @json(route('chat.index'));
      } else {
        throw new Error('Failed to save');
      }

    } catch (err) {
      await Swal.fire({
        icon: 'error',
        title: 'Save failed',
        text: 'Please try again. If this continues, contact support.'
      });
    }
  });
});
</script>
@endpush
