<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\BannerResource\Pages\ListBanners;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

/**
 * Columns whose text comes from formatStateUsing().
 *
 * Filament hands a closure its arguments by parameter *name*, so a closure
 * written as fn ($s) never receives the state — it gets null, silently. The
 * column still renders, so nothing fails: the payment method quietly read
 * "Onepay" on every order, cash on delivery included, and the banner placement
 * badge came out empty. Rendering tests cannot catch that, only assertions on
 * the formatted text can.
 */
class TableColumnFormattingTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    public function test_a_cash_on_delivery_order_is_not_labelled_as_onepay(): void
    {
        $this->actingAsAdmin();
        $cod = $this->order(['payment_method' => 'cod'], [$this->variant()]);
        $online = $this->order(['payment_method' => 'onepay'], [$this->variant()]);

        Livewire::test(ListOrders::class)
            ->assertTableColumnFormattedStateSet('payment_method', 'COD', $cod)
            ->assertTableColumnFormattedStateSet('payment_method', 'Onepay', $online);
    }

    public function test_the_banner_placement_badge_shows_its_label(): void
    {
        $this->actingAsAdmin();

        $placement = array_key_first(Banner::PLACEMENTS);
        $banner = Banner::query()->create([
            'placement' => $placement,
            'title' => 'Free delivery',
            'position' => 1,
        ]);

        Livewire::test(ListBanners::class)
            ->assertTableColumnFormattedStateSet('placement', Banner::PLACEMENTS[$placement], $banner);
    }
}
