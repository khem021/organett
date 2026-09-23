<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use BelongsToFarm, SoftDeletes;

    protected $table = 'inventory';

    protected $fillable = [
        'farm_id', 'item_name', 'category', 'unit', 'stock_qty', 'reorder_level', 'location',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }
}
