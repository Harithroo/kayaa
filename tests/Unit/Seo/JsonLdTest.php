<?php

namespace Tests\Unit\Seo;

use App\Models\Faq;
use App\Models\Review;
use App\Services\Seo\JsonLd;
use App\Support\Seo\Breadcrumbs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

class JsonLdTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    public function test_every_builder_survives_a_json_round_trip(): void
    {
        $variant = $this->variant();

        $blocks = [
            JsonLd::product($variant->product),
            JsonLd::breadcrumbs(Breadcrumbs::forProduct($variant->product)),
            JsonLd::organization(),
            JsonLd::webSite(),
        ];

        foreach ($blocks as $block) {
            $encoded = json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $this->assertNotFalse($encoded, 'Block would not encode: '.json_last_error_msg());
            $this->assertSame($block, json_decode($encoded, true));
            $this->assertSame('https://schema.org', $block['@context']);
        }
    }

    public function test_one_price_across_variants_produces_a_single_offer(): void
    {
        $variant = $this->variant(['price' => 185000]);
        $this->variant(['price' => 185000], $variant->product);

        $offers = JsonLd::product($variant->product->fresh())['offers'];

        $this->assertSame('Offer', $offers['@type']);
        $this->assertSame('1850.00', $offers['price']);
    }

    public function test_mixed_prices_produce_an_aggregate_offer_with_the_real_range(): void
    {
        $cheap = $this->variant(['price' => 120000]);
        $this->variant(['price' => 450000], $cheap->product);

        $offers = JsonLd::product($cheap->product->fresh())['offers'];

        $this->assertSame('AggregateOffer', $offers['@type']);
        $this->assertSame('1200.00', $offers['lowPrice']);
        $this->assertSame('4500.00', $offers['highPrice']);
        $this->assertSame(2, $offers['offerCount']);
    }

    public function test_zero_stock_everywhere_is_out_of_stock(): void
    {
        $variant = $this->variant(['stock' => 0]);
        $this->variant(['stock' => 0], $variant->product);

        $this->assertSame(
            'https://schema.org/OutOfStock',
            JsonLd::product($variant->product->fresh())['offers']['availability']
        );
    }

    public function test_stock_in_any_variant_is_in_stock(): void
    {
        $variant = $this->variant(['stock' => 0]);
        $this->variant(['stock' => 3], $variant->product);

        $this->assertSame(
            'https://schema.org/InStock',
            JsonLd::product($variant->product->fresh())['offers']['availability']
        );
    }

    public function test_a_product_with_no_variants_has_no_offers(): void
    {
        // An offer with no price is worse than no offer at all.
        $this->assertArrayNotHasKey('offers', JsonLd::product($this->product()));
    }

    public function test_no_reviews_means_no_aggregate_rating(): void
    {
        $variant = $this->variant();

        $block = JsonLd::product($variant->product, collect());

        $this->assertArrayNotHasKey('aggregateRating', $block);
        $this->assertArrayNotHasKey('review', $block);
    }

    public function test_null_reviews_means_no_aggregate_rating(): void
    {
        $this->assertArrayNotHasKey('aggregateRating', JsonLd::product($this->variant()->product));
    }

    public function test_rendered_reviews_produce_a_rating_and_the_review_entries(): void
    {
        $variant = $this->variant();
        $product = $variant->product;

        foreach ([5, 4] as $rating) {
            Review::create([
                'product_id' => $product->id,
                'name' => 'Nimali',
                'rating' => $rating,
                'body' => 'Washed well.',
                'is_approved' => true,
                'approved_at' => now(),
            ]);
        }

        $block = JsonLd::product($product->fresh(), $product->reviews()->approved()->get());

        $this->assertSame('4.5', $block['aggregateRating']['ratingValue']);
        $this->assertSame(2, $block['aggregateRating']['reviewCount']);
        $this->assertCount(2, $block['review']);
        $this->assertSame('Review', $block['review'][0]['@type']);
    }

    public function test_prices_are_converted_from_cents(): void
    {
        $variant = $this->variant(['price' => 99]);

        $this->assertSame('0.99', JsonLd::product($variant->product)['offers']['price']);
    }

    public function test_breadcrumbs_number_from_one_and_leave_the_last_item_unlinked(): void
    {
        $variant = $this->variant();

        $items = JsonLd::breadcrumbs(Breadcrumbs::forProduct($variant->product))['itemListElement'];

        $this->assertSame(1, $items[0]['position']);
        $this->assertSame('Home', $items[0]['name']);
        $this->assertArrayHasKey('item', $items[0]);

        $last = end($items);
        $this->assertSame($variant->product->name, $last['name']);
        $this->assertArrayNotHasKey('item', $last);
    }

    public function test_a_trail_of_one_produces_no_breadcrumb_list(): void
    {
        $this->assertNull(JsonLd::breadcrumbs([['name' => 'Home', 'url' => null]]));
    }

    public function test_an_empty_faq_collection_produces_nothing(): void
    {
        $this->assertNull(JsonLd::faqPage(collect()));
    }

    public function test_faqs_become_questions_with_answers(): void
    {
        $faq = Faq::create([
            'placement' => 'contact',
            'question' => 'Do you deliver island-wide?',
            'answer' => '<p>Yes, by courier.</p>',
            'is_active' => true,
        ]);

        $block = JsonLd::faqPage(collect([$faq]));

        $this->assertSame('FAQPage', $block['@type']);
        $this->assertSame('Do you deliver island-wide?', $block['mainEntity'][0]['name']);
        // Answers are flattened to plain text, tags and all.
        $this->assertSame('Yes, by courier.', $block['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function test_the_website_block_carries_a_search_action(): void
    {
        $block = JsonLd::webSite();

        $this->assertSame('SearchAction', $block['potentialAction']['@type']);
        $this->assertStringContainsString('{search_term_string}', $block['potentialAction']['target']['urlTemplate']);
    }

    public function test_organization_lists_configured_profiles_as_same_as(): void
    {
        config([
            'seo.org.instagram' => 'https://www.instagram.com/kayaa',
            'seo.org.facebook' => 'https://www.facebook.com/kayaa',
        ]);

        $this->assertSame(
            ['https://www.instagram.com/kayaa', 'https://www.facebook.com/kayaa'],
            JsonLd::organization()['sameAs']
        );
    }

    public function test_organization_omits_same_as_when_no_profiles_are_set(): void
    {
        config(['seo.org.instagram' => '', 'seo.org.facebook' => '']);

        $this->assertArrayNotHasKey('sameAs', JsonLd::organization());
    }
}
