<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_no', 'customer_id', 'order_date', 'delivery_date',
        'item_name', 'quantity_kg', 'unit_price', 'total_amount',
        'payment_status', 'order_status', 'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}