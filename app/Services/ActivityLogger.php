<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(string $module, string $action, string $description): void
    {
        $user = Auth::user();

        ActivityLog::create([
            'farm_id' => $user?->farm_id,
            'user_id' => $user?->getKey(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
