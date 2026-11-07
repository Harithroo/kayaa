<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Whether the storefront is open, and what to show when it is not.
 *
 * The mode lives in the settings table (Admin > Storefront > Site availability)
 * and is overlaid on config('kayaa.*') at boot, exactly like the contact details
 * and the store numbers, so everything here reads config.
 */
class SiteMode
{
    public const LIVE = 'live';

    public const COMING_SOON = 'coming_soon';

    public const MAINTENANCE = 'maintenance';

    /** Mode => admin label. The order here is the order shown in the admin. */
    public const LABELS = [
        self::LIVE => 'Live',
        self::COMING_SOON => 'Coming soon',
        self::MAINTENANCE => 'Under maintenance',
    ];

    public static function current(): string
    {
        $mode = (string) config('kayaa.site_mode', self::LIVE);

        return array_key_exists($mode, self::LABELS) ? $mode : self::LIVE;
    }

    public static function isLive(): bool
    {
        return static::current() === self::LIVE;
    }

    public static function isComingSoon(): bool
    {
        return static::current() === self::COMING_SOON;
    }

    public static function isMaintenance(): bool
    {
        return static::current() === self::MAINTENANCE;
    }

    public static function label(?string $mode = null): string
    {
        return self::LABELS[$mode ?? static::current()] ?? self::LABELS[self::LIVE];
    }

    /** True when signed-in admins may browse the real storefront behind the notice. */
    public static function adminBypassEnabled(): bool
    {
        return (bool) config('kayaa.site_mode_admin_bypass', true);
    }

    /**
     * True when the person asking is an admin allowed past the notice. Guarded:
     * a missing session or users table must not turn every page into a 500.
     */
    public static function viewerBypasses(): bool
    {
        if (! static::adminBypassEnabled()) {
            return false;
        }

        try {
            return (bool) Auth::user()?->is_admin;
        } catch (Throwable) {
            return false;
        }
    }

    /** The heading, message and date for whichever notice is up. */
    public static function notice(?string $mode = null): array
    {
        $mode = $mode ?? static::current();

        if ($mode === self::MAINTENANCE) {
            return [
                'mode' => self::MAINTENANCE,
                'icon' => 'i-wrench',
                'eyebrow' => 'Under maintenance',
                'heading' => (string) config('kayaa.maintenance.heading'),
                'message' => (string) config('kayaa.maintenance.message'),
                'date' => static::date('kayaa.maintenance.back_at'),
                'dateLabel' => 'Back online',
            ];
        }

        return [
            'mode' => self::COMING_SOON,
            'icon' => 'i-hourglass',
            'eyebrow' => 'Coming soon',
            'heading' => (string) config('kayaa.coming_soon.heading'),
            'message' => (string) config('kayaa.coming_soon.message'),
            'date' => static::date('kayaa.coming_soon.date'),
            'dateLabel' => 'Opening',
        ];
    }

    /** A stored date/time, or null when it is blank or unparseable. */
    private static function date(string $key): ?Carbon
    {
        $value = config($key);

        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
