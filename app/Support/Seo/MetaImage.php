<?php

namespace App\Support\Seo;

use Illuminate\Support\Facades\Storage;

/**
 * The one place a share image is turned into an absolute URL.
 *
 * og:image must be absolute. WhatsApp and Instagram silently drop a relative
 * one, and since a pasted product link is how this store is mostly shared, a
 * missing preview is a real cost rather than a cosmetic one.
 */
final class MetaImage
{
    /** Resolve a possibly-relative URL against the site root. */
    public static function absolute(string $url): string
    {
        return str_starts_with($url, 'http') ? $url : url($url);
    }

    /**
     * Resolve a stored setting that may hold either a public-disk path or an
     * already-absolute URL. Null when it holds neither.
     */
    public static function fromPathOrUrl(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return str_starts_with($value, 'http')
            ? $value
            : self::absolute(Storage::disk('public')->url($value));
    }

    /**
     * The store-wide fallback share image, set in Admin > Store settings.
     * Null when none is configured — better no og:image than a broken one.
     */
    public static function storeDefault(): ?string
    {
        return self::fromPathOrUrl(config('seo.default_og_image'));
    }
}
