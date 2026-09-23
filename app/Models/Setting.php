<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Setting extends Model
{
    use BelongsToFarm;

    public $timestamps = false;

    protected $fillable = ['farm_id', 'setting_key', 'setting_value', 'description'];

    private static function cacheKey(string $key): string
    {
        $farmId = Auth::user()?->farm_id ?? 'global';

        return "setting.{$farmId}.{$key}";
    }

    public static function getValue(string $key, string $default = ''): string
    {
        return cache()->remember(static::cacheKey($key), 86400, function () use ($key, $default) {
            return static::where('setting_key', $key)->value('setting_value') ?: $default;
        });
    }

    public static function flushCache(): void
    {
        foreach (static::pluck('setting_key') as $key) {
            cache()->forget(static::cacheKey($key));
        }
    }
}
