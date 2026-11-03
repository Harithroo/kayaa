<?php

namespace Tests\Unit\Seo;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

/**
 * The contract: metaTitle() and metaDescription() never return an empty string,
 * whatever the record is missing.
 */
class HasSeoMetaTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    public function test_a_product_with_both_fields_set_uses_them(): void
    {
        $product = $this->product([
            'meta_title' => 'A chosen title',
            'meta_description' => 'A chosen description.',
        ]);

        $this->assertSame('A chosen title', $product->metaTitle());
        $this->assertSame('A chosen description.', $product->metaDescription());
    }

    public function test_a_product_with_both_fields_blank_generates_them(): void
    {
        $variant = $this->variant([], null, [
            'name' => 'Organic Cotton Bodysuit',
            'description' => 'Soft, breathable organic cotton, cut for Sri Lankan weather.',
        ]);

        $title = $variant->product->metaTitle();

        $this->assertNotSame('', $title);
        $this->assertStringContainsString('Organic Cotton Bodysuit', $title);
        $this->assertStringEndsWith('| Kayaa', $title);
        $this->assertLessThanOrEqual(70, mb_strlen($title));

        $this->assertStringContainsString('Soft, breathable organic cotton', $variant->product->metaDescription());
    }

    public function test_the_generated_title_includes_the_size_range(): void
    {
        $variant = $this->variant();

        $this->assertStringContainsString(
            $variant->product->sizeRangeLabel(),
            $variant->product->metaTitle()
        );
    }

    public function test_a_product_with_no_description_falls_back_to_care_info(): void
    {
        $product = $this->product([
            'description' => null,
            'fabric_care' => 'Machine wash cold. Do not tumble dry.',
        ]);

        $this->assertStringContainsString('Machine wash cold', $product->metaDescription());
    }

    public function test_a_product_with_no_description_and_no_care_info_still_has_a_description(): void
    {
        $product = $this->product([
            'name' => 'Ribbed Romper',
            'description' => null,
            'fabric_care' => null,
        ]);

        $this->assertSame('Ribbed Romper. Available at Kayaa.', $product->metaDescription());
    }

    public function test_an_over_long_name_is_clamped_but_keeps_the_brand_suffix(): void
    {
        $product = $this->product([
            'name' => 'An Extremely Long Product Name That Would Push The Title Well Past The Limit',
            'description' => 'Something.',
        ]);

        $title = $product->metaTitle();

        // The suffix is what puts the brand in the result, so it is never
        // the part that gets cut.
        $this->assertLessThanOrEqual(70, mb_strlen($title));
        $this->assertStringEndsWith('| Kayaa', $title);
    }

    public function test_a_product_with_no_images_has_no_share_image_unless_one_is_configured(): void
    {
        config(['seo.default_og_image' => '']);

        $this->assertNull($this->product()->ogImageUrl());
    }

    public function test_the_store_default_share_image_is_used_when_the_product_has_none(): void
    {
        config(['seo.default_og_image' => 'seo/share.jpg']);

        $url = $this->product()->ogImageUrl();

        $this->assertNotNull($url);
        $this->assertStringStartsWith('http', $url, 'og:image must be absolute.');
        $this->assertStringContainsString('seo/share.jpg', $url);
    }

    public function test_a_products_first_photo_is_its_share_image(): void
    {
        $product = $this->product();
        $product->images()->create(['path' => 'products/first.jpg', 'position' => 0]);
        $product->images()->create(['path' => 'products/second.jpg', 'position' => 1]);

        $this->assertStringContainsString('products/first.jpg', $product->ogImageUrl());
    }

    public function test_a_chosen_share_image_wins_over_the_first_photo(): void
    {
        $product = $this->product();
        $product->images()->create(['path' => 'products/first.jpg', 'position' => 0]);
        $chosen = $product->images()->create(['path' => 'products/chosen.jpg', 'position' => 1]);

        $product->update(['og_image_id' => $chosen->id]);

        $this->assertStringContainsString('products/chosen.jpg', $product->fresh()->ogImageUrl());
    }

    public function test_deleting_the_chosen_share_image_falls_back_to_the_first_photo(): void
    {
        $product = $this->product();
        $product->images()->create(['path' => 'products/first.jpg', 'position' => 0]);
        $chosen = $product->images()->create(['path' => 'products/chosen.jpg', 'position' => 1]);
        $product->update(['og_image_id' => $chosen->id]);

        // The foreign key is nullOnDelete, so removing the photo in the admin
        // clears the choice rather than leaving a dangling id behind.
        $chosen->delete();

        $this->assertStringContainsString('products/first.jpg', $product->fresh()->ogImageUrl());
    }

    public function test_a_category_with_a_blank_intro_still_has_a_title_and_description(): void
    {
        $this->product();
        $category = Category::first();
        $category->update(['description' => null, 'meta_title' => null, 'meta_description' => null]);

        $title = $category->metaTitle();

        $this->assertNotSame('', $title);
        $this->assertStringContainsString($category->name, $title);
        $this->assertLessThanOrEqual(70, mb_strlen($title));
        $this->assertNotSame('', $category->metaDescription());
    }

    public function test_a_category_canonical_is_its_department_scoped_url(): void
    {
        $this->product();
        $category = Category::first();

        $this->assertSame($category->url(), $category->canonicalUrl());
        $this->assertStringNotContainsString('?', $category->canonicalUrl());
    }
}
