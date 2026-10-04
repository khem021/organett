<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ImpersonationController;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // "Remember me for 30 days" has to mean 30 days; the framework default is 400.
        Auth::setRememberDuration(60 * 24 * 30);

        // Compared in lower case: PostgreSQL treats "Ana@x.com" and "ana@x.com" as different.
        $lookup = [
            'email' => fn ($query) => $query->whereRaw('lower(email) = ?', [Str::lower(trim($credentials['email']))]),
            'password' => $credentials['password'],
        ];

        if (! Auth::attempt($lookup, $request->boolean('remember'))) {
            Log::warning('auth.failed', ['email' => $credentials['email'], 'ip' => $request->ip()]);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Right password, but the account or its farm is not open: say why, and do
        // not let the visit count as a login.
        if ($reason = Auth::user()->lockoutReason()) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => $reason]);
        }

        $request->session()->regenerate();

        ActivityLogger::log('Auth', 'login', "Logged in from IP: {$request->ip()}");

        return redirect()->intended('/dashboard');
    }

    public function destroy(Request $request)
    {
        // Signing out while viewing a farm as the platform admin means "leave the farm":
        // a real logout would sign the operator out entirely, file the entry under the
        // farm admin being viewed, and rotate that person's remember token.
        if ($request->session()->has('impersonator_id')) {
            return app(ImpersonationController::class)->stop($request);
        }

        ActivityLogger::log('Auth', 'logout', 'Logged out');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
