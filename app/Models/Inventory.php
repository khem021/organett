<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use SoftDeletes;

    protected $table = 'inventory';

    protected $fillable = [
        'item_name', 'category', 'unit', 'stock_qty', 'reorder_level', 'location',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function isBelowReorderLevel(): bool
    {
        return $this->stock_qty < $this->reorder_level;
    }
}