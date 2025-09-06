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
    // Tunables (stay false for this activity: no email sending)
    private const ENABLE_SENDING      = false;
    private const MIN_SECONDS_ON_FORM = 3;
    private const DAILY_CAP_PER_EMAIL = 10;
    private const HOURLY_CAP_PER_IP   = 30;
    private const DISALLOW_DOMAINS    = [
        'mailinator.com','guerrillamail.com','10minutemail.com','tempmail.com',
        'trashmail.com','yopmail.com',
    ];

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'   => ['required','string','email:rfc,strict','max:191'],
            'fp_ts'   => ['nullable','integer'],
            'website' => ['nullable','string','max:50'],
        ]);

        $rawEmail   = strtolower(trim((string)$request->input('email')));
        $canonEmail = $this->canonicalizeForRateLimit($rawEmail);
        $domain     = substr(strrchr($rawEmail, '@') ?: '', 1);

        // Honeypot + min dwell time
        $isBot   = (string)$request->input('website') !== '';
        $start   = (int)$request->input('fp_ts', 0);
        $elapsed = $start > 0
            ? Carbon::now()->diffInSeconds(Carbon::createFromTimestamp($start))
            : 0;

        if ($isBot || $elapsed < self::MIN_SECONDS_ON_FORM) {
            $this->log('auth.password.reset_link_blocked_bot', [
                'email_hash' => hash('sha256', $canonEmail),
                'honeypot'   => $isBot,
                'elapsed'    => $elapsed,
                'ip'         => $request->ip(),
                'ua'         => $request->userAgent(),
            ]);
            return $this->genericResponse();
        }

        // Disposable domain?
        if ($domain && in_array($domain, self::DISALLOW_DOMAINS, true)) {
            $this->log('auth.password.reset_link_blocked_domain', [
                'email_hash' => hash('sha256', $canonEmail),
                'domain'     => $domain,
                'ip'         => $request->ip(),
                'ua'         => $request->userAgent(),
            ]);
            return $this->genericResponse();
        }

        // Per-IP hourly cap
        $ipKey   = 'fp_ipcap:' . $request->ip() . ':' . date('Y-m-d-H');
        $ipCount = (int) Cache::increment($ipKey);
        Cache::put($ipKey, $ipCount, now()->addHour());
        if ($ipCount > self::HOURLY_CAP_PER_IP) {
            $this->log('auth.password.reset_link_blocked_ip_cap', [
                'ip'    => $request->ip(),
                'ua'    => $request->userAgent(),
                'count' => $ipCount,
            ]);
            return $this->genericResponse();
        }

        // Per-email daily cap (canonicalized)
        $capKey = 'fp_cap:' . hash('sha256', $canonEmail) . ':' . date('Y-m-d');
        $count  = (int) Cache::increment($capKey);
        Cache::put($capKey, $count, now()->endOfDay());
        if ($count > self::DAILY_CAP_PER_EMAIL) {
            $this->log('auth.password.reset_link_blocked_daily_cap', [
                'email_hash' => hash('sha256', $canonEmail),
                'count'      => $count,
                'ip'         => $request->ip(),
                'ua'         => $request->userAgent(),
            ]);
            return $this->genericResponse();
        }

        // Small timing jitter
        try { usleep(random_int(200_000, 500_000)); } catch (\Throwable $e) {}

        // Delivery (disabled for activity)
        if (self::ENABLE_SENDING) {
            Password::sendResetLink(['email' => $rawEmail]);
        }

        $this->log('auth.password.reset_link_requested', [
            'email_hash' => hash('sha256', $canonEmail),
            'ip'         => $request->ip(),
            'ua'         => $request->userAgent(),
            'sent'       => self::ENABLE_SENDING,
        ]);

        return $this->genericResponse();
    }

    // Helpers
    private function canonicalizeForRateLimit(string $email): string
    {
        if ($email === '' || !str_contains($email, '@')) return $email;
        [$local, $domain] = explode('@', $email, 2);
        $domain = strtolower($domain);
        $local  = strtolower($local);

        if (in_array($domain, ['gmail.com','googlemail.com'], true)) {
            $local = str_replace('.', '', $local);
        }
        $local = explode('+', $local, 2)[0];

        return $local . '@' . $domain;
    }

    private function genericResponse(): RedirectResponse
    {
        return back()->with('status', 'If your email exists, we sent a reset link.');
    }

    private function log(string $event, array $meta): void
    {
        try {
            ActivityLog::create([
                'event'        => $event,
                'description'  => null,
                'actor_id'     => null,
                'subject_type' => null,
                'subject_id'   => null,
                'meta'         => $meta,
            ]);
        } catch (\Throwable $e) {
            // ignore logging issues
        }
    }
}
