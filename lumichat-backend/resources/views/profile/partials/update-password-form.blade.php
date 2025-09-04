<section>
  <header>
    <h2 class="title-dynamic text-lg font-medium">
      {{ __('Update Password') }}
    </h2>
    <p class="mt-1 muted-dynamic text-sm">
      {{ __('Ensure your account is using a long, random password to stay secure.') }}
    </p>
  </header>

  <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
    @csrf
    @method('put')

    <div>
      <x-input-label for="update_password_current_password" :value="__('Current Password')" class="title-dynamic"/>
      <x-text-input id="update_password_current_password" name="current_password" type="password"
                    class="mt-1 block w-full input-dynamic" autocomplete="current-password" />
      <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
    </div>

    <div>
      <x-input-label for="update_password_password" :value="__('New Password')" class="title-dynamic"/>
      <x-text-input id="update_password_password" name="password" type="password"
                    class="mt-1 block w-full input-dynamic" autocomplete="new-password" />
      <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
    </div>

    <div>
      <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" class="title-dynamic"/>
      <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password"
                    class="mt-1 block w-full input-dynamic" autocomplete="new-password" />
      <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
    </div>

    <div class="flex items-center gap-4">
      <button type="submit" class="btn-primary">
        {{ __('Save') }}
      </button>
    </div>
  </form>
</section>

{{-- SweetAlert2 on success (Jetstream returns status=password-updated) --}}
@if (session('status') === 'password-updated')
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    window.addEventListener('DOMContentLoaded', () => {
      Swal.fire({
        icon: 'success',
        title: 'Password updated',
        text: 'Your password has been changed successfully.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#4F46E5'
      });
    });
  </script>
@endif
