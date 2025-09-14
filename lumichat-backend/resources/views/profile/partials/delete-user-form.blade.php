{{-- resources/views/profile/partials/delete-user-form.blade.php --}}
<section class="space-y-6">
  <header>
    <h2 class="title-dynamic text-lg font-medium">{{ __('Delete Account') }}</h2>
    <p class="mt-1 muted-dynamic text-sm">
      {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
    </p>
  </header>

  {{-- Trigger (SweetAlert confirm first) --}}
  <button
    type="button"
    id="btn-delete-account"
    class="!bg-rose-600 hover:!bg-rose-700 !text-white !rounded-xl !px-5 !py-2.5"
  >
    {{ __('Delete Account') }}
  </button>

  {{-- === Pure-Alpine centered modal with animations === --}}
  <div
    x-data="{ show: @js($errors->userDeletion->isNotEmpty()) }"
    x-cloak
    x-on:open-delete-modal.window="
      show = true;
      $nextTick(() => $refs.pwd?.focus());
    "
    class="fixed inset-0 z-[100000] flex items-center justify-center p-4"
    :class="show ? 'pointer-events-auto' : 'pointer-events-none'"
  >
    <!-- Overlay -->
    <div
      x-show="show"
      x-transition.opacity.duration.200ms
      class="fixed inset-0 bg-black/40 backdrop-blur-[2px]"
      @click="show=false"
    ></div>

    <!-- Panel -->
    <form
      method="post"
      action="{{ route('profile.destroy') }}"

      x-show="show"
      x-transition:enter="ease-out duration-200"
      x-transition:enter-start="opacity-0 scale-95 translate-y-1"
      x-transition:enter-end="opacity-100 scale-100 translate-y-0"
      x-transition:leave="ease-in duration-150"
      x-transition:leave-start="opacity-100 scale-100"
      x-transition:leave-end="opacity-0 scale-95"

      class="relative z-10 w-full max-w-xl rounded-2xl bg-white dark:bg-gray-800 shadow-2xl p-6"
      x-ref="form"
      @keydown.escape.window="show=false"
      @submit.prevent="
        // disable button & play exit animation before submit
        $refs.submitBtn.disabled = true;
        $refs.submitBtn.classList.add('opacity-70','cursor-not-allowed');
        $refs.submitBtn.textContent = 'Deleting…';
        show = false;
        setTimeout(() => $refs.form.submit(), 180);
      "
    >
      @csrf
      @method('delete')

      {{-- Animated title --}}
      <h2
        class="title-dynamic text-lg font-medium"
        :class="show ? 'animate-modal-title' : ''"
      >
        {{ __('Are you sure you want to delete your account?') }}
      </h2>

      <p class="mt-1 muted-dynamic text-sm">
        {{ __('This action is permanent and cannot be undone.') }}
      </p>

      <div class="mt-6">
        <x-input-label for="delete_password" value="{{ __('Password') }}" class="sr-only" />
        <x-text-input
          x-ref="pwd"
          id="delete_password"
          name="password"
          type="password"
          class="mt-1 block w-3/4 input-dynamic"
          placeholder="{{ __('Enter your password to confirm') }}"
          autocomplete="current-password"
          required
          minlength="6"
        />
        <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
      </div>

      <div class="mt-6 flex justify-end">
        <button type="button" class="btn-secondary" @click="show=false">
          {{ __('Cancel') }}
        </button>

        <button
          type="submit"
          x-ref="submitBtn"
          class="ms-3 !bg-rose-600 hover:!bg-rose-700 !text-white !rounded-xl !px-5 !py-2.5"
        >
          {{ __('Delete Account') }}
        </button>
      </div>
    </form>
  </div>
</section>

@push('styles')
<style>
/* Small pop-in animation for the modal title */
@keyframes modalTitlePop {
  0%   { opacity: 0; transform: translateY(6px) scale(.98); }
  60%  { opacity: 1; transform: translateY(-1px) scale(1.01); }
  100% { opacity: 1; transform: translateY(0) scale(1); }
}
.animate-modal-title {
  animation: modalTitlePop .28s cubic-bezier(.2,.8,.2,1) 1;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('btn-delete-account');
  const openModal = () => window.dispatchEvent(new CustomEvent('open-delete-modal'));

  btn?.addEventListener('click', async () => {
    if (typeof Swal === 'undefined') return openModal();

    const res = await Swal.fire({
      icon: 'warning',
      title: 'Delete account permanently?',
      html: 'This action <b>cannot be undone</b>. Your account and related data will be deleted forever.',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete permanently',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#dc2626',
      focusCancel: true,
      reverseButtons: true
    });

    if (res.isConfirmed) openModal();
  });
});
</script>
@endpush
