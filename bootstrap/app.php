<?php

use App\Http\Middleware\ApiCacheHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // New, in-progress v2 API — runs alongside the existing /api routes untouched.
            Route::middleware('api')->prefix('api/v2')->group(base_path('routes/api_v2.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cloud Run / load balancers: trust X-Forwarded-Proto so request()->url() is https.
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Use Redis for Laravel rate limiting.
        $middleware->throttleWithRedis();

        // Public read APIs (home, news-details, etc.) are called heavily by Next.js SSR
        // from a small set of server IPs. A global per-IP api throttle causes intermittent
        // 429s and frontend 404s. Keep per-route throttles on writes (votes, forms, login).
        $middleware->api(append: [
            ApiCacheHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
