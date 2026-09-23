<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = Auth::user()?->role;

        if (! in_array($role, ['farm_admin', 'super_admin'], true)) {
            abort(403, 'This area is restricted to administrators only.');
        }

        return $next($request);
    }
}
