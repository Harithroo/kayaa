<?php

namespace Tests\Feature\Storefront;

use App\Models\Product;
use Database\Seeders\CatalogStructureSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\SizeScaleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The shop's filter panel (App\Support\ShopFilters), against the demo catalogue:
 * 11 products in 7 categories.
 */
class ShopFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([SizeScaleSeeder::class, CatalogStructureSeeder::class, DemoProductSeeder::class]);
    }

    /** The product names on a listing page, in order. */
    private function names(string $url): array
    {
        preg_match_all('#<a class="product-card__link" href="[^"]*">([^<]*)</a>#', $this->get($url)->assertOk()->getContent(), $m);

        return array_map(fn ($n) => html_entity_decode($n), $m[1]);
    }

    public function test_the_shop_lists_everything_with_the_filter_panel(): void
    {
        $html = $this->get(route('shop.index'))->assertOk()->getContent();

        $this->assertCount(11, $this->names(route('shop.index')));
        $this->assertStringContainsString('<form class="filters" id="filters"', $html);
        foreach (['Category', 'Size', 'Colour', 'Price', 'Show only'] as $group) {
            $this->assertStringContainsString('<span>'.$group.'</span>', $html);
        }
        // Counts: Sleepwear has two styles, Napkins three.
        $this->assertMatchesRegularExpression('#value="sleepwear"[^>]*>.*?filter-option__count">2<#s', $html);
        $this->assertMatchesRegularExpression('#value="napkins"[^>]*>.*?filter-option__count">3<#s', $html);
        $this->assertStringContainsString('placeholder="1,250"', $html);
        $this->assertStringContainsString('placeholder="4,950"', $html);
    }

    public function test_categories_combine_as_alternatives(): void
    {
        $names = $this->names(route('shop.index', ['category' => ['sleepwear', 'outerwear']]));

        sort($names);
        $this->assertSame(['Bamboo Sleepsuit', 'Muslin Sleep Bag 0.5 TOG', 'Waffle Knit Cardigan'], $names);
    }

    public function test_size_accepts_the_plain_links_and_the_checkboxes(): void
    {
        // ?size=3Y is how the menus, the footer and the home page link to a size.
        $this->assertSame(3, count($this->names(route('shop.index', ['size' => '3Y']))));
        $this->assertSame(3, count($this->names(route('shop.index', ['size' => ['3Y']]))));

        // A size only counts when it is in stock: the Ribbed bodysuit is sold out in 6–9m.
        $this->assertNotContains('Ribbed Cotton Bodysuit', $this->names(route('shop.index', ['size' => ['6–9m']])));
        $this->assertContains('Ribbed Cotton Bodysuit', $this->names(route('shop.index', ['size' => ['6–9m', '0–3m']])));
    }

    public function test_colour_price_and_the_flags(): void
    {
        $this->assertSame(['Cotton Kurta Set', 'Waffle Knit Cardigan'], $this->sorted(route('shop.index', ['colour' => ['moss']])));

        $this->assertSame(['Burp Cloth & Napkin Duo', 'Mittens & Booties Set', 'Muslin Napkins 6-Pack', 'Reversible Bib Pair'],
            $this->sorted(route('shop.index', ['max' => 2000])));
        $this->assertSame(['Cotton Kurta Set', 'Muslin Sleep Bag 0.5 TOG'], $this->sorted(route('shop.index', ['min' => 4000])));
        // A reversed range is read the right way round.
        $this->assertSame($this->sorted(route('shop.index', ['min' => 1000, 'max' => 2000])), $this->sorted(route('shop.index', ['min' => 2000, 'max' => 1000])));

        $this->assertSame(['Ribbed Cotton Bodysuit'], $this->names(route('shop.index', ['sale' => 1])));
        $this->assertSame(Product::where('is_new', true)->count(), count($this->names(route('shop.index', ['new' => 1]))));

        Product::where('slug', 'pointelle-romper')->first()->variants()->update(['stock' => 0]);
        $this->assertNotContains('Pointelle Romper', $this->names(route('shop.index', ['stock' => 1])));
        $this->assertContains('Pointelle Romper', $this->names(route('shop.index')));
    }

    public function test_filters_combine_and_counts_leave_out_their_own_facet(): void
    {
        $url = route('shop.index', ['category' => ['napkins'], 'colour' => ['blush']]);
        $this->assertSame(['Bamboo Napkin Set', 'Burp Cloth & Napkin Duo'], $this->sorted($url));

        $html = $this->get($url)->getContent();
        // Other categories still show what they would add with Blush: the Ribbed bodysuit comes in Blush.
        $this->assertMatchesRegularExpression('#value="bodysuits"[^>]*>.*?filter-option__count">1<#s', $html);
        // The colour facet counts within Napkins: two napkin products come in Cloud.
        $this->assertMatchesRegularExpression('#value="cloud"[^>]*>.*?filter-option__count">2<#s', $html);
    }

    public function test_active_filters_show_as_chips_that_remove_themselves(): void
    {
        $html = $this->get(route('shop.index', ['category' => ['napkins', 'sets'], 'max' => 3000, 'sort' => 'price_asc']))->getContent();

        $this->assertStringContainsString('aria-label="Remove filter: Napkins"', $html);
        $this->assertStringContainsString('aria-label="Remove filter: Up to Rs. 3,000"', $html);
        $this->assertStringContainsString('<span class="filters__badge">3</span>', $html);

        // Removing Napkins keeps Sets, the price and the sort.
        preg_match('#href="([^"]*)" aria-label="Remove filter: Napkins"#', $html, $m);
        parse_str(parse_url(html_entity_decode($m[1]), PHP_URL_QUERY), $query);
        $this->assertSame(['category' => ['sets'], 'max' => '3000', 'sort' => 'price_asc'], $query);

        // Clear all keeps only the sort.
        $this->assertStringContainsString('<a class="active-filters__clear" href="'.e(route('shop.index').'?sort=price_asc').'">Clear all</a>', $html);
    }

    public function test_a_category_page_filters_inside_the_category_without_a_category_facet(): void
    {
        $category = \App\Models\Category::where('slug', 'napkins')->first();
        $html = $this->get($category->url().'?colour[]=white')->assertOk()->getContent();

        $this->assertStringNotContainsString('name="category[]"', $html);
        $this->assertSame(['Bamboo Napkin Set', 'Muslin Napkins 6-Pack'], $this->sorted($category->url().'?colour[]=white'));
        $this->assertStringContainsString('action="'.$category->url().'"', $html);
    }

    public function test_every_filter_makes_the_page_noindex(): void
    {
        $this->get(route('shop.index'))->assertSee('content="index,follow"', false);

        foreach (['category' => ['sets'], 'colour' => ['moss'], 'min' => 1000, 'max' => 2000, 'stock' => 1, 'new' => 1] as $key => $value) {
            $this->get(route('shop.index', [$key => $value]))->assertSee('content="noindex,follow"', false);
        }
    }

    public function test_nonsense_input_is_ignored(): void
    {
        $this->assertCount(11, $this->names(route('shop.index', ['min' => 'abc', 'colour' => [''], 'size' => ['']])));
        $this->get(route('shop.index', ['category' => ['no-such-category']]))->assertOk()->assertSee('No products match these filters');
    }

    private function sorted(string $url): array
    {
        $names = $this->names($url);
        sort($names);

        return $names;
    }
}
