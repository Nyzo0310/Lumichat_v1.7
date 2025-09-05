<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct()
    {
        // Prevent brute force attacks on registration
        $this->middleware('throttle:6,1')->only('store');
    }

    /**
     * Show the registration form.
     */
    public function create(): View
    {
        // If your file is at resources/views/auth/register.blade.php
        return view('auth.register');
    }

    /**
     * Handle the registration request.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Force lowercase email
        $data['email'] = Str::lower($data['email']);
        $hashedPassword = Hash::make($data['password']);

        Registration::create([
            'full_name'      => $data['full_name'],
            'email'          => $data['email'],
            'contact_number' => $data['contact_number'],
            'course'         => $data['course'],
            'year_level'     => $data['year_level'],
            'password'       => Hash::make($data['password']),
        ]);
         // Save to tbl_users
        User::create([
            'name'                 => $data['full_name'],
            'email'                => $data['email'],
            'course'               => $data['course'],
            'year_level'           => $data['year_level'],
            'contact_number'       => $data['contact_number'],
            'password'             => $hashedPassword,
            'role'                 => User::ROLE_STUDENT,  // default role
            'appointments_enabled' => false,               // default setting
        ]);

        return redirect()
            ->route('login')
            ->with('success', 'Your account has been created! Please sign in.');
    }
}
