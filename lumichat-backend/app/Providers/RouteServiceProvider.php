<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Where users land by default after login (students).
     */
    public const HOME = '/chat';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            // API routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Web routes (student side, auth, etc.)
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // ⬇️ Load admin routes (prefix/name/middleware are defined inside routes/admin.php)
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        });
    }
}
