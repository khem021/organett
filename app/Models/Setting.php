<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;

    protected $fillable = ['setting_key', 'setting_value', 'description'];

    public static function getValue(string $key, string $default = ''): string
    {
        return static::where('setting_key', $key)->value('setting_value') ?: $default;
    }
}