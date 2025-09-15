<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    // ==== Constants ====
    private const VIEW_LOGIN      = 'auth.login';
    private const CTX_ADMIN       = 'admin';
    private const CTX_STUDENT     = 'student';
    private const ALLOWED_CONTEXT = [self::CTX_ADMIN, self::CTX_STUDENT];

    /**
     * Show login form (role-aware).
     */
    public function create(Request $request): View
    {
        $loginContext = $this->resolveLoginContext($request);

        return view(self::VIEW_LOGIN, ['loginContext' => $loginContext]);
    }

    /**
     * Handle login.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Sanitized + rate-limited by LoginRequest
        $request->authenticate();
        $request->session()->regenerate(); // session fixation protection

        $user = $request->user();

        if (\method_exists($user, 'canAccessAdmin') && $user->canAccessAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended(route('chat.index'));
    }

    /**
     * Logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    // ==== Private helpers (no logic change) ====

    /**
     * Determine login context based on query, path, or intended URL.
     */
    private function resolveLoginContext(Request $request): string
    {
        // default
        $loginContext = self::CTX_STUDENT;

        // quick override for testing: /login?ctx=admin
        $ctxParam = \strtolower((string) $request->query('ctx', ''));
        if (\in_array($ctxParam, self::ALLOWED_CONTEXT, true)) {
            return $ctxParam;
        }

        // if URL is /admin/login -> admin
        if ($request->is('admin') || $request->is('admin/*')) {
            return self::CTX_ADMIN;
        }

        // if redirected from admin page -> admin
        $intended     = (string) $request->session()->get('url.intended', '');
        $intendedPath = \parse_url($intended, PHP_URL_PATH) ?? '';
        if (Str::startsWith($intendedPath, '/admin')) {
            return self::CTX_ADMIN;
        }

        return $loginContext;
    }
}
