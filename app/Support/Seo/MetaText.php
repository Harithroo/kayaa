<?php

namespace App\Support\Seo;

use Illuminate\Support\Str;

/**
 * The one place prose is turned into a meta tag value.
 *
 * Product and category copy is admin-entered and may carry tags, entities and
 * ragged whitespace; a meta description has to be one clean line, cut on a word
 * boundary. Both the title and the description paths go through here so the two
 * can never drift apart.
 */
final class MetaText
{
    /**
     * Flatten prose to a single line of plain text. Returns null when nothing
     * is left, so callers can fall through to the next fallback with `?:`.
     */
    public static function flatten(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        // Tags first, then entities. Decoding first would let strip_tags eat the
        // text around a &lt;b&gt;, because once decoded it is a real tag.
        $plain = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Decoding can itself reveal markup: copy pasted into the admin as
        // "&lt;b&gt;bold&lt;/b&gt;" survives the strip above and comes out of
        // the decode as a literal <b> tag, which would then show as angle
        // brackets in the search result. Remove what is now plainly a tag.
        //
        // Matched narrowly rather than with a second strip_tags() pass: that
        // treats "Fits 5 < 10 kg" as an unterminated tag and deletes the rest
        // of the sentence. This pattern needs a letter after the < and a
        // closing >, so prose with a stray comparison is left alone.
        $plain = (string) preg_replace('/<\/?[a-zA-Z][^<>]*>/', '', $plain);

        // Collapse every run of whitespace, newlines included, to one space.
        $plain = trim((string) preg_replace('/\s+/u', ' ', $plain));

        return $plain === '' ? null : $plain;
    }

    /**
     * Flatten, then cut to $limit characters on a word boundary. No mid-word
     * ellipsis: a description that ends "organic cott…" reads as broken.
     */
    public static function summarise(?string $text, int $limit = 155): ?string
    {
        $plain = self::flatten($text);

        if ($plain === null) {
            return null;
        }

        return self::clamp($plain, $limit);
    }

    /**
     * Cut an already-flat string to $limit characters on a word boundary.
     *
     * Str::limit with an empty end marker does the work and is multibyte-safe;
     * it cuts mid-word, so the result is then trimmed back to the last space.
     * A single word longer than the limit is returned hard-cut rather than
     * emptied — an over-long title is better than no title.
     */
    public static function clamp(string $text, int $limit): string
    {
        $text = trim($text);

        if ($text === '' || mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = Str::limit($text, $limit, '');
        $lastSpace = mb_strrpos($cut, ' ');

        // Only back off to the word boundary when doing so keeps most of the
        // budget; otherwise the single long word is all there is.
        if ($lastSpace !== false && $lastSpace > (int) ($limit * 0.6)) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        // Trailing punctuation left dangling by the cut reads as a typo.
        // preg with /u rather than rtrim: rtrim's charlist is byte-based, and
        // these separators are multibyte, so it could cut into a character.
        return (string) preg_replace('/[\s,;:·—–-]+$/u', '', $cut);
    }
}
