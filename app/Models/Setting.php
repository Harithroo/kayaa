<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const CACHE_KEY = 'kayaa.settings';

    /**
     * All settings as key => value. Cached, and deliberately fault-tolerant:
     * if the table is missing (migration not run on the server yet) the store
     * falls back to config instead of throwing a 500 on every page.
     *
     * @return array<string, string|null>
     */
    public static function map(): array
    {
        // The whole lookup is guarded, cache included: during `composer install` in CI
        // (package:discover) there is no .env and no database, so even reading the
        // database cache store throws. A failure is not cached, so settings apply as
        // soon as the table exists instead of staying empty until the cache is cleared.
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            return [];
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::map()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
