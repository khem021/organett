<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckFarmFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        if ($user->role === 'super_admin') {
            return $next($request);
        }

        if (! $user->farm || ! $user->farm->hasFeature($feature)) {
            abort(403, "The '{$feature}' feature is not enabled for your farm.");
        }

        return $next($request);
    }
}
