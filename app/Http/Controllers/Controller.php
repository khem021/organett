<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * Namespace a cache key to the current user's farm so cached lookups
     * (dropdowns, category lists, …) never leak between tenants.
     */
    protected function farmCacheKey(string $key): string
    {
        return $key.'.farm.'.(Auth::user()?->farm_id ?? 'none');
    }
}
