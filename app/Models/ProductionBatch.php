<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionBatch extends Model
{
    use BelongsToFarm, SoftDeletes;

    protected $fillable = [
        'farm_id', 'batch_code', 'substrate_type', 'spawn_type',
        'inoculation_date', 'expected_harvest_date',
        'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'inoculation_date' => 'date',
        'expected_harvest_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function harvestRecords(): HasMany
    {
        return $this->hasMany(HarvestRecord::class, 'batch_id');
    }
}
