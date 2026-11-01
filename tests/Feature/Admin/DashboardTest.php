<?php

namespace Tests\Feature\Admin;

use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\LowStock;
use App\Filament\Widgets\SalesChart;
use App\Filament\Widgets\StoreStats;
use App\Filament\Widgets\TopSellers;
use App\Models\Setting;
use App\Support\SalesReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    public function test_sales_figures_skip_cancelled_orders(): void
    {
        $this->order(['total' => 100000]);
        $this->order(['total' => 50000, 'status' => 'confirmed']);
        $this->order(['total' => 999999, 'status' => 'cancelled']);

        $today = SalesReport::today();

        $this->assertSame(150000, $today['revenue']);
        $this->assertSame(2, $today['orders']);
    }

    public function test_daily_series_is_zero_filled_and_ordered(): void
    {
        $this->order(['total' => 70000]);
        $this->order(['total' => 30000, 'created_at' => now()->subDays(2)]);

        $series = SalesReport::daily(7);

        $this->assertCount(7, $series);
        $this->assertSame(array_keys($series), collect($series)->keys()->sort()->values()->all());
        $this->assertSame(70000, $series[now()->toDateString()]['revenue']);
        $this->assertSame(30000, $series[now()->subDays(2)->toDateString()]['revenue']);
        $this->assertSame(0, $series[now()->subDay()->toDateString()]['revenue']);
    }

    public function test_change_helper(): void
    {
        $this->assertNull(SalesReport::change(500, 0));
        $this->assertSame('+50%', SalesReport::change(150, 100));
        $this->assertSame('-25%', SalesReport::change(75, 100));
    }

    public function test_stats_widget_shows_todays_sales_and_work_waiting(): void
    {
        $this->actingAsAdmin();
        $this->order(['total' => 100000]);
        $this->order(['total' => 34000, 'status' => 'confirmed']);

        Livewire::test(StoreStats::class)
            ->assertSee('Sales today')
            ->assertSee('Rs. 1,340')
            ->assertSee('1 to confirm · 1 to ship');
    }

    public function test_chart_widget_offers_range_filters(): void
    {
        $this->actingAsAdmin();
        $this->order(['total' => 88000]);

        Livewire::test(SalesChart::class)->assertOk()->set('filter', '7')->assertOk();
    }

    public function test_latest_orders_lists_recent_orders(): void
    {
        $this->actingAsAdmin();
        $order = $this->order();

        Livewire::test(LatestOrders::class)->assertCanSeeTableRecords([$order])->assertSee($order->reference);
    }

    public function test_low_stock_lists_only_sellable_variants_at_or_below_the_threshold(): void
    {
        $this->actingAsAdmin();
        Setting::putMany(['low_stock_threshold' => '5']);
        config(['kayaa.low_stock_threshold' => 5]);

        $low = $this->variant(['stock' => 3]);
        $out = $this->variant(['stock' => 0]);
        $fine = $this->variant(['stock' => 40]);
        $inactive = $this->variant(['stock' => 1, 'is_active' => false]);
        $draftProduct = $this->variant(['stock' => 1], productOverrides: ['status' => 'draft']);

        Livewire::test(LowStock::class)
            ->assertCanSeeTableRecords([$low, $out])
            ->assertCanNotSeeTableRecords([$fine, $inactive, $draftProduct]);
    }

    public function test_top_sellers_rank_by_units_and_ignore_cancelled_orders(): void
    {
        $this->actingAsAdmin();

        $hit = $this->variant(productOverrides: ['name' => 'Bestseller Bib']);
        $flop = $this->variant(productOverrides: ['name' => 'Quiet Cap']);
        $cancelledOnly = $this->variant(productOverrides: ['name' => 'Ghost Socks']);

        $this->order([], [$hit, $flop]);
        $this->order([], [$hit]);
        $this->order(['status' => 'cancelled'], [$cancelledOnly]);

        Livewire::test(TopSellers::class)
            ->assertSeeInOrder(['Bestseller Bib', 'Quiet Cap'])
            ->assertDontSee('Ghost Socks');
    }
}
