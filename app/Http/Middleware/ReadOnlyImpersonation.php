<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * While a super admin is viewing a farm as one of its users, allow reads only.
 *
 * Enforcing this in one place is what makes impersonation safe to ship — the
 * alternative is trusting every controller to check for it.
 */
class ReadOnlyImpersonation
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, Closure $next)
    {
        if (! $request->session()->has('impersonator_id')) {
            return $next($request);
        }

        if (in_array($request->method(), self::SAFE_METHODS, true)) {
            return $next($request);
        }

        // Leaving the session must stay reachable, or the operator is stuck.
        if ($request->routeIs('impersonate.stop') || $request->routeIs('logout')) {
            return $next($request);
        }

        abort(403, 'You are viewing this farm as a platform administrator. This view is read-only.');
    }
}
