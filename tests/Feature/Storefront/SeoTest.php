<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

/**
 * The guard rails for every page's search metadata.
 *
 * These exist so a later refactor of a controller or the layout cannot quietly
 * ship blank titles, duplicate canonicals or an indexable checkout — all of
 * which are invisible in the browser and take weeks to show up in Search
 * Console.
 */
class SeoTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    // ---------- titles and canonicals ----------

    public function test_a_product_page_has_one_h1_a_title_and_a_self_referencing_canonical(): void
    {
        $variant = $this->variant();
        $product = $variant->product;

        $html = $this->get(route('products.show', $product))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'), 'A page must have exactly one h1.');
        $this->assertStringContainsString('<link rel="canonical" href="'.route('products.show', $product).'">', $html);

        $title = $this->title($html);
        $this->assertNotSame('', $title);
        $this->assertLessThanOrEqual(70, mb_strlen($title), "Title is too long: {$title}");
        $this->assertStringContainsString($product->name, $title);
    }

    public function test_a_category_page_has_one_h1_a_title_and_a_self_referencing_canonical(): void
    {
        $this->variant();
        $category = Category::first();

        $html = $this->get($category->url())->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('<link rel="canonical" href="'.$category->url().'">', $html);
        $this->assertLessThanOrEqual(70, mb_strlen($this->title($html)));
    }

    public function test_every_public_page_ships_a_non_empty_title_within_the_limit(): void
    {
        $variant = $this->variant();
        $category = Category::first();

        $urls = [
            route('home'),
            route('shop.index'),
            route('categories.index'),
            route('search'),
            route('pages.size-guide'),
            route('pages.contact'),
            route('products.show', $variant->product),
            $category->url(),
            ...array_map(fn ($p) => route('pages.show', $p), ['about', 'delivery', 'returns', 'privacy', 'terms']),
        ];

        foreach ($urls as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $title = $this->title($html);

            $this->assertNotSame('', $title, "No title on {$url}");
            $this->assertLessThanOrEqual(70, mb_strlen($title), "Title too long on {$url}: {$title}");
            $this->assertStringContainsString('rel="canonical"', $html, "No canonical on {$url}");
            $this->assertMatchesRegularExpression(
                '/<meta name="description" content="[^"]+">/',
                $html,
                "No description on {$url}"
            );
        }
    }

    public function test_a_blank_meta_field_still_produces_a_title_and_description(): void
    {
        // The worst realistic case: a product saved with no description, no care
        // info and no SEO fields touched.
        $variant = $this->variant([], null, [
            'description' => null,
            'fabric_care' => null,
            'meta_title' => null,
            'meta_description' => null,
        ]);

        $html = $this->get(route('products.show', $variant->product))->assertOk()->getContent();

        $this->assertNotSame('', $this->title($html));
        $this->assertMatchesRegularExpression('/<meta name="description" content="[^"]+">/', $html);
    }

    public function test_an_admin_written_meta_title_wins_over_the_generated_one(): void
    {
        $variant = $this->variant([], null, ['meta_title' => 'Hand-written title for Google']);

        $this->get(route('products.show', $variant->product))
            ->assertOk()
            ->assertSee('<title>Hand-written title for Google</title>', false);
    }

    // ---------- the duplicate-content rule ----------

    public function test_a_filtered_category_url_is_noindex_follow_and_canonicals_to_the_clean_url(): void
    {
        $variant = $this->variant();
        $category = Category::first();

        $html = $this->get($category->url().'?size='.urlencode($variant->sizeOption->label).'&sort=price_asc')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<meta name="robots" content="noindex,follow">', $html);
        // The canonical must point at the unfiltered page, not the filtered one.
        $this->assertStringContainsString('<link rel="canonical" href="'.$category->url().'">', $html);
        $this->assertStringNotContainsString('canonical" href="'.$category->url().'?', $html);
    }

    public function test_an_unfiltered_category_url_is_indexable(): void
    {
        $this->variant();

        $this->get(Category::first()->url())
            ->assertOk()
            ->assertSee('<meta name="robots" content="index,follow">', false);
    }

    public function test_a_second_page_canonicals_to_itself(): void
    {
        $this->variant();
        $category = Category::first();

        $html = $this->get($category->url().'?page=2')->assertOk()->getContent();

        // Pagination is real navigation, unlike a filter: page 2 is its own page.
        $this->assertStringContainsString('<link rel="canonical" href="'.$category->url().'?page=2">', $html);
    }

    public function test_a_product_flagged_noindex_says_so_but_stays_reachable(): void
    {
        $variant = $this->variant([], null, ['noindex' => true]);

        $this->get(route('products.show', $variant->product))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow">', false);
    }

    public function test_search_results_are_noindex_follow(): void
    {
        $this->get(route('search', ['q' => 'romper']))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow">', false);
    }

    // ---------- private pages ----------

    public function test_private_pages_are_noindex_nofollow(): void
    {
        foreach ([route('cart.index'), route('orders.track')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
        }
    }

    public function test_the_account_area_is_noindex_nofollow(): void
    {
        $this->actingAs($this->customer());

        $this->get(route('account.index'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }

    public function test_checkout_is_noindex_nofollow(): void
    {
        $variant = $this->variant(['stock' => 5]);
        $this->post(route('cart.add'), ['variant_id' => $variant->id]);

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }

    // ---------- meta keywords ----------

    public function test_no_page_emits_meta_keywords(): void
    {
        $variant = $this->variant();

        $urls = [
            route('home'),
            route('shop.index'),
            route('products.show', $variant->product),
            route('cart.index'),
            route('pages.contact'),
            Category::first()->url(),
        ];

        foreach ($urls as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('name="keywords"', false);
        }
    }

    // ---------- JSON-LD ----------

    public function test_every_json_ld_block_on_a_product_page_is_valid_json(): void
    {
        $variant = $this->variant();

        $blocks = $this->jsonLdBlocks($this->get(route('products.show', $variant->product))->getContent());

        $this->assertNotEmpty($blocks, 'A product page must carry JSON-LD.');

        $types = array_column($blocks, '@type');
        $this->assertContains('Product', $types);
        $this->assertContains('BreadcrumbList', $types);
    }

    public function test_the_product_block_carries_the_price_in_rupees_and_its_availability(): void
    {
        $variant = $this->variant(['price' => 185000, 'stock' => 4]);

        $product = $this->blockOfType(
            $this->jsonLdBlocks($this->get(route('products.show', $variant->product))->getContent()),
            'Product'
        );

        $this->assertSame('Offer', $product['offers']['@type']);
        // Prices are stored in cents; schema.org wants the major unit.
        $this->assertSame('1850.00', $product['offers']['price']);
        $this->assertSame('LKR', $product['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/InStock', $product['offers']['availability']);
    }

    public function test_mixed_variant_prices_become_an_aggregate_offer(): void
    {
        $cheap = $this->variant(['price' => 150000]);
        $this->variant(['price' => 320000], $cheap->product);

        $product = $this->blockOfType(
            $this->jsonLdBlocks($this->get(route('products.show', $cheap->product))->getContent()),
            'Product'
        );

        $this->assertSame('AggregateOffer', $product['offers']['@type']);
        $this->assertSame('1500.00', $product['offers']['lowPrice']);
        $this->assertSame('3200.00', $product['offers']['highPrice']);
    }

    public function test_a_product_with_no_stock_is_marked_out_of_stock(): void
    {
        $variant = $this->variant(['stock' => 0]);

        $product = $this->blockOfType(
            $this->jsonLdBlocks($this->get(route('products.show', $variant->product))->getContent()),
            'Product'
        );

        $this->assertSame('https://schema.org/OutOfStock', $product['offers']['availability']);
    }

    public function test_a_product_with_no_reviews_emits_no_rating(): void
    {
        $variant = $this->variant();

        $product = $this->blockOfType(
            $this->jsonLdBlocks($this->get(route('products.show', $variant->product))->getContent()),
            'Product'
        );

        // Marking up a rating the page does not show is a manual-action offence.
        $this->assertArrayNotHasKey('aggregateRating', $product);
        $this->assertArrayNotHasKey('review', $product);
    }

    public function test_an_unapproved_review_does_not_produce_a_rating(): void
    {
        $variant = $this->variant();
        $this->review($variant->product, ['is_approved' => false]);

        $product = $this->blockOfType(
            $this->jsonLdBlocks($this->get(route('products.show', $variant->product))->getContent()),
            'Product'
        );

        $this->assertArrayNotHasKey('aggregateRating', $product);
    }

    public function test_an_approved_review_produces_a_matching_rating(): void
    {
        $variant = $this->variant();
        $this->review($variant->product, ['rating' => 5]);
        $this->review($variant->product, ['rating' => 4]);

        $product = $this->blockOfType(
            $this->jsonLdBlocks($this->get(route('products.show', $variant->product))->getContent()),
            'Product'
        );

        $this->assertSame('4.5', $product['aggregateRating']['ratingValue']);
        $this->assertSame(2, $product['aggregateRating']['reviewCount']);
        $this->assertCount(2, $product['review']);
    }

    public function test_the_home_page_carries_organization_and_website(): void
    {
        $types = array_column($this->jsonLdBlocks($this->get(route('home'))->getContent()), '@type');

        $this->assertContains('Organization', $types);
        $this->assertContains('WebSite', $types);
    }

    public function test_the_breadcrumb_markup_matches_the_visible_trail(): void
    {
        $variant = $this->variant();
        $html = $this->get(route('products.show', $variant->product))->getContent();

        $crumbs = $this->blockOfType($this->jsonLdBlocks($html), 'BreadcrumbList');
        $names = array_column($crumbs['itemListElement'], 'name');

        $this->assertSame('Home', $names[0]);
        $this->assertSame($variant->product->name, end($names));

        // Every name in the markup must also be text on the page.
        foreach ($names as $name) {
            $this->assertStringContainsString(e($name), $html, "Breadcrumb \"{$name}\" is marked up but not shown.");
        }

        // The last crumb is the current page and carries no link.
        $this->assertArrayNotHasKey('item', $crumbs['itemListElement'][count($names) - 1]);
    }

    // ---------- open graph ----------

    public function test_a_product_page_carries_an_absolute_og_url(): void
    {
        $variant = $this->variant();

        $this->get(route('products.show', $variant->product))
            ->assertOk()
            ->assertSee('<meta property="og:url" content="'.route('products.show', $variant->product).'">', false)
            ->assertSee('<meta property="og:type" content="product">', false);
    }

    // ---------- helpers ----------

    private function title(string $html): string
    {
        preg_match('/<title>(.*?)<\/title>/s', $html, $m);

        return trim(html_entity_decode($m[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /** @return array<int, array<string, mixed>> */
    private function jsonLdBlocks(string $html): array
    {
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);

        $blocks = [];

        foreach ($m[1] as $raw) {
            $decoded = json_decode($raw, true);

            $this->assertNotNull($decoded, 'Invalid JSON-LD: '.json_last_error_msg()."\n".$raw);

            $blocks[] = $decoded;
        }

        return $blocks;
    }

    /** @param  array<int, array<string, mixed>>  $blocks */
    private function blockOfType(array $blocks, string $type): array
    {
        foreach ($blocks as $block) {
            if (($block['@type'] ?? null) === $type) {
                return $block;
            }
        }

        $this->fail("No {$type} block on the page.");
    }

    private function review(Product $product, array $overrides = []): Review
    {
        return Review::create([
            'product_id' => $product->id,
            'name' => 'Nimali',
            'rating' => 5,
            'body' => 'Lovely soft cotton, washed well.',
            'is_approved' => true,
            'approved_at' => now(),
            ...$overrides,
        ]);
    }
}
