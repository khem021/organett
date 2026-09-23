<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'full_name', 'username', 'email', 'password', 'profile_photo',
        'farm_id', 'role', 'status',
    ];

    protected $hidden = ['password'];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isFarmAdmin(): bool
    {
        return $this->role === 'farm_admin';
    }
}
