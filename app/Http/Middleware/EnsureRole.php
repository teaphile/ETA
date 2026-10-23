<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (! $request->user() || ! in_array($request->user()->role, $roles)) {
            abort(403, 'Access denied.');
        }
        if ($request->user()->account_status === 'suspended') {
            abort(403, 'Account suspended.');
        }

        return $next($request);
    }
}
