<?php

namespace App\Support;

/**
 * Turns the raw contact numbers the admin types into links. Numbers are
 * entered the local way ("077 000 0000"); Sri Lanka's country code is added
 * for wa.me and tel: links.
 */
class StoreContact
{
    public const COUNTRY_CODE = '94';

    public static function email(): ?string
    {
        return config('kayaa.email') ?: null;
    }

    public static function phone(): ?string
    {
        return config('kayaa.phone') ?: null;
    }

    public static function whatsapp(): ?string
    {
        return config('kayaa.whatsapp') ?: null;
    }

    public static function phoneHref(): ?string
    {
        return ($n = static::phone()) ? 'tel:+'.static::international($n) : null;
    }

    public static function whatsappHref(): ?string
    {
        return ($n = static::whatsapp()) ? 'https://wa.me/'.static::international($n) : null;
    }

    /**
     * "077 000 0000" => "94770000000". A number already written with the
     * country code (+94..., 94...) is left alone.
     */
    public static function international(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number);

        if (str_starts_with($digits, '0')) {
            return static::COUNTRY_CODE.ltrim($digits, '0');
        }

        if (str_starts_with($digits, static::COUNTRY_CODE)) {
            return $digits;
        }

        return static::COUNTRY_CODE.$digits;
    }
}
