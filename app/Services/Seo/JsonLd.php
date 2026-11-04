<?php

namespace App\Services\Seo;

use App\Models\Faq;
use App\Models\Product;
use App\Support\Seo\MetaImage;
use App\Support\Seo\MetaText;
use Illuminate\Support\Collection;

/**
 * schema.org structured data, as plain arrays. No rendering here — the
 * <x-json-ld> component encodes whatever these return.
 *
 * The governing rule, and the reason several arguments are passed in rather
 * than queried: never mark up something the page does not show. Structured data
 * that disagrees with the visible page is a manual-action offence, and review
 * markup is the single most commonly penalised type. So the caller hands over
 * the reviews it actually rendered, and availability is read from the same
 * figure that draws the stock badge.
 */
final class JsonLd
{
    public const CONTEXT = 'https://schema.org';

    /**
     * Product + Offer: the block that earns price and stock in the result.
     *
     * @param  Collection<int, \App\Models\Review>|null  $renderedReviews
     *   The approved reviews visible on this page. Null or empty means no
     *   rating is emitted at all — a rating is never synthesised.
     */
    public static function product(Product $product, ?Collection $renderedReviews = null): array
    {
        $product->loadMissing(['images', 'variants.sizeOption', 'category']);

        $data = [
            '@context' => self::CONTEXT,
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->metaDescription(),
            'url' => $product->canonicalUrl(),
            'itemCondition' => self::CONTEXT.'/NewCondition',
            'brand' => [
                '@type' => 'Brand',
                'name' => (string) config('seo.org.name', 'Kayaa'),
            ],
        ];

        $images = $product->images
            ->map(fn ($image) => MetaImage::absolute($image->url()))
            ->filter()
            ->values()
            ->all();

        if ($images !== []) {
            $data['image'] = $images;
        }

        if ($product->category) {
            $data['category'] = $product->category->name;
        }

        $skus = $product->activeVariants()->pluck('sku')->filter()->values();

        // One variant: its SKU identifies the product. Several: the SKUs belong
        // to the offers, not to the parent, so only a single one is lifted here.
        if ($skus->count() === 1) {
            $data['sku'] = (string) $skus->first();
        }

        $offers = self::offers($product);

        if ($offers !== null) {
            $data['offers'] = $offers;
        }

        $rating = self::aggregateRating($product, $renderedReviews);

        if ($rating !== null) {
            $data['aggregateRating'] = $rating;
            $data['review'] = self::reviews($renderedReviews);
        }

        return $data;
    }

    /**
     * A single Offer when every active variant shares a price, an AggregateOffer
     * otherwise. Null when there is nothing sellable — an offer with no price is
     * worse than no offer.
     */
    private static function offers(Product $product): ?array
    {
        $variants = $product->activeVariants();

        if ($variants->isEmpty()) {
            return null;
        }

        $prices = $variants->pluck('price')->map(fn ($p) => (int) $p)->filter(fn ($p) => $p > 0);

        if ($prices->isEmpty()) {
            return null;
        }

        // Read stock from the same expression the page badge uses
        // (resources/views/store/product.blade.php), so the markup and the
        // button can never disagree about whether this is buyable.
        $availability = self::CONTEXT.($product->totalStock() > 0 ? '/InStock' : '/OutOfStock');
        $currency = (string) config('kayaa.currency', 'LKR');
        $url = $product->canonicalUrl();

        $low = $prices->min();
        $high = $prices->max();

        if ($low === $high) {
            return [
                '@type' => 'Offer',
                'url' => $url,
                'priceCurrency' => $currency,
                'price' => self::money($low),
                'availability' => $availability,
            ];
        }

        return [
            '@type' => 'AggregateOffer',
            'url' => $url,
            'priceCurrency' => $currency,
            'lowPrice' => self::money($low),
            'highPrice' => self::money($high),
            'offerCount' => $variants->count(),
            'availability' => $availability,
        ];
    }

    /**
     * Only when approved reviews exist AND are on the page. The count is the
     * product's full approved count, which is what the page's rating summary
     * shows; the individual `review` entries are the ones rendered.
     */
    private static function aggregateRating(Product $product, ?Collection $renderedReviews): ?array
    {
        if ($renderedReviews === null || $renderedReviews->isEmpty()) {
            return null;
        }

        $average = $product->ratingAverage();
        $count = $product->ratingCount();

        if ($average === null || $count < 1) {
            return null;
        }

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $average,
            'reviewCount' => $count,
            'bestRating' => '5',
            'worstRating' => '1',
        ];
    }

    private static function reviews(?Collection $renderedReviews): array
    {
        return ($renderedReviews ?? collect())
            ->map(fn ($review) => array_filter([
                '@type' => 'Review',
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => (string) $review->rating,
                    'bestRating' => '5',
                    'worstRating' => '1',
                ],
                'author' => ['@type' => 'Person', 'name' => $review->name],
                'datePublished' => $review->created_at?->toDateString(),
                'name' => $review->title ? MetaText::flatten($review->title) : null,
                'reviewBody' => MetaText::flatten($review->body),
            ]))
            ->values()
            ->all();
    }

    /**
     * BreadcrumbList from a trail built by App\Support\Seo\Breadcrumbs, which is
     * the same array the visible <nav> renders from.
     *
     * @param  array<int, array{name: string, url: ?string}>  $trail
     */
    public static function breadcrumbs(array $trail): ?array
    {
        if (count($trail) < 2) {
            return null;
        }

        $items = [];

        foreach (array_values($trail) as $i => $crumb) {
            $items[] = array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
                // The current page's crumb carries no url, which is valid for
                // the final item.
                'item' => $crumb['url'] ?? null,
            ], fn ($v) => $v !== null);
        }

        return [
            '@context' => self::CONTEXT,
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Organization for the home page. `sameAs` carries the Instagram and
     * Facebook profiles, which is how most of this store's traffic already
     * knows it, so stating the connection is worth more here than usual.
     */
    public static function organization(): array
    {
        $org = (array) config('seo.org', []);

        $sameAs = collect([$org['instagram'] ?? null, $org['facebook'] ?? null])
            ->filter()
            ->values()
            ->all();

        $logo = $org['logo_path'] ?? null;

        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'Organization',
            'name' => $org['name'] ?? 'Kayaa',
            'url' => url('/'),
            'logo' => MetaImage::fromPathOrUrl($logo),
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'addressLocality' => $org['city'] ?? null,
                'addressRegion' => $org['region'] ?? null,
                'addressCountry' => $org['country'] ?? 'LK',
            ]),
            // Reuses the contact details the admin already maintains under
            // Settings > Contact, rather than storing a second phone number.
            'contactPoint' => self::contactPoint(),
            'sameAs' => $sameAs === [] ? null : $sameAs,
        ], fn ($v) => $v !== null && $v !== []);
    }

    private static function contactPoint(): ?array
    {
        $phone = trim((string) config('kayaa.phone', ''));
        $email = trim((string) config('kayaa.email', ''));

        if ($phone === '' && $email === '') {
            return null;
        }

        return array_filter([
            '@type' => 'ContactPoint',
            'contactType' => 'customer service',
            'telephone' => $phone === '' ? null : $phone,
            'email' => $email === '' ? null : $email,
            'areaServed' => 'LK',
            'availableLanguage' => ['en', 'si', 'ta'],
        ], fn ($v) => $v !== null);
    }

    /**
     * WebSite with a SearchAction, which is what makes a site-links search box
     * possible in the result.
     */
    public static function webSite(): array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'WebSite',
            'name' => (string) config('seo.site_name', 'Kayaa'),
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('search').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * FAQPage from the faqs the page renders. Takes the collection rather than
     * querying, for the same reason reviews do: the markup must match the page.
     *
     * @param  Collection<int, Faq>  $faqs
     */
    public static function faqPage(Collection $faqs): ?array
    {
        if ($faqs->isEmpty()) {
            return null;
        }

        return [
            '@context' => self::CONTEXT,
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn (Faq $faq) => [
                '@type' => 'Question',
                'name' => MetaText::flatten($faq->question),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => MetaText::flatten($faq->answer),
                ],
            ])->values()->all(),
        ];
    }

    /** Cents to a schema.org price string: 185000 -> "1850.00". */
    private static function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
