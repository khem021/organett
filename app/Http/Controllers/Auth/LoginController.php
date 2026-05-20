<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (Auth::user()->status !== 'active') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Please contact the administrator.',
            ]);
        }

        $request->session()->regenerate();

        ActivityLogger::log('Auth', 'login', "Logged in from IP: {$request->ip()}");

        return redirect()->intended('/dashboard');
    }

    public function destroy(Request $request)
    {
        ActivityLogger::log('Auth', 'logout', 'Logged out');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
