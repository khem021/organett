<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\User;
use App\Services\AccountRecovery;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function resetPassword(Request $request, Farm $farm, User $user)
    {
        $this->guard($farm, $user);

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $cleared = AccountRecovery::resetPassword(
            $user,
            $request->password,
            "Password reset by a platform administrator for {$user->email}.",
        );

        return back()->with('status', "Password reset for {$user->email}. Cleared {$cleared} active session(s).");
    }

    public function updateStatus(Request $request, Farm $farm, User $user)
    {
        $this->guard($farm, $user);

        $request->validate(['status' => ['required', 'in:active,inactive']]);

        // Deactivating a farm's only administrator leaves nobody able to run it.
        if ($request->status === 'inactive' && $user->role === 'farm_admin') {
            $remaining = User::where('farm_id', $farm->id)
                ->where('role', 'farm_admin')
                ->where('status', 'active')
                ->where('id', '!=', $user->id)
                ->count();

            if ($remaining === 0) {
                return back()->with('error', "Cannot deactivate {$user->full_name} — they are the last active administrator for this farm.");
            }
        }

        $user->update(['status' => $request->status]);

        ActivityLogger::logForFarm(
            $farm->id,
            'Users',
            $request->status === 'active' ? 'enable' : 'suspend',
            "Set account {$user->email} to {$request->status} from the platform admin.",
        );

        return back()->with('status', "{$user->full_name} is now {$request->status}.");
    }

    /**
     * Super admins bypass FarmScope, so a nested {farm}/{user} pair is not checked
     * for consistency by anything else — a mismatched pair would otherwise let one
     * farm's screen act on another farm's account.
     */
    private function guard(Farm $farm, User $user): void
    {
        abort_unless($user->farm_id === $farm->id, 404);

        // Platform accounts stay console-only; nothing here should be able to
        // reset or disable a fellow super admin through the web.
        abort_if($user->role === 'super_admin', 403);
    }
}
