<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Looked up in lower case, matching the login form.
        Password::sendResetLink([
            'email' => fn ($query) => $query->whereRaw('lower(email) = ?', [Str::lower(trim($request->input('email')))]),
        ]);

        // Same response whether or not the email exists, so accounts can't be enumerated.
        return back()->with('status', 'If that email is registered, a password reset link has been sent.');
    }
}
