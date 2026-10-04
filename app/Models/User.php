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

    /**
     * Why this person cannot use the application right now, or null if they can.
     *
     * Shared by the login form and the per-request check, so a person is told the
     * same thing whether they are being turned away at the door or were already
     * inside when their farm was closed. The account's own status wins over the farm's.
     */
    public function lockoutReason(): ?string
    {
        if ($this->status !== 'active') {
            return 'Your account has been deactivated. Please contact the administrator.';
        }

        // The platform owner belongs to no farm; a farm-less member sees nothing anyway.
        if ($this->role === 'super_admin' || $this->farm_id === null) {
            return null;
        }

        // An archived farm is soft-deleted, so the relation resolves to nothing.
        $farm = $this->farm;

        if (! $farm) {
            return 'Your farm has been archived. Please contact Organett support.';
        }

        return match ($farm->status) {
            'active' => null,
            'pending' => 'Your farm registration is awaiting approval by a platform administrator. You can sign in once it has been approved.',
            'rejected' => 'Your farm registration was not approved. Please contact Organett support.',
            default => 'Your farm has been suspended. Please contact Organett support.',
        };
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
