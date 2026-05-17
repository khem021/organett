<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'order_id', 'destination', 'delivery_date',
        'transport_status', 'assigned_personnel',
        'vehicle_info', 'remarks',
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}