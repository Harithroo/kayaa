<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    protected function tearDown(): void
    {
        @unlink(public_path('sitemap.xml'));

        foreach (glob(public_path('sitemap-*.xml')) ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_it_writes_valid_xml_listing_the_live_catalogue(): void
    {
        $variant = $this->variant();

        $this->artisan('kayaa:sitemap')->assertSuccessful();

        $xml = file_get_contents(public_path('sitemap.xml'));

        // Parsed rather than string-matched: a sitemap Google cannot read is
        // worse than no sitemap.
        $parsed = simplexml_load_string($xml);
        $this->assertNotFalse($parsed, 'Sitemap is not valid XML.');

        $this->assertStringContainsString(route('home'), $xml);
        $this->assertStringContainsString(route('products.show', $variant->product->slug), $xml);
        $this->assertStringContainsString(Category::first()->url(), $xml);
    }

    public function test_a_noindex_product_is_left_out(): void
    {
        $listed = $this->variant();
        $hidden = $this->variant([], null, ['noindex' => true]);

        $this->artisan('kayaa:sitemap')->assertSuccessful();
        $xml = file_get_contents(public_path('sitemap.xml'));

        $this->assertStringContainsString(route('products.show', $listed->product->slug), $xml);
        $this->assertStringNotContainsString(route('products.show', $hidden->product->slug), $xml);
    }

    public function test_a_draft_product_is_left_out(): void
    {
        $draft = $this->variant([], null, ['status' => 'draft']);
        $this->variant();

        $this->artisan('kayaa:sitemap')->assertSuccessful();

        $this->assertStringNotContainsString(
            route('products.show', $draft->product->slug),
            file_get_contents(public_path('sitemap.xml'))
        );
    }

    public function test_a_category_with_no_active_products_is_left_out(): void
    {
        $this->variant();

        $empty = Category::create([
            'department_id' => Category::first()->department_id,
            'name' => 'Empty Shelf',
            'slug' => 'empty-shelf',
            'is_active' => true,
        ]);

        $this->artisan('kayaa:sitemap')->assertSuccessful();

        // An empty category is a dead end for a crawler and a bad result for a
        // shopper, so it is not advertised.
        $this->assertStringNotContainsString(
            $empty->url(),
            file_get_contents(public_path('sitemap.xml'))
        );
    }

    public function test_each_entry_carries_a_lastmod_for_catalogue_pages(): void
    {
        $variant = $this->variant();

        $this->artisan('kayaa:sitemap')->assertSuccessful();

        $parsed = simplexml_load_string(file_get_contents(public_path('sitemap.xml')));

        $entry = collect($parsed->url)->first(
            fn ($url) => (string) $url->loc === route('products.show', $variant->product->slug)
        );

        $this->assertNotNull($entry);
        $this->assertNotSame('', (string) $entry->lastmod);
    }
}
