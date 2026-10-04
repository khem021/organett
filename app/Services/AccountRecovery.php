<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountRecovery
{
    /**
     * Set a new password on an existing account and sign it out everywhere else.
     *
     * Shared by the organett:reset-password console command and the super admin
     * recovery screen, so both paths behave identically.
     *
     * @return int how many stored sessions were cleared
     */
    public static function resetPassword(User $user, string $plain, string $description): int
    {
        // Rotating the token and dropping stored sessions signs out anyone still
        // holding the old credential on another device.
        $user->forceFill([
            'password' => Hash::make($plain),
            'remember_token' => Str::random(60),
        ])->save();

        $cleared = DB::table('sessions')->where('user_id', $user->getKey())->delete();

        // Written directly rather than through ActivityLogger so the entry lands
        // on the farm that owns the account, not the actor's own farm. From the
        // web the actor is the super admin; in console there is none, so the
        // entry falls back to the account being reset.
        ActivityLog::create([
            'farm_id' => $user->farm_id,
            'user_id' => Auth::id() ?? $user->getKey(),
            'ip_address' => request()->ip(),
            'module' => 'users',
            'action' => 'password_reset',
            'description' => $description,
        ]);

        return $cleared;
    }
}
