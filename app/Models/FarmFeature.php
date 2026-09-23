<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmFeature extends Model
{
    protected $fillable = ['farm_id', 'feature_key', 'is_enabled'];

    protected $casts = ['is_enabled' => 'boolean'];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }
}
