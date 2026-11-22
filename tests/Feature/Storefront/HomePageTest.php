<?php

namespace Tests\Feature\Storefront;

use App\Models\Product;
use App\Models\Review;
use Database\Seeders\BannerSeeder;
use Database\Seeders\CatalogStructureSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\SizeScaleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

/**
 * The home page (the prototype's Home v2): what the server writes into it for the
 * scripts in resources/js/store/home to draw. Run against the demo catalogue.
 */
class HomePageTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([SizeScaleSeeder::class, CatalogStructureSeeder::class, DemoProductSeeder::class, BannerSeeder::class]);
    }

    public function test_the_sections_render_in_the_prototype_order(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $order = ['id="intro"', 'class="hero"', 'class="trust"', 'id="age-title"', 'id="rail-title"', 'class="marquee"', 'id="fabrics"', 'id="products"', 'aria-label="Promotions"', 'id="help-title"'];
        $positions = array_map(fn ($needle) => strpos($html, $needle), $order);

        $this->assertNotContains(false, $positions, 'Every Home section is on the page.');
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'The sections follow the prototype order.');
        $this->assertSame(1, substr_count($html, '<h1'));
    }

    public function test_the_motion_script_is_on_the_home_page_only(): void
    {
        $this->get('/')->assertOk()->assertSee('window.KAYAA_RM = rm;', false);
        $this->get(route('shop.index'))->assertOk()->assertDontSee('window.KAYAA_RM', false);
    }

    public function test_shop_by_age_carries_the_size_chart(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(9, substr_count($html, 'class="age-chip"'));
        $this->assertStringContainsString('aria-pressed="true" data-i="1">0–3m</button>', $html);

        preg_match('#<script type="application/json" id="age-data">(.*?)</script>#s', $html, $m);
        $ages = json_decode($m[1], true);

        $this->assertCount(9, $ages);
        $this->assertSame(['label' => 'Newborn', 'name' => 'Newborn', 'height' => 'Up to 50 cm', 'weight' => 'Up to 3.5 kg', 'h' => [45, 50], 'w' => [2, 3.5], 'g' => 'newborn'],
            array_intersect_key($ages[0], array_flip(['label', 'name', 'height', 'weight', 'h', 'w', 'g'])));
        $this->assertSame('0–3 months', $ages[1]['name']);
        $this->assertSame([50, 58], $ages[1]['h']);
        $this->assertSame([9, 10.5], $ages[4]['w']);
        $this->assertSame('2 years', $ages[7]['name']);
        $this->assertSame(route('shop.index', ['size' => '3Y']), $ages[8]['href']);

        // The first paint shows 0–3m, as the prototype's generated text does.
        $this->assertStringContainsString('Height <b>50–58 cm</b> · Weight <b>3.5–5.5 kg</b>', $html);
        $this->assertStringContainsString('<span>Newborn, 0–3m … 3Y</span>', $html);
    }

    public function test_the_rail_has_one_link_per_category_with_products(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Napkins has no demo products, so six hangers.
        $this->assertSame(6, substr_count($html, 'class="hanger"'));
        $this->assertStringContainsString('style="--rail-n: 6"', $html);
        $this->assertStringNotContainsString('data-name="Napkins"', $html);
        $this->assertStringContainsString('data-name="Bodysuits" data-count="2" data-range="NB–18m" data-type="bodysuit" data-c="peri"', $html);
        $this->assertStringContainsString('aria-label="Sleepwear, 2 styles, sizes NB–24m"', $html);
        $this->assertStringContainsString('data-name="Sets" data-count="1" data-range="6m–24m" data-type="set" data-c="mist" data-c2="lilac" aria-label="Sets, 1 style, sizes 6m–24m"', $html);
        $this->assertStringContainsString('Sets <span>1 style · 6m–24m</span>', $html);
        $this->assertStringContainsString('Six categories, from the first bodysuit', $html);
    }

    public function test_the_fabric_cards_link_the_products_made_of_them(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(4, substr_count($html, 'class="fab-tab"'));
        foreach (['Muslin Sleep Bag 0.5 TOG', 'Bamboo Sleepsuit', 'Ribbed Cotton Bodysuit', 'Waffle Knit Cardigan'] as $name) {
            $product = Product::where('name', $name)->firstOrFail();
            $this->assertStringContainsString('<a href="'.route('products.show', $product).'">'.e($name).'</a>', $html);
        }

        // No product left for a fabric: the card searches for it instead.
        Product::where('slug', 'waffle-knit-cardigan')->update(['status' => 'draft']);
        $this->get('/')->assertSee('<a href="'.e(route('search', ['q' => 'Waffle knit'])).'">Search for waffle knit</a>', false);
    }

    public function test_new_this_week_and_bestsellers_are_two_tabs_over_the_shared_card(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="tab-new"', $html);
        $this->assertStringContainsString('id="tab-best"', $html);
        $this->assertSame(8, substr_count($html, '<article class="product-card'));

        $best = substr($html, strpos($html, 'id="panel-best"'));
        $this->assertStringContainsString('Ribbed Cotton Bodysuit', $best);

        // Nothing featured: the Bestsellers tab goes, and with it the tab list.
        Product::query()->update(['is_featured' => false]);
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('id="tab-best"', $html);
        $this->assertStringNotContainsString('role="tablist" aria-label="Product lists"', $html);
        $this->assertStringContainsString('<h2 id="prod-title">New this week</h2>', $html);
    }

    public function test_the_reviews_feed_shows_verified_four_and_five_star_reviews_only(): void
    {
        $this->get('/')->assertOk()->assertDontSee('id="rev-title"', false);

        $product = Product::where('slug', 'ribbed-cotton-bodysuit')->first();
        $variant = $product->variants()->whereHas('sizeOption', fn ($q) => $q->where('label', '3–6m'))->first();
        $customer = $this->customer(['name' => 'Nadeesha Silva']);
        $this->order(['user_id' => $customer->id, 'status' => 'delivered'], [$variant]);

        Review::create(['product_id' => $product->id, 'user_id' => $customer->id, 'name' => 'Nadeesha Silva', 'rating' => 4, 'body' => 'Fits exactly as the guide said.', 'is_verified_purchase' => true, 'is_approved' => true]);
        Review::create(['product_id' => $product->id, 'name' => 'Guest', 'rating' => 5, 'body' => 'Not a verified buyer.', 'is_verified_purchase' => false, 'is_approved' => true]);
        Review::create(['product_id' => $product->id, 'user_id' => $customer->id, 'name' => 'Nadeesha', 'rating' => 3, 'body' => 'Three stars stay off Home.', 'is_verified_purchase' => true, 'is_approved' => true]);
        Review::create(['product_id' => $product->id, 'user_id' => $customer->id, 'name' => 'Nadeesha', 'rating' => 5, 'body' => 'Waiting for approval.', 'is_verified_purchase' => true, 'is_approved' => false]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<figure class="review"'));
        $this->assertStringContainsString('Fits exactly as the guide said.', $html);
        $this->assertStringContainsString('aria-label="4 out of 5 stars"', $html);
        $this->assertSame(1, substr_count($html, 'class="is-off"'));
        $this->assertStringContainsString('<b>Nadeesha</b>Ribbed Cotton Bodysuit · <span class="who__size">Bought 3–6m</span>', $html);
        $this->assertStringContainsString(route('products.show', $product).'#reviews', $html);
        $this->assertStringNotContainsString('Not a verified buyer.', $html);
        $this->assertStringNotContainsString('Three stars stay off Home.', $html);
        $this->assertStringNotContainsString('Waiting for approval.', $html);
    }

    public function test_the_size_help_falls_back_to_the_contact_page_without_a_whatsapp_number(): void
    {
        config(['kayaa.whatsapp' => '']);
        $this->get('/')->assertOk()->assertSee('<a class="btn btn--white btn--lg" href="'.route('pages.contact').'">Contact us</a>', false);

        config(['kayaa.whatsapp' => '0771234567']);
        $this->get('/')->assertOk()->assertSee('Chat on WhatsApp</a>', false)->assertSee('age and weight on WhatsApp', false);
    }
}
