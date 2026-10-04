<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, Farm $farm)
    {
        // A suspended farm would get the session destroyed by CheckActiveUser on
        // the next request, taking impersonator_id with it and dumping the
        // operator at the login screen. Refuse clearly instead.
        if ($farm->status !== 'active') {
            return back()->with('error', "'{$farm->name}' is {$farm->status}. Activate it before viewing as one of its users.");
        }

        $target = User::where('farm_id', $farm->id)
            ->where('role', 'farm_admin')
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if (! $target) {
            return back()->with('error', "'{$farm->name}' has no active administrator to view as.");
        }

        $impersonator = Auth::id();

        // Logged before the identity switch so the entry names the super admin.
        ActivityLogger::logForFarm(
            $farm->id,
            'Platform',
            'impersonate_start',
            "Started viewing \"{$farm->name}\" as {$target->email} (read-only).",
        );

        Auth::login($target);

        // Set after login(): Auth::login() migrates the session, so writing the
        // key afterwards does not depend on migrate preserving existing data.
        $request->session()->put('impersonator_id', $impersonator);

        return redirect('/dashboard');
    }

    public function stop(Request $request)
    {
        $impersonatorId = $request->session()->pull('impersonator_id');

        if (! $impersonatorId) {
            return redirect('/dashboard');
        }

        $farm = Auth::user()?->farm;
        $viewedAs = Auth::user()?->email;

        Auth::loginUsingId($impersonatorId);

        ActivityLogger::logForFarm(
            $farm?->id,
            'Platform',
            'impersonate_end',
            "Stopped viewing \"{$farm?->name}\" as {$viewedAs}.",
        );

        return $farm
            ? redirect()->route('admin.farms.show', $farm)
            : redirect()->route('admin.farms.index');
    }
}
