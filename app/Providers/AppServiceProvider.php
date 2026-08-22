<?php

namespace App\Providers;

use App\Repositories\MediaHelperRepositoryInterface;
use App\Support\MediaHelper;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MediaHelperRepositoryInterface::class, MediaHelper::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
            return Limit::perMinute(60)->by($request->ip());
        });

        if (App::environment('production')) {
            URL::forceScheme('https');
        }
    }
}
