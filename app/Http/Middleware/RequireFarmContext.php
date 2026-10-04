<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep the platform owner out of a farm's own pages.
 *
 * The platform owner belongs to no farm, and FarmScope does not filter for them, so
 * /orders or /users opened by hand would list every farm's rows mixed together, and
 * anything they created would belong to no farm at all. They look inside one farm at
 * a time through "View as farm", where they hold that farm admin's identity.
 *
 * An allowlist rather than a blocklist: a farm page added later is closed to the
 * platform owner until someone decides otherwise.
 */
class RequireFarmContext
{
    /** Routes the platform owner may use outside the /admin area. */
    private const PLATFORM_ROUTES = ['dashboard', 'search', 'impersonate.stop', 'activity-logs.index', 'admin.*'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isSuperAdmin() || $request->routeIs(...self::PLATFORM_ROUTES)) {
            return $next($request);
        }

        $message = 'Platform administrators manage farms from the master dashboard. Use "View as farm" to look inside one.';

        if ($request->expectsJson()) {
            abort(403, $message);
        }

        return redirect()->route('admin.farms.index')->with('error', $message);
    }
}
