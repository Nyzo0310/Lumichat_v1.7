<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show the Profile page.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        // Option A (no migration): link registration by email (and fallback by name)
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
     * Update profile (name, email, course/year/phone).
     * Validation + sanitation are handled by ProfileUpdateRequest.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated(); // phone is REQUIRED here

        try {
            DB::transaction(function () use ($user, $data) {
                $originalEmail = $user->email;

                // Update Users table
                $user->fill([
                    'name'  => $data['name'],
                    'email' => $data['email'],
                ]);
                if ($user->isDirty('email')) {
                    $user->email_verified_at = null;
                }
                $user->save();

                // Upsert Registration by email (schema without user_id)
                Registration::updateOrCreate(
                    ['email' => $user->email],
                    [
                        'full_name'      => $user->name,
                        'email'          => $user->email,
                        'course'         => $data['course']      ?? null,
                        'year_level'     => $data['year_level']  ?? null,
                        'contact_number' => $data['contact_number'], // required by FormRequest
                    ]
                );

                // Clean any stale row left under the old email
                if ($originalEmail !== $user->email) {
                    Registration::where('email', $originalEmail)->delete();
                }
            });

            return Redirect::route('profile.edit')
                ->with('status',  'profile-updated')   // alerts partial maps to nice text
                ->with('success', 'Profile updated'); // fallback
        } catch (\Throwable $e) {
            // Log and show a friendly message
            Log::error('Profile update failed', ['user_id' => $user->id, 'err' => $e]);
            return back()
                ->withInput()
                ->with('error', 'Something went wrong while saving. Please try again.');
        }
    }

    /**
     * Permanently delete the user account (and related data).
     * - Confirms current password (Jetstream-style)
     * - Deletes dependent rows first to avoid FK violations
     * - Deletes tbl_registration row(s)
     * - Deletes the user
     * - Logs out and invalidates session
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        try {
            DB::beginTransaction();

            // Helper: delete from a table if it (and columns) exist
            $deleteIfExists = function (string $table, array $conds) {
                if (!Schema::hasTable($table)) return;
                foreach ($conds as $col => $val) {
                    if (!Schema::hasColumn($table, $col)) return;
                }
                DB::table($table)->where($conds)->delete();
            };

            // ---- Delete dependents FIRST (adjust names to your schema as needed) ----
            // Appointments
            $deleteIfExists('tbl_appointments', ['student_id' => $user->id]);
            $deleteIfExists('tbl_appointments', ['email'      => $user->email]);

            // Chat sessions / messages
            $deleteIfExists('tbl_chatbot_sessions', ['user_id' => $user->id]);
            $deleteIfExists('tbl_chat_messages',    ['user_id' => $user->id]);

            // Self assessments / diagnosis
            $deleteIfExists('tbl_self_assessment', ['user_id' => $user->id]);
            $deleteIfExists('tbl_diagnosis',       ['user_id' => $user->id]);

            // Add more tables here as you need:
            // $deleteIfExists('tbl_notes', ['user_id' => $user->id]);

            // ---- tbl_registration rows (both by user_id if exists, and email) ----
            if (Schema::hasTable('tbl_registration')) {
                if (Schema::hasColumn('tbl_registration', 'user_id')) {
                    DB::table('tbl_registration')->where('user_id', $user->id)->delete();
                }
                if (Schema::hasColumn('tbl_registration', 'email')) {
                    DB::table('tbl_registration')->where('email', $user->email)->delete();
                }
            }

            // ---- Delete the user row ----
            $user->delete();

            DB::commit();

            // Logout AFTER commit
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('status', 'account-deleted'); 
        } catch (\Throwable $e) {
            DB::rollBack();

            // When APP_DEBUG=true you'll also see this in the toast
            if (config('app.debug')) {
                session()->flash('error', 'Account deletion failed: '.$e->getMessage());
            } else {
                session()->flash('error', 'Account deletion failed.');
            }
            Log::error('Account deletion error', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'err'     => $e,
            ]);

            return back()
                ->withErrors(['password' => 'Could not delete account. Please try again.'], 'userDeletion');
        }
    }
}
