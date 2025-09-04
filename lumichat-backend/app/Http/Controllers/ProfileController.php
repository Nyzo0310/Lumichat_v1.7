<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        // Find the registration row by email OR full name (covers mismatch)
        $registration = Registration::query()
            ->where('email', $user->email)
            ->orWhere('full_name', $user->name)
            ->first();

        return view('profile.edit', [
            'user'         => $user,
            'registration' => $registration,
        ]);
    }

    /**
     * Update the user's profile information.
     * - Updates users.name / users.email
     * - Upserts tbl_registration (full_name/email + course/year_level/contact_number)
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Update Users table
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        // Sync to tbl_registration
        Registration::updateOrCreate(
            // Key: prefer email; also keep a fallback on full_name
            ['email' => $user->email],
            [
                'full_name'      => $user->name,
                'email'          => $user->email,
                'course'         => $request->input('course'),
                'year_level'     => $request->input('year_level'),
                'contact_number' => $request->input('contact_number'),
            ]
        );

        return Redirect::route('profile.edit')->with('profile_updated', true);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // Optionally also delete from tbl_registration:
        // Registration::where('email', $user->email)->orWhere('full_name', $user->name)->delete();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
