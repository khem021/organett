<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AccountRecovery;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRules;

class ResetPasswordController extends Controller
{
    public function create(Request $request)
    {
        return view('auth.reset-password', ['token' => $request->route('token')]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRules::defaults()],
        ]);

        $status = Password::reset(
            // The email is matched in lower case, as on the login and forgot-password forms.
            [
                'email' => fn ($query) => $query->whereRaw('lower(email) = ?', [Str::lower(trim($request->input('email')))]),
                ...$request->only('password', 'password_confirmation', 'token'),
            ],
            function ($user, $password) {
                // Same routine as the admin and console resets: it also signs the account
                // out of every other device and leaves an entry in the audit trail, which
                // matters most when the reset was done because a password had been stolen.
                AccountRecovery::resetPassword($user, $password, "Password reset by the account owner through an emailed link ({$user->email}).");

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
