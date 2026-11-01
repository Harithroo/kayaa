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
        $this->get('/admin')->assertRedirect();
    }

    public function test_customers_are_turned_away_from_the_admin(): void
    {
        $this->actingAs($this->customer())->get('/admin')->assertForbidden();
    }

    public function test_admins_see_the_dashboard_with_every_widget(): void
    {
        $this->actingAsAdmin();

        // Widgets load lazily, so assert they are mounted; their content is
        // covered in DashboardTest.
        $this->get('/admin')
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

        $this->get('/admin')
            ->assertSee('Sales')
            ->assertSee('Catalog')
            ->assertSee('Engagement')
            ->assertSee('Storefront');
    }

    public function test_every_admin_list_page_renders(): void
    {
        $this->actingAsAdmin();

        foreach (['orders', 'products', 'customers', 'reviews', 'contact-messages', 'faqs', 'banners',
            'categories', 'departments', 'colors', 'size-scales', 'contact-settings', 'store-settings'] as $path) {
            $this->get("/admin/{$path}")->assertOk();
        }
    }
}
