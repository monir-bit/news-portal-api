<?php

namespace App\Providers;

use App\Services\SlowQueryLoggerService;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;
use League\Flysystem\Visibility;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)
                ->by($request->ip());
        });

        // Baseline throttle for the admin panel (web middleware group).
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(180)->by($request->user()?->id ?: $request->ip());
        });

        // Brute-force protection for the reporter mobile-app login endpoint.
        RateLimiter::for('reporter-login', function (Request $request) {
            $key = Str::transliterate(Str::lower((string) $request->input('phone'))).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        // Sensitive account actions: password changes, profile mutation.
        RateLimiter::for('sensitive', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Public lead-gen/registration forms (club sign-ups) — spam/bot protection.
        RateLimiter::for('public-forms', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Voting / quiz / poll answer submissions — prevent ballot stuffing.
        RateLimiter::for('votes', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        // Search and other heavy read endpoints — protect the DB from query-flood DDoS.
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        SlowQueryLoggerService::register();

        // Pagination uses request()->url() by default, which ignores URL::forceScheme.
        // url()->current() respects forceScheme and the trusted proxy–corrected request.
        Paginator::currentPathResolver(fn () => url()->current());

        if (App::environment() === 'production') {
            URL::forceScheme('https');
        }

    }
}
