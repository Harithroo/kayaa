<?php

namespace Tests\Feature\Storefront;

use App\Models\Order;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsShopData;
use Tests\Concerns\PlacesStorefrontOrders;
use Tests\TestCase;

/**
 * Two shoppers, one unit left. Requests here run one after the other, which is
 * all a SQLite test can do; the row lock that protects true simultaneity is
 * exercised by the stale-cart test in CheckoutTest.
 */
class OversellingTest extends TestCase
{
    use BuildsShopData, PlacesStorefrontOrders, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_the_second_buyer_of_the_last_unit_is_refused(): void
    {
        $variant = $this->variant(['stock' => 1]);

        // Both shoppers have the last unit in their bags.
        $first = $this->checkout([$variant->id => 1], ['first_name' => 'First', 'email' => 'first@example.test']);
        $second = $this->checkout([$variant->id => 1], ['first_name' => 'Second', 'email' => 'second@example.test']);

        $first->assertRedirect();
        $this->assertSame(['First'], Order::pluck('first_name')->all(), 'only the first order exists');
        $this->assertNotSame(200, $second->status());
        $this->assertSame(0, $variant->fresh()->stock, 'stock is exactly used up, never negative');
    }

    public function test_the_refused_buyer_leaves_no_trace_in_the_stock_history(): void
    {
        $variant = $this->variant(['stock' => 1]);

        $this->checkout([$variant->id => 1]);
        $this->checkout([$variant->id => 1]);

        $this->assertSame(1, StockMovement::where('reason', 'order_placed')->count());
    }

    public function test_buyers_can_share_the_stock_until_it_runs_out(): void
    {
        $variant = $this->variant(['stock' => 5]);

        $this->checkout([$variant->id => 2]);
        $this->checkout([$variant->id => 2]);
        $this->checkout([$variant->id => 2]);   // wants 2, only 1 left: gets the 1

        $this->assertSame(5, Order::with('items')->get()->sum(fn ($o) => $o->items->sum('qty')));
        $this->assertSame(0, $variant->fresh()->stock);
    }
}
