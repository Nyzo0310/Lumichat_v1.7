<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email', 'max:191'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = strtolower(trim($request->input('email')));

        $status = Password::reset(
            [
                'email'                 => $email,
                'password'              => $request->password,
                'password_confirmation' => $request->password_confirmation,
                'token'                 => $request->token,
            ],
            function ($user) use ($request) {
                // Update password securely
                $user->forceFill([
                    'password'       => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                // Sign them in, kill other devices, rotate session
                Auth::login($user);
                Auth::logoutOtherDevices($request->password);
                $request->session()->regenerate();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            ActivityLog::create([
                'event'        => 'auth.password.reset_success',
                'description'  => null,
                'actor_id'     => auth()->id(),
                'subject_type' => \App\Models\User::class,
                'subject_id'   => auth()->id(),
                'meta'         => [
                    'ip' => $request->ip(),
                    'ua' => $request->userAgent(),
                ],
            ]);

            return redirect()->route('login')->with('status', 'Password updated. You can now sign in.');
        }

        // Generic failure (invalid/expired) + audit
        ActivityLog::create([
            'event'        => 'auth.password.reset_failed',
            'description'  => null,
            'actor_id'     => null,
            'subject_type' => null,
            'subject_id'   => null,
            'meta'         => [
                'email_hash' => hash('sha256', $email),
                'ip'         => $request->ip(),
                'ua'         => $request->userAgent(),
            ],
        ]);

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Link invalid or expired—request a new one.']);
    }
}
