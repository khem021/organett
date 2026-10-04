<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Record an action against the acting user's own farm.
     */
    public static function log(string $module, string $action, string $description): void
    {
        self::logForFarm(Auth::user()?->farm_id, $module, $action, $description);
    }

    /**
     * Record an action against a specific farm.
     *
     * Super admins have no farm of their own, so a platform action aimed at one
     * farm — approving it, resetting one of its accounts — would otherwise land
     * under "Platform" and never show up when filtering the audit by that farm.
     * Pass null for genuinely platform-wide actions such as the kill switches.
     */
    public static function logForFarm(?int $farmId, string $module, string $action, string $description): void
    {
        ActivityLog::create([
            'farm_id' => $farmId,
            'user_id' => Auth::user()?->getKey(),
            // Null in a console command, where there is no real request to read.
            'ip_address' => request()->ip(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
