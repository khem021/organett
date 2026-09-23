<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use BelongsToFarm;

    protected $fillable = [
        'farm_id', 'order_id', 'customer_id', 'sale_date',
        'quantity_kg', 'amount', 'payment_method', 'remarks',
    ];

    protected $casts = [
        'sale_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
