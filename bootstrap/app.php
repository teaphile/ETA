<?php

use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Applied to every web request in this order.
        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
            'locale' => SetLocale::class,
            // Conditional: only blocks unverified accounts when REQUIRE_EMAIL_VERIFICATION=true.
            'verified' => EnsureEmailVerified::class,
        ]);

        // Honour X-Forwarded-* from a trusted reverse proxy / load balancer only.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '127.0.0.1,::1'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
