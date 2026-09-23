<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use BelongsToFarm;

    protected $fillable = [
        'farm_id', 'alert_type', 'message', 'severity',
        'source_module', 'reference_id', 'is_resolved',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_resolved', false);
    }
}
