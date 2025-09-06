<x-guest-layout logo-path="images/icons/forget-password.png" logo-alt="Forgot Password">
    {{-- Back to login (single location, predictable) --}}
    <div class="max-w-md mx-auto w-full mt-6">
        <a href="{{ route('login') }}"
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white"
           aria-label="{{ __('Back to login') }}">
            <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M12.78 15.22a.75.75 0 01-1.06 0L6.47 10l5.25-5.22a.75.75 0 111.06 1.06L8.56 10l4.22 4.16a.75.75 0 010 1.06z" clip-rule="evenodd"/>
            </svg>
            {{ __('Back to login') }}
        </a>
    </div>

    <div class="max-w-md mx-auto w-full mt-3 bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
        {{-- Title + purpose (recognition over recall) --}}
        <div class="flex items-start gap-3 mb-4">
            <div class="p-2 rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 1.75A5.25 5.25 0 006.75 7v2.25H6a2.25 2.25 0 00-2.25 2.25v7.5A2.25 2.25 0 006 21.25h12a2.25 2.25 0 002.25-2.25v-7.5A2.25 2.25 0 0018 9.25h-.75V7A5.25 5.25 0 0012 1.75zm-3.75 5.25A3.75 3.75 0 0112 3.25a3.75 3.75 0 013.75 3.75v2.25h-7.5V7z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('Forgot your password?') }}
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Enter your email. We’ll email a reset link if the address is registered.') }}
                </p>
            </div>
        </div>

        {{-- Clear feedback (status lives here) --}}
        @if (session('status'))
            <div role="status" aria-live="polite"
                 class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700
                        dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                {{ __('If your email exists, we sent a reset link.') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" novalidate>
            @csrf

            {{-- Email field (minimal friction, strong affordance) --}}
            <div>
                <x-input-label for="email" :value="__('Email')" />
                @php $emailError = $errors->first('email'); @endphp
                <x-text-input
                    id="email"
                    class="block mt-1 w-full"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="email"
                    autocapitalize="none"
                    spellcheck="false"
                    inputmode="email"
                    maxlength="191"
                    aria-describedby="{{ $emailError ? 'email-help email-error' : 'email-help' }}"
                    aria-invalid="{{ $emailError ? 'true' : 'false' }}"
                />
                <p id="email-help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('We’ll never display whether an address exists this protects your account.') }}
                </p>
                <x-input-error :messages="$errors->get('email')" class="mt-2" id="email-error"/>
            </div>

            {{-- Subtle guidance (visibility of system status) --}}
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Reset links expire in ~20 minutes. Check Spam/Promotions if you don’t see it.') }}
            </p>

            {{-- Primary action (single, prominent) --}}
            <div class="mt-6">
                <x-primary-button class="w-full justify-center">
                    {{ __('Email Password Reset Link') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
