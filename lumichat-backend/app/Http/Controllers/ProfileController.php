<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Registration;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProfileController extends Controller
{
    // ==== Constants (dedupe magic strings) ====
    private const VIEW_EDIT        = 'profile.edit';
    private const ROUTE_EDIT       = 'profile.edit';
    private const FLASH_STATUS     = 'status';
    private const FLASH_SUCCESS    = 'success';
    private const MSG_UPDATED_KEY  = 'profile-updated';
    private const MSG_UPDATED      = 'Profile updated';
    private const MSG_SAVE_ERROR   = 'Something went wrong while saving. Please try again.';
    private const MSG_DELETE_OK    = 'account-deleted';

    /**
     * Show the Profile page.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        // Link registration by email (fallback by name)
        $registration = Registration::query()
            ->where('email', $user->email)
            ->orWhere('full_name', $user->name)
            ->first();

        return view(self::VIEW_EDIT, [
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

            return Redirect::route(self::ROUTE_EDIT)
                ->with(self::FLASH_STATUS, self::MSG_UPDATED_KEY)
                ->with(self::FLASH_SUCCESS, self::MSG_UPDATED);
        } catch (\Throwable $e) {
            Log::error('Profile update failed', ['user_id' => $user->id, 'err' => $e]);

            return back()
                ->withInput()
                ->with('error', self::MSG_SAVE_ERROR);
        }
    }

    /**
     * Permanently delete the user account (and related data).
     * - Confirms current password
     * - Deletes dependent rows first to avoid FK violations
     * - Deletes tbl_registration row(s)
     * - Deletes auth artifacts (sanctum tokens, reset tokens, sessions)
     * - Deletes the user (forceDelete if SoftDeletes)
     * - Logs out and invalidates session
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user   = $request->user();
        $userId = $user->id;
        $email  = $user->email;
        $type   = \get_class($user);

        try {
            DB::transaction(function () use ($user, $userId, $email, $type) {
                // ---- Delete dependents FIRST (adjust to your schema) ----
                $this->deleteIfExists('tbl_appointments', ['student_id' => $userId]);
                $this->deleteIfExists('tbl_appointments', ['email' => $email]);

                // Chat sessions / messages
                $this->deleteIfExists('tbl_chatbot_sessions', ['user_id' => $userId]);
                $this->deleteIfExists('tbl_chat_messages', ['user_id' => $userId]);

                // Self assessments / diagnosis
                $this->deleteIfExists('tbl_self_assessment', ['user_id' => $userId]);
                $this->deleteIfExists('tbl_diagnosis', ['user_id' => $userId]);

                // ---- tbl_registration rows (by user_id if present, and by email) ----
                if (Schema::hasTable('tbl_registration')) {
                    if (Schema::hasColumn('tbl_registration', 'user_id')) {
                        DB::table('tbl_registration')->where('user_id', $userId)->delete();
                    }
                    if (Schema::hasColumn('tbl_registration', 'email')) {
                        DB::table('tbl_registration')->where('email', $email)->delete();
                    }
                }

                // ---- Auth artifacts (if present) ----
                // Sanctum tokens
                if (
                    class_exists(\Laravel\Sanctum\PersonalAccessToken::class)
                    && Schema::hasTable('personal_access_tokens')
                ) {
                    \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $userId)
                        ->where('tokenable_type', $type)
                        ->delete();
                }

                // Password reset tokens
                if (
                    Schema::hasTable('password_reset_tokens')
                    && Schema::hasColumn('password_reset_tokens', 'email')
                ) {
                    DB::table('password_reset_tokens')->where('email', $email)->delete();
                }

                // DB sessions (only if you use database session driver)
                if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                    DB::table('sessions')->where('user_id', $userId)->delete();
                }

                // ---- Delete the user row (force if soft-deleting model) ----
                $usesSoftDeletes = \in_array(
                    SoftDeletes::class,
                    class_uses_recursive($user),
                    true
                );

                if ($usesSoftDeletes) {
                    $user->forceDelete();
                } else {
                    $user->delete();
                }
            });

            // Logout AFTER successful deletion
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with(self::FLASH_STATUS, self::MSG_DELETE_OK); // your alerts show this as a toast
        } catch (\Throwable $e) {
            Log::error('Account deletion error', [
                'user_id' => $userId,
                'email'   => $email,
                'err'     => $e,
            ]);

            if (config('app.debug')) {
                return back()->withErrors([
                    'password' => 'Could not delete account: ' . $e->getMessage(),
                ], 'userDeletion');
            }

            return back()->withErrors([
                'password' => 'Could not delete account. Please try again.',
            ], 'userDeletion');
        }
    }

    // ==== Private helpers (no logic change) ====

    /**
     * Delete rows from a table if it (and required columns) exists.
     *
     * @param  string $table
     * @param  array<string, mixed> $conds
     */
    private function deleteIfExists(string $table, array $conds): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($conds as $col => $_) {
            if (!Schema::hasColumn($table, $col)) {
                return;
            }
        }

        DB::table($table)->where($conds)->delete();
    }
}
