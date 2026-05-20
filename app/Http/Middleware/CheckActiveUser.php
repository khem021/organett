<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors([
                'email' => 'Your account has been deactivated. Contact the administrator.',
            ]);
        }

        return $next($request);
    }
}
