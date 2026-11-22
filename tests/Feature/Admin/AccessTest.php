<?php

namespace Tests\Feature\Admin;

use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\LowStock;
use App\Filament\Widgets\SalesChart;
use App\Filament\Widgets\StoreStats;
use App\Filament\Widgets\TopSellers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    public function test_guests_cannot_open_the_admin(): void
    {
        $this->get($this->adminPath())->assertRedirect();
    }

    public function test_customers_are_turned_away_from_the_admin(): void
    {
        $this->actingAs($this->customer())->get($this->adminPath('overview'))->assertForbidden();
    }

    public function test_admins_see_the_dashboard_with_every_widget(): void
    {
        $this->actingAsAdmin();

        // Widgets load lazily, so assert they are mounted; their content is
        // covered in DashboardTest.
        $this->get($this->adminPath('overview'))
            ->assertOk()
            ->assertSeeLivewire(StoreStats::class)
            ->assertSeeLivewire(SalesChart::class)
            ->assertSeeLivewire(LatestOrders::class)
            ->assertSeeLivewire(LowStock::class)
            ->assertSeeLivewire(TopSellers::class);
    }

    public function test_navigation_is_grouped(): void
    {
        $this->actingAsAdmin();

        $this->get($this->adminPath('overview'))
            ->assertSee('Sales')
            ->assertSee('Catalog')
            ->assertSee('Engagement')
            ->assertSee('Storefront');
    }

    public function test_every_admin_list_page_renders(): void
    {
        $this->actingAsAdmin();

        foreach (['orders', 'products', 'customers', 'reviews', 'contact-messages', 'faqs', 'banners',
            'categories', 'departments', 'colors', 'size-scales', 'contact-settings', 'store-settings',
            'stock-movements', 'admin-users'] as $path) {
            $this->get($this->adminPath($path))->assertOk();
        }
    }

    // ---------- where the panel lives ----------

    public function test_the_panel_is_not_at_admin(): void
    {
        $this->assertSame('kdash', config('kayaa.admin_path'));

        $this->get('/admin')->assertNotFound();
        $this->get('/admin/login')->assertNotFound();
        $this->get('/kdash/login')->assertOk();
    }

    public function test_the_panel_root_opens_the_overview(): void
    {
        $this->actingAsAdmin();

        $this->get($this->adminPath())->assertRedirect($this->adminPath('overview'));
        $this->get($this->adminPath('overview'))->assertOk();
    }

    public function test_every_admin_response_asks_not_to_be_indexed(): void
    {
        $this->get($this->adminPath('login'))->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->actingAsAdmin();
        $this->get($this->adminPath('orders'))->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_robots_and_the_sitemap_never_name_the_panel(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertStringNotContainsString(config('kayaa.admin_path'), $robots);
        $this->assertStringContainsString('Disallow: /admin', $robots);

        $this->product(['name' => 'Ribbed Bodysuit', 'slug' => 'ribbed-bodysuit']);
        $this->artisan('kayaa:sitemap')->assertSuccessful();
        $sitemap = file_get_contents(public_path('sitemap.xml'));
        @unlink(public_path('sitemap.xml'));

        $this->assertStringContainsString('ribbed-bodysuit', $sitemap);
        $this->assertStringNotContainsString('/'.config('kayaa.admin_path'), $sitemap);
        $this->assertStringNotContainsString('/admin', $sitemap);
    }
}
