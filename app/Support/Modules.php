<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Show/hide switches for the GO modules. GoBus and GoRent are hidden for the
 * TaxiGo release but never deleted; turn them back on from Settings.
 */
class Modules
{
    protected static array $cache = [];

    public static function gobus(): bool
    {
        return static::enabled('gobus');
    }

    public static function gorent(): bool
    {
        return static::enabled('gorent');
    }

    /** Shared screens (providers, cities) stay visible while either module is on. */
    public static function anyLegacy(): bool
    {
        return static::gobus() || static::gorent();
    }

    public static function enabled(string $module): bool
    {
        // Missing setting = module visible, so nothing disappears before the seeder runs
        return static::$cache[$module] ??= (bool) (int) Setting::getValue("module_{$module}_enabled", '1');
    }

    public static function toArray(): array
    {
        return [
            'taxigo' => true,
            'gobus'  => static::gobus(),
            'gorent' => static::gorent(),
        ];
    }
}
