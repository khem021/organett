<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    protected $fillable = [
        'name', 'slug', 'address', 'contact_email', 'contact_phone', 'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function productionBatches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(FarmFeature::class);
    }

    /**
     * Features are enabled by default. A feature is only "off" when an explicit
     * farm_features row exists with is_enabled = false. This matches how the
     * super-admin feature-flag screen and farm sign-up seed the flags.
     */
    public function hasFeature(string $key): bool
    {
        $record = $this->features()->where('feature_key', $key)->first();

        return $record === null ? true : (bool) $record->is_enabled;
    }
}
