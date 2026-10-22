<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Schema::defaultStringLength(191);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Login / password-reset attempts: 5 per minute per email *and* IP.
        // Keying by IP alone would let a few people behind one shared network
        // (a school or office) lock everyone else out of logging in.
        RateLimiter::for('auth-attempts', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')) . '|' . $request->ip());
        });
    }
}
