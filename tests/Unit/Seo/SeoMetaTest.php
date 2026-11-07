<?php

namespace Tests\Unit\Seo;

use App\Models\Category;
use App\Services\Seo\SeoMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

/**
 * The two rules that keep the catalogue from generating duplicate pages:
 * canonicals carry no query string, and any filter means noindex,follow.
 */
class SeoMetaTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    public function test_a_canonical_drops_filter_and_sort_parameters(): void
    {
        $category = $this->category();

        $meta = SeoMeta::forCategory($category, $this->request($category->url(), [
            'size' => '0-3m',
            'sort' => 'price_asc',
        ]));

        $this->assertSame($category->url(), $meta->canonical);
        $this->assertStringNotContainsString('?', $meta->canonical);
    }

    public function test_a_canonical_keeps_the_page_number(): void
    {
        $category = $this->category();

        $meta = SeoMeta::forCategory($category, $this->request($category->url(), ['page' => 2]));

        $this->assertSame($category->url().'?page=2', $meta->canonical);
    }

    public function test_page_one_is_written_without_the_parameter(): void
    {
        $category = $this->category();

        // Otherwise ?page=1 and the bare URL compete as two copies of one page.
        $meta = SeoMeta::forCategory($category, $this->request($category->url(), ['page' => 1]));

        $this->assertSame($category->url(), $meta->canonical);
    }

    public function test_a_filtered_request_is_noindex_follow(): void
    {
        $category = $this->category();

        foreach (['size' => '0-3m', 'sort' => 'price_asc', 'tag' => 'gifts', 'sale' => '1'] as $param => $value) {
            $meta = SeoMeta::forCategory($category, $this->request($category->url(), [$param => $value]));

            $this->assertSame('noindex,follow', $meta->robots, "?{$param} should be noindex,follow");
        }
    }

    public function test_a_clean_request_is_indexable(): void
    {
        $category = $this->category();

        $this->assertSame('index,follow', SeoMeta::forCategory($category, $this->request($category->url()))->robots);
    }

    public function test_an_empty_filter_parameter_is_not_a_filter(): void
    {
        $category = $this->category();

        // ?size= is how the "all sizes" chip submits; it shows the same page.
        $meta = SeoMeta::forCategory($category, $this->request($category->url(), ['size' => '']));

        $this->assertSame('index,follow', $meta->robots);
    }

    public function test_a_record_flagged_noindex_is_noindex_follow_even_unfiltered(): void
    {
        $category = $this->category();
        $category->update(['noindex' => true]);

        $this->assertSame('noindex,follow', SeoMeta::forCategory($category, $this->request($category->url()))->robots);
    }

    public function test_the_staging_switch_overrides_everything(): void
    {
        config(['kayaa.noindex' => true]);
        $category = $this->category();

        $this->assertSame('noindex,nofollow', SeoMeta::forCategory($category, $this->request($category->url()))->robots);
    }

    public function test_pagination_is_not_treated_as_a_filter(): void
    {
        $category = $this->category();

        $this->assertSame('index,follow', SeoMeta::forCategory($category, $this->request($category->url(), ['page' => 3]))->robots);
    }

    public function test_a_product_canonical_ignores_the_request_entirely(): void
    {
        $variant = $this->variant();
        $product = $variant->product;

        $meta = SeoMeta::forProduct($product, $this->request(
            route('products.show', $product),
            ['reviews' => 'all', 'utm_source' => 'instagram']
        ));

        $this->assertSame(route('products.show', $product), $meta->canonical);
        $this->assertSame('index,follow', $meta->robots);
    }

    public function test_og_type_is_product_for_a_product_and_website_elsewhere(): void
    {
        $variant = $this->variant();

        $this->assertSame('product', SeoMeta::forProduct($variant->product, $this->request('/'))->ogType);
        $this->assertSame('website', SeoMeta::forPage('home', $this->request('/'))->ogType);
    }

    public function test_og_title_and_description_mirror_the_page(): void
    {
        $meta = SeoMeta::forPage('home', $this->request('/'));

        $this->assertSame($meta->title, $meta->ogTitle);
        $this->assertSame($meta->description, $meta->ogDescription);
        // og:url is the canonical, so a link shared with tracking parameters
        // still resolves to one page.
        $this->assertSame($meta->canonical, $meta->ogUrl);
    }

    public function test_the_noindex_helper_is_noindex_nofollow(): void
    {
        $meta = SeoMeta::noindex($this->request('/cart'), 'Your Bag');

        $this->assertSame('noindex,nofollow', $meta->robots);
        $this->assertStringContainsString('Your Bag', $meta->title);
    }

    public function test_a_page_key_with_no_config_entry_still_produces_a_title(): void
    {
        $meta = SeoMeta::forPage('a-key-that-does-not-exist', $this->request('/'));

        $this->assertNotSame('', $meta->title);
        $this->assertNotSame('', $meta->description);
    }

    public function test_search_is_noindex_follow(): void
    {
        $this->assertSame('noindex,follow', SeoMeta::forPage('search', $this->request('/search'))->robots);
    }

    // ---------- helpers ----------

    private function category(): Category
    {
        $this->product();

        return Category::first();
    }

    private function request(string $url, array $query = []): Request
    {
        return Request::create($url, 'GET', $query);
    }
}
