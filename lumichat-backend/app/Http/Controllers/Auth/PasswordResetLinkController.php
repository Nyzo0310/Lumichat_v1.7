<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     * Security: no enumeration (always generic), honeypot, min form time, daily cap.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'   => ['required', 'string', 'email', 'max:191'],
            'fp_ts'   => ['nullable', 'integer'],         // hidden timestamp
            'website' => ['nullable', 'string', 'max:50'],// honeypot
        ]);

        // Normalize email (lookup only); we never reveal account existence.
        $email = strtolower(trim((string) $request->input('email')));

        // ── Bot friction: honeypot + minimum dwell time (>=3s) ────────────────
        $honeypotFilled = (string) $request->input('website') !== '';
        $startedAt      = (int) $request->input('fp_ts', 0);
        $elapsed        = $startedAt > 0
            ? Carbon::now()->diffInSeconds(Carbon::createFromTimestamp($startedAt))
            : 0;
        $tooFast        = $elapsed < 3;

        if ($honeypotFilled || $tooFast) {
            ActivityLog::create([
                'event'        => 'auth.password.reset_link_blocked_bot',
                'description'  => null,
                'actor_id'     => null,
                'subject_type' => null,
                'subject_id'   => null,
                'meta'         => [
                    'email_hash' => hash('sha256', $email),
                    'honeypot'   => $honeypotFilled,
                    'elapsed'    => $elapsed,
                    'ip'         => $request->ip(),
                    'ua'         => $request->userAgent(),
                ],
            ]);

            // Always the same generic UI response.
            return back()->with('status', 'If your email exists, we sent a reset link.');
        }

        // ── Per-email daily cap (10/day) — complements your route rate limiter ─
        $capKey = 'fp_cap:' . hash('sha256', $email) . ':' . date('Y-m-d');
        $count  = (int) Cache::increment($capKey);
        Cache::put($capKey, $count, now()->endOfDay());

        if ($count > 10) {
            ActivityLog::create([
                'event'        => 'auth.password.reset_link_blocked_daily_cap',
                'description'  => null,
                'actor_id'     => null,
                'subject_type' => null,
                'subject_id'   => null,
                'meta'         => [
                    'email_hash' => hash('sha256', $email),
                    'count'      => $count,
                    'ip'         => $request->ip(),
                    'ua'         => $request->userAgent(),
                ],
            ]);

            return back()->with('status', 'If your email exists, we sent a reset link.');
        }

        // ── Ask Laravel’s broker to issue the token and deliver (MAIL_MAILER=log in dev) ─
        Password::sendResetLink(['email' => $email]);

        // Audit (no PII)
        ActivityLog::create([
            'event'        => 'auth.password.reset_link_requested',
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

        // Always neutral response (no account enumeration).
        return back()->with('status', 'If your email exists, we sent a reset link.');
    }
}
