<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the interface language for a request.
 *
 * Priority: the signed-in user's stored preference → the session switch → the
 * application default. Business logic is never duplicated per language; only the
 * translation files differ (see lang/en and lang/hi).
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'hi'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->preferred_language
            ?? $request->session()->get('locale')
            ?? config('app.locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.fallback_locale', 'en');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
