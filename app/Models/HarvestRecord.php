<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HarvestRecord extends Model
{
    use BelongsToFarm, SoftDeletes;

    protected $fillable = [
        'farm_id', 'batch_id', 'harvest_date', 'quantity_kg',
        'quality_grade', 'notes', 'created_by',
    ];

    protected $casts = [
        'harvest_date' => 'date',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'batch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
