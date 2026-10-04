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

        // Deactivated individual account
        $accountInactive = $user->status !== 'active';

        // Suspended / pending farm — super admins have no farm and are exempt.
        // An archived farm no longer resolves through the relation (SoftDeletes),
        // so a user still carrying its farm_id must be treated as locked out too,
        // or they would sail through this check into an empty app.
        $farmInactive = $user->role !== 'super_admin'
            && $user->farm_id !== null
            && (! $user->farm || $user->farm->status !== 'active');

        if ($accountInactive || $farmInactive) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $farmInactive && ! $accountInactive
                ? 'Your farm account is not active. Please contact Organett support.'
                : 'Your account has been deactivated. Contact the administrator.';

            return redirect('/login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
