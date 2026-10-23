<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureSecurityDefaults();
        $this->configureRateLimiting();

        // Surface N+1 queries and silently discarded attributes while developing.
        Model::shouldBeStrict(! $this->app->isProduction());
    }

    private function configureSecurityDefaults(): void
    {
        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->numbers()->mixedCase()->uncompromised()
            : Password::min(8));

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }

    /**
     * Named rate limiters for every endpoint that costs money, sends email, or can be
     * abused. Limits are deliberately generous enough for real students and tight
     * enough to protect paid integrations and the database.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('register', fn (Request $r) => Limit::perMinute(6)->by($r->ip()));
        RateLimiter::for('password-reset', fn (Request $r) => Limit::perMinute(4)->by($r->ip()));

        // The assistant may call a paid LLM provider — limit per user, then per IP.
        RateLimiter::for('assistant', fn (Request $r) => [
            Limit::perMinute(12)->by($r->user()?->id ?: 'guest:'.$r->ip()),
            Limit::perHour(100)->by($r->ip()),
        ]);

        RateLimiter::for('journey', fn (Request $r) => Limit::perMinute(20)->by($r->user()?->id ?: 'guest:'.$r->ip()));
        RateLimiter::for('places', fn (Request $r) => Limit::perMinute(30)->by($r->user()?->id ?: 'guest:'.$r->ip()));
        RateLimiter::for('community-write', fn (Request $r) => Limit::perMinute(5)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('reports', fn (Request $r) => Limit::perMinute(5)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('admin-import', fn (Request $r) => Limit::perMinute(5)->by($r->user()?->id ?: $r->ip()));
    }
}
