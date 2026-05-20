<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'batch_code', 'substrate_type', 'spawn_type',
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