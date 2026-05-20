<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(string $module, string $action, string $description): void
    {
        ActivityLog::create([
            'user_id'     => Auth::id(),
            'module'      => $module,
            'action'      => $action,
            'description' => $description,
        ]);
    }
}
