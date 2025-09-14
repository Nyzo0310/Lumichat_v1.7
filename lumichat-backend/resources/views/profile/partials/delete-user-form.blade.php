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

  {{-- Alpine controller (kept in-page) --}}
  <div
    x-data="{ show: @js($errors->userDeletion->isNotEmpty()) }"
    x-cloak
    x-on:open-delete-modal.window="
      show = true;
      // lock scroll
      document.documentElement.classList.add('modal-open');
      $nextTick(() => $refs.pwd?.focus());
    "
    x-effect="
      if (!show) document.documentElement.classList.remove('modal-open');
    "
  >
    {{-- Teleport the actual modal to <body> so it always sits on top & centers correctly --}}
    <template x-teleport="body">
      {{-- OUTER CONTAINER (covers viewport) --}}
      <div
        x-show="show"
        class="fixed inset-0 modal-zp"
        x-cloak
      >
        {{-- Backdrop --}}
        <div
          class="fixed inset-0 modal-z bg-black/40 backdrop-blur-sm"
          x-transition.opacity.duration.150ms
          @click="show=false"
          aria-hidden="true"
        ></div>

        {{-- Centering layer + dialog (pointer-events trick so only panel is interactive) --}}
        <div class="absolute inset-0 grid place-items-center p-4 pointer-events-none">
          <form
            method="post"
            action="{{ route('profile.destroy') }}"
            class="modal-zp pointer-events-auto w-full max-w-xl rounded-2xl bg-white dark:bg-gray-800 shadow-2xl p-6 ring-1 ring-gray-200 dark:ring-gray-700 transform transition-all"
            x-ref="form"
            x-show="show"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @submit.prevent="
              $refs.submitBtn.disabled = true;
              $refs.submitBtn.classList.add('opacity-70','cursor-not-allowed');
              $refs.submitBtn.textContent = 'Deleting…';
              show = false;
              setTimeout(() => $refs.form.submit(), 160);
            "
            role="dialog"
            aria-modal="true"
            aria-labelledby="delete-modal-title"
          >
            @csrf
            @method('delete')

            <h2 id="delete-modal-title" class="title-dynamic text-lg font-medium">
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
                class="mt-1 block w-full input-dynamic"
                placeholder="{{ __('Enter your password to confirm') }}"
                autocomplete="current-password"
                required
                minlength="6"
              />
              <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
              <button type="button" class="btn-secondary" @click="show=false">
                {{ __('Cancel') }}
              </button>

              <button
                type="submit"
                x-ref="submitBtn"
                class="!bg-rose-600 hover:!bg-rose-700 !text-white !rounded-xl !px-5 !py-2.5"
              >
                {{ __('Delete Account') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </template>
  </div>
</section>

@push('styles')
<style>
  /* Optional: prevent scroll-jump when modal is open */
  html.modal-open, html.modal-open body { overflow: hidden !important; }
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
      reverseButtons: true,
    });

    if (res.isConfirmed) openModal();
  });
});
</script>
@endpush
