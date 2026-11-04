<?php

namespace Tests\Feature\Storefront;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\BuildsShopData;
use Tests\Concerns\PlacesStorefrontOrders;
use Tests\TestCase;

class CustomerCancelTest extends TestCase
{
    use BuildsShopData, PlacesStorefrontOrders, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_a_pending_order_can_be_cancelled_and_the_stock_comes_back(): void
    {
        $variant = $this->variant(['stock' => 10]);
        $order = $this->placed($variant, 3);
        $this->assertSame(7, $variant->fresh()->stock);

        $this->post($order->cancelUrl())
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertStringContainsString('Cancelled by the customer.', $order->note);
        $this->assertSame(10, $variant->fresh()->stock);
    }

    public function test_cancelling_twice_restocks_once(): void
    {
        $variant = $this->variant(['stock' => 10]);
        $order = $this->placed($variant, 3);

        $this->post($order->cancelUrl());
        $this->post($order->fresh()->cancelUrl());

        $this->assertSame(10, $variant->fresh()->stock);
    }

    public function test_a_confirmed_order_can_no_longer_be_cancelled_by_the_customer(): void
    {
        $variant = $this->variant(['stock' => 10]);
        $order = $this->placed($variant, 3);
        $order->update(['status' => 'confirmed']);

        $this->post($order->cancelUrl())
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame(7, $variant->fresh()->stock, 'nothing was put back');
    }

    public function test_a_paid_order_cannot_be_cancelled_by_the_customer_even_while_pending(): void
    {
        $variant = $this->variant(['stock' => 10]);
        $order = $this->placed($variant, 1);
        $order->update(['payment_status' => 'paid']);

        $this->post($order->cancelUrl())->assertSessionHas('error');

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_a_shipped_order_cannot_be_cancelled_by_the_customer(): void
    {
        $order = $this->placed($this->variant(), 1);
        $order->update(['status' => 'shipped']);

        $this->post($order->cancelUrl())->assertSessionHas('error');

        $this->assertSame('shipped', $order->fresh()->status);
    }

    public function test_cancelling_needs_the_signed_link_or_the_owner(): void
    {
        $variant = $this->variant(['stock' => 10]);
        $order = $this->placed($variant, 1);

        // Reference alone: no signature, not signed in.
        $this->post(URL::to("/orders/{$order->reference}/cancel"))->assertNotFound();

        // Signed in as somebody else.
        $this->actingAs($this->customer())->post(URL::to("/orders/{$order->reference}/cancel"))->assertNotFound();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_the_signed_in_owner_can_cancel_without_the_signed_link(): void
    {
        $owner = $this->customer();
        $this->actingAs($owner);
        $order = $this->placed($this->variant(), 1);
        $this->assertSame($owner->id, $order->user_id);

        $this->post(URL::to("/orders/{$order->reference}/cancel"))->assertSessionHas('success');

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    /** An order placed through the real checkout, so the stock has really been taken. */
    private function placed(ProductVariant $variant, int $qty): Order
    {
        $this->checkout([$variant->id => $qty])->assertSessionHasNoErrors();

        return Order::latest('id')->firstOrFail();
    }
}
