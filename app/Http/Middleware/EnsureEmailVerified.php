<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces email verification *only* when the platform is configured to require it
 * (config: exam_travel.security.require_email_verification, env REQUIRE_EMAIL_VERIFICATION).
 *
 * It deliberately replaces Laravel's unconditional `verified` middleware: this product
 * lets a student search and plan immediately by default, and operators can switch strict
 * verification on once outbound mail is configured. When enforcement is off, an
 * unverified account is not blocked anywhere.
 */
class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('exam_travel.security.require_email_verification', false)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user instanceof User && ! $user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('messages.auth.verify_first')], 403);
            }

            return redirect()->guest(route('verification.notice'));
        }

        return $next($request);
    }
}
