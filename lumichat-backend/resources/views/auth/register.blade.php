@extends('layouts.student-registration')

@section('content')
<div class="flex justify-center items-center min-h-screen py-20 bg-gray-200">
    <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-2xl space-y-8 animate__animated animate__fadeIn">
        <h2 class="text-center text-xl font-bold text-gray-800">Registration Form</h2>

        <form method="POST" action="{{ route('register') }}" class="space-y-6" novalidate>
            @csrf

            <!-- Personal Information Card -->
            <div class="bg-gray-100 p-6 rounded-xl shadow space-y-3">
                <h3 class="font-semibold text-gray-700">Personal Information</h3>
                <p class="text-sm text-gray-500">Share your name, email, and contact so we can stay in touch</p>

                {{-- Full Name --}}
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <div class="mt-1 flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-2 focus-within:ring-indigo-500">
                        <img src="{{ asset('images/icons/user.png') }}" class="w-5 h-5 mr-2" alt="User Icon">
                        <input
                            id="full_name"
                            name="full_name"
                            type="text"
                            inputmode="text"
                            autocomplete="name"
                            required
                            minlength="2"
                            maxlength="80"
                            value="{{ old('full_name') }}"
                            class="w-full bg-transparent text-sm outline-none border-none placeholder-gray-400"
                            placeholder="Enter your full name">
                    </div>
                    @error('full_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <div class="mt-1 flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-2 focus-within:ring-indigo-500">
                        <img src="{{ asset('images/icons/mail.png') }}" class="w-5 h-5 mr-2" alt="Mail Icon">
                        <input
                            id="email"
                            name="email"
                            type="email"
                            inputmode="email"
                            autocomplete="email"
                            required
                            maxlength="255"
                            value="{{ old('email') }}"
                            class="w-full bg-transparent text-sm outline-none border-none placeholder-gray-400"
                            placeholder="your.email@example.com">
                    </div>
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Contact Number --}}
                <div>
                    <label for="contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                    <div class="mt-1 flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-2 focus-within:ring-indigo-500">
                        <img src="{{ asset('images/icons/phone.png') }}" class="w-5 h-5 mr-2" alt="Phone Icon">
                        <input
                            id="contact_number"
                            name="contact_number"
                            type="text"
                            inputmode="tel"
                            autocomplete="tel"
                            required
                            minlength="7"
                            maxlength="20"
                            value="{{ old('contact_number') }}"
                            class="w-full bg-transparent text-sm outline-none border-none placeholder-gray-400"
                            placeholder="+63 900 000 0000">
                    </div>
                    @error('contact_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Academic Information Card -->
            <div class="bg-gray-100 p-6 rounded-xl shadow space-y-3">
                <h3 class="font-semibold text-gray-700">Academic Information</h3>
                <p class="text-sm text-gray-500">Tell us your course and year level to access tailored support</p>

                {{-- Course --}}
                <div>
                    <label for="course" class="block text-sm font-medium text-gray-700">Course</label>
                    <div class="mt-1 flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-2 focus-within:ring-indigo-500">
                        <img src="{{ asset('images/icons/graduate.png') }}" class="w-5 h-5 mr-2" alt="Course Icon">
                        <select
                            id="course"
                            name="course"
                            class="w-full bg-transparent text-sm outline-none border-none text-gray-700"
                            required
                        >
                            <option disabled {{ old('course') ? '' : 'selected' }}>Select your course</option>
                            <option value="BSIT"      {{ old('course') == 'BSIT' ? 'selected' : '' }}>College of Information Technology</option>
                            <option value="EDUC"      {{ old('course') == 'EDUC' ? 'selected' : '' }}>College of Education</option>
                            <option value="CAS"       {{ old('course') == 'CAS' ? 'selected' : '' }}>College of Arts and Sciences</option>
                            <option value="CRIM"      {{ old('course') == 'CRIM' ? 'selected' : '' }}>College of Criminal Justice and Public Safety</option>
                            <option value="BLIS"      {{ old('course') == 'BLIS' ? 'selected' : '' }}>College of Library Information Science</option>
                            <option value="MIDWIFERY" {{ old('course') == 'MIDWIFERY' ? 'selected' : '' }}>College of Midwifery</option>
                            <option value="BSHM"      {{ old('course') == 'BSHM' ? 'selected' : '' }}>College of Hospitality Management</option>
                            <option value="BSBA"      {{ old('course') == 'BSBA' ? 'selected' : '' }}>College of Business</option>
                        </select>
                    </div>
                    @error('course')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Year Level --}}
                <div>
                    <label for="year_level" class="block text-sm font-medium text-gray-700">Year Level</label>
                    <div class="mt-1 flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-2 focus-within:ring-indigo-500">
                        <img src="{{ asset('images/icons/graduate.png') }}" class="w-5 h-5 mr-2" alt="Year Icon">
                        <select
                            id="year_level"
                            name="year_level"
                            class="w-full bg-transparent text-sm outline-none border-none text-gray-700"
                            required
                        >
                            <option disabled {{ old('year_level') ? '' : 'selected' }}>Select your year level</option>
                            <option value="1st year" {{ old('year_level') == '1st year' ? 'selected' : '' }}>1st year</option>
                            <option value="2nd year" {{ old('year_level') == '2nd year' ? 'selected' : '' }}>2nd year</option>
                            <option value="3rd year" {{ old('year_level') == '3rd year' ? 'selected' : '' }}>3rd year</option>
                            <option value="4th year" {{ old('year_level') == '4th year' ? 'selected' : '' }}>4th year</option>
                        </select>
                    </div>
                    @error('year_level')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Security Card -->
            <div class="bg-gray-100 p-6 rounded-xl shadow space-y-3">
                <h3 class="font-semibold text-gray-700">Security</h3>
                <p class="text-sm text-gray-500">Choose a strong password (min 12 chars with upper/lower, number, symbol)</p>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="mt-1 flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-2 focus-within:ring-indigo-500">
                        <img src="{{ asset('images/icons/lock.png') }}" class="w-5 h-5 mr-2" alt="Lock Icon">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                            minlength="12"
                            class="w-full bg-transparent text-sm outline-none border-none placeholder-gray-400"
                            placeholder="Create a strong password">
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <div class="mt-1 flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-2 focus-within:ring-indigo-500">
                        <img src="{{ asset('images/icons/lock.png') }}" class="w-5 h-5 mr-2" alt="Lock Icon">
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            minlength="12"
                            class="w-full bg-transparent text-sm outline-none border-none placeholder-gray-400"
                            placeholder="Confirm your password">
                    </div>
                </div>
            </div>

            <!-- Terms + Submit -->
            <div class="space-y-3">
                <label class="inline-flex items-start text-sm text-gray-600" for="agree">
                    <input type="checkbox" id="agree" class="mt-1 mr-2 rounded border-gray-300" required>
                    <span>
                        I agree to
                        <a href="{{ route('privacy.policy') }}" class="text-blue-600 underline">LumiCHAT’s Privacy Policy</a>
                        and understand how my data will be used to provide mental health support services.
                    </span>
                </label>

                <button
                    type="submit"
                    id="registerBtn"
                    disabled
                    class="w-full bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-blue-600 text-white py-2.5 rounded-lg font-medium shadow">
                    Register Account
                </button>
            </div>

            <p class="text-center text-sm text-gray-600 mt-4">
                Already have an account? <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">Return to Login</a>
            </p>
        </form>
    </div>
</div>

<!-- SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if ($errors->any())
            // Safely pass errors to JS and show as text (no untrusted HTML)
            const errs = @json($errors->all());
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                text: errs.join('\n'),
                confirmButtonColor: '#e3342f'
            });
        @endif
    });
</script>

<script>
    const agree = document.getElementById('agree');
    const registerBtn = document.getElementById('registerBtn');

    agree.addEventListener('change', function () {
        registerBtn.disabled = !this.checked;
    });
</script>
@endsection
