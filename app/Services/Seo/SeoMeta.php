<?php

namespace App\Services\Seo;

use App\Models\Category;
use App\Models\Product;
use App\Support\Seo\MetaImage;
use App\Support\Seo\MetaText;
use Illuminate\Http\Request;

/**
 * Everything the <head> needs for one page, resolved once in the controller.
 *
 * Two rules live here and nowhere else:
 *
 *  1. A canonical URL never carries a query string. The single exception is
 *     ?page=, where a paginated listing is self-referencing including the page
 *     number — page 2 is genuinely a different page, a filtered view is not.
 *  2. Any filter parameter makes the page noindex,follow. This is what stops the
 *     catalog minting thousands of thin, near-identical URLs for Google to crawl
 *     instead of the pages that matter.
 */
final class SeoMeta
{
    /**
     * Query parameters that narrow or reorder a listing without producing a page
     * worth indexing. These are ShopController's real parameters — a new filter
     * must be added here too, or it will quietly start generating duplicates.
     */
    public const FILTER_PARAMS = ['size', 'sort', 'tag', 'sale'];

    /**
     * Parameters stripped from the canonical but which do not trigger noindex:
     * same content, just expanded.
     */
    public const COSMETIC_PARAMS = ['reviews'];

    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $canonical,
        public readonly string $robots,
        public readonly string $ogTitle,
        public readonly string $ogDescription,
        public readonly ?string $ogImage,
        public readonly string $ogType,
        public readonly string $ogUrl,
        public readonly string $twitterCard,
    ) {}

    public static function forProduct(Product $product, Request $request): self
    {
        return self::make(
            title: $product->metaTitle(),
            description: $product->metaDescription(),
            request: $request,
            // A product page's canonical is the product URL, never the request:
            // ?reviews=all and any stray campaign parameter resolve to one page.
            canonical: $product->canonicalUrl(),
            robots: self::robotsFor($request, $product->noindex),
            ogImage: $product->ogImageUrl(),
            ogType: 'product',
        );
    }

    public static function forCategory(Category $category, Request $request): self
    {
        return self::make(
            title: $category->metaTitle(),
            description: $category->metaDescription(),
            request: $request,
            // Pagination is the one query parameter kept, so page 2 points at
            // itself rather than at page 1.
            canonical: self::paginatedCanonical($category->canonicalUrl(), $request),
            robots: self::robotsFor($request, $category->noindex),
            ogImage: $category->ogImageUrl(),
        );
    }

    /**
     * A page with no model behind it. $key indexes config('seo.pages').
     */
    public static function forPage(string $key, Request $request): self
    {
        $page = (array) config('seo.pages.'.$key, []);
        $suffix = trim((string) config('seo.title_suffix', 'Kayaa'));

        $title = trim((string) ($page['title'] ?? ''));
        // The home page leads with the brand; every other page trails it.
        $full = match (true) {
            $title === '' => $suffix,
            $suffix === '' => $title,
            $key === 'home' => $suffix.': '.$title,
            default => $title.' | '.$suffix,
        };

        $description = MetaText::summarise($page['description'] ?? null, 170)
            ?? config('seo.pages.home.description');

        return self::make(
            title: MetaText::clamp($full, 70),
            description: (string) $description,
            request: $request,
            canonical: self::paginatedCanonical($request->url(), $request),
            // Search results and any other filter-driven page stay out of the
            // index but still pass link equity to the products they list.
            robots: in_array($key, ['search', 'wishlist'], true)
                ? 'noindex,follow'
                : self::robotsFor($request, false),
            ogImage: MetaImage::storeDefault(),
        );
    }

    /**
     * Pages that must never be indexed: anything holding a basket, an address or
     * an order. nofollow as well — there is nothing on them worth crawling.
     */
    public static function noindex(Request $request, ?string $title = null): self
    {
        $suffix = trim((string) config('seo.title_suffix', 'Kayaa'));

        return self::make(
            title: MetaText::clamp($title ? $title.' | '.$suffix : $suffix, 70),
            description: '',
            request: $request,
            canonical: $request->url(),
            robots: 'noindex,nofollow',
            ogImage: null,
        );
    }

    private static function make(
        string $title,
        string $description,
        Request $request,
        string $canonical,
        string $robots,
        ?string $ogImage,
        string $ogType = 'website',
    ): self {
        return new self(
            title: $title,
            description: $description,
            canonical: $canonical,
            robots: $robots,
            // Open Graph mirrors the page's own title and description; a
            // separately-worded share card is a maintenance trap, not a feature.
            ogTitle: $title,
            ogDescription: $description,
            ogImage: $ogImage,
            ogType: $ogType,
            // og:url is the canonical, not the request: a link shared with
            // tracking parameters should still resolve to the one page.
            ogUrl: $canonical,
            twitterCard: (string) config('seo.twitter_card', 'summary_large_image'),
        );
    }

    /**
     * Keep ?page= and drop everything else. Page 1 is written without the
     * parameter so /baby/bodysuits and ?page=1 do not compete.
     */
    private static function paginatedCanonical(string $url, Request $request): string
    {
        $page = (int) $request->query('page', 1);

        return $page > 1 ? $url.'?page='.$page : $url;
    }

    private static function robotsFor(Request $request, bool $recordNoindex): string
    {
        // Staging switch, set with APP_NOINDEX. Nothing overrides it.
        if (config('kayaa.noindex')) {
            return 'noindex,nofollow';
        }

        // An admin excluding one record still wants its links crawled.
        if ($recordNoindex || self::isFiltered($request)) {
            return 'noindex,follow';
        }

        return 'index,follow';
    }

    public static function isFiltered(Request $request): bool
    {
        foreach (self::FILTER_PARAMS as $param) {
            // Present but empty (?size=) is not a filter — it is how the "all
            // sizes" chip submits.
            if (filled($request->query($param))) {
                return true;
            }
        }

        return false;
    }
}
