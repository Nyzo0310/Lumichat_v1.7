<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        $registration = Registration::query()
            ->where('email', $user->email)
            ->orWhere('full_name', $user->name)
            ->first();

        return view('profile.edit', [
            'user'         => $user,
            'registration' => $registration,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            $originalEmail = $user->email;

            $user->fill([
                'name'  => $data['name'],
                'email' => $data['email'],
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
            $user->save();

            Registration::updateOrCreate(
                ['email' => $user->email],
                [
                    'full_name'      => $user->name,
                    'email'          => $user->email,
                    'course'         => $data['course'] ?? null,
                    'year_level'     => $data['year_level'] ?? null,
                    'contact_number' => $data['contact_number'] ?? null,
                ]
            );

            if ($originalEmail !== $user->email) {
                Registration::where('email', $originalEmail)->delete();
            }
        });

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated')     // for global script map
            ->with('success', 'Profile updated');   // generic fallback
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
