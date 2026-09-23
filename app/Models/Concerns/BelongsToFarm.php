<?php

namespace App\Models\Concerns;

use App\Models\Farm;
use App\Models\Scopes\FarmScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Adds multi-tenant behaviour to a model:
 *  - a global scope that limits every query to the current user's farm
 *  - a "creating" hook that stamps farm_id from the authenticated user
 *    so callers never have to remember to set it
 *
 * Super admins (farm_id === null) are never scoped and never stamped.
 */
trait BelongsToFarm
{
    protected static function bootBelongsToFarm(): void
    {
        static::addGlobalScope(new FarmScope);

        static::creating(function ($model) {
            if (! empty($model->farm_id)) {
                return;
            }

            $farmId = Auth::user()?->farm_id;

            if ($farmId) {
                $model->farm_id = $farmId;
            }
        });
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Query helper to bypass the farm scope (reporting, super-admin tools).
     */
    public function scopeAllFarms($query)
    {
        return $query->withoutGlobalScope(FarmScope::class);
    }
}
