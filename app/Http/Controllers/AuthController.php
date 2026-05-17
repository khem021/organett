<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
            'status' => 'active',
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'module' => 'Authentication',
                'action' => 'Login',
                'description' => Auth::user()->username . ' logged in.',
            ]);

            return redirect()->intended('/');
        }

        return back()->with('error', 'Invalid login credentials.');
    }

    public function logout(Request $request)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'module' => 'Authentication',
            'action' => 'Logout',
            'description' => Auth::user()->username . ' logged out.',
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}