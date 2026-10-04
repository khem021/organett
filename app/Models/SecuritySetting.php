<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

/**
 * Super-admin kill switches for the platform's built-in protections.
 *
 * Not farm-scoped on purpose: both protections cover guest routes (login,
 * password reset) where no farm is known yet, so a switch applies platform-wide.
 */
class SecuritySetting extends Model
{
    public const HEADERS = 'security_headers';

    public const AUTH_THROTTLING = 'auth_throttling';

    /** Switchable protections, with the label and warning shown in the admin UI. */
    public const TOGGLES = [
        self::HEADERS => [
            'label' => 'Security headers & CSP',
            'covers' => 'Content-Security-Policy, X-Frame-Options, Referrer-Policy, Permissions-Policy and HSTS on every web response.',
            'risk' => 'Browsers stop blocking injected scripts, and any site can load ORGANETT inside a frame.',
        ],
        self::AUTH_THROTTLING => [
            'label' => 'Login & password-reset throttling',
            'covers' => 'Login capped at 5 tries per account and 20 per IP each minute; password resets at 3 per minute; sign-up form at 20 per minute.',
            'risk' => 'Passwords can be guessed without limit, and reset emails can be used to flood an inbox.',
        ],
    ];

    protected $fillable = ['setting_key', 'is_enabled', 'updated_by'];

    protected $casts = ['is_enabled' => 'boolean'];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Protections default to ON, and stay ON if the switch cannot be read at all
     * (table not migrated, cache store down). This is called on every web request,
     * so it must never take the app down and must never fail open.
     */
    public static function enabled(string $key): bool
    {
        try {
            return cache()->remember("security.{$key}", 300, function () use ($key) {
                $value = static::query()->where('setting_key', $key)->value('is_enabled');

                return $value === null ? true : (bool) $value;
            });
        } catch (Throwable) {
            return true;
        }
    }

    public static function setEnabled(string $key, bool $enabled, ?int $userId = null): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            ['is_enabled' => $enabled, 'updated_by' => $userId],
        );

        cache()->forget("security.{$key}");
    }

    /**
     * Current on/off state of every switchable protection.
     *
     * @return array<string, bool>
     */
    public static function states(): array
    {
        $states = [];

        foreach (array_keys(self::TOGGLES) as $key) {
            $states[$key] = static::enabled($key);
        }

        return $states;
    }
}
