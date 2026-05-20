<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HarvestRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'batch_id', 'harvest_date', 'quantity_kg',
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