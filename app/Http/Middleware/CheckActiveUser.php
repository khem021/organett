<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        // A deactivated account, or a farm that is pending, rejected, suspended or
        // archived (an archived farm no longer resolves through its relation, so a
        // user still carrying its farm_id must be locked out too, or they would sail
        // through into an empty app). Super admins have no farm and are exempt.
        if ($reason = $user->lockoutReason()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors(['email' => $reason]);
        }

        return $next($request);
    }
}
