<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'inventory_id', 'transaction_type', 'quantity',
        'transaction_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}