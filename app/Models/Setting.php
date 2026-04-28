<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'is_sensitive',
    ];

    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->where('is_active', 1)->first();
        return $setting ? $setting->value : $default;
    }
}