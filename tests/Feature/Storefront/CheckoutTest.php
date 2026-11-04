<?php

namespace Tests\Feature\Storefront;

use App\Mail\OrderPlaced;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Providers\AppServiceProvider;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\OutOfStockException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use ReflectionMethod;
use Tests\Concerns\BuildsShopData;
use Tests\Concerns\PlacesStorefrontOrders;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use BuildsShopData, PlacesStorefrontOrders, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Mail::fake();

        // The shipped defaults, whatever the developer's .env says.
        config(['kayaa.shipping_fee' => 45000, 'kayaa.free_shipping_over' => 750000]);
    }

    // ---------- validation ----------

    public function test_the_checkout_page_needs_something_in_the_cart(): void
    {
        $this->get(route('checkout.show'))->assertRedirect(route('cart.index'));
    }

    public function test_the_checkout_page_renders_with_a_cart(): void
    {
        $variant = $this->variant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id]);

        $this->get(route('checkout.show'))->assertOk();
    }

    public function test_required_fields_are_validated_and_nothing_is_ordered(): void
    {
        $variant = $this->variant(['stock' => 5]);

        $this->checkout([$variant->id => 1], [
            'first_name' => '', 'last_name' => '', 'phone' => '', 'address' => '', 'city' => '', 'district' => '',
        ])->assertSessionHasErrors(['first_name', 'last_name', 'phone', 'address', 'city', 'district']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $variant->fresh()->stock);
    }

    public function test_the_phone_must_be_a_sri_lankan_mobile(): void
    {
        $variant = $this->variant();

        foreach (['0112345678', '12345', 'abcdefghij', '+94771234567'] as $bad) {
            $this->checkout([$variant->id => 1], ['phone' => $bad])->assertSessionHasErrors('phone');
        }

        foreach (['0771234567', '771234567', '077-123-4567', '077 123 4567'] as $good) {
            $this->checkout([$variant->id => 1], ['phone' => $good])->assertSessionHasNoErrors();
        }
    }

    public function test_district_email_and_payment_method_must_be_valid(): void
    {
        $variant = $this->variant();

        $this->checkout([$variant->id => 1], ['district' => 'Atlantis'])->assertSessionHasErrors('district');
        $this->checkout([$variant->id => 1], ['email' => 'not-an-email'])->assertSessionHasErrors('email');
        $this->checkout([$variant->id => 1], ['payment_method' => 'bitcoin'])->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_email_is_optional(): void
    {
        $variant = $this->variant();

        $this->checkout([$variant->id => 1], ['email' => ''])->assertSessionHasNoErrors();

        $this->assertNull(Order::firstOrFail()->email);
        Mail::assertNothingSent();
    }

    public function test_checking_out_with_an_empty_cart_places_nothing(): void
    {
        $this->checkout([])->assertStatus(400);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_cart_holding_only_a_deactivated_variant_places_nothing(): void
    {
        $variant = $this->variant(['stock' => 5, 'is_active' => false]);

        $this->checkout([$variant->id => 1])->assertStatus(400);

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $variant->fresh()->stock);
    }

    // ---------- a COD order ----------

    public function test_a_cod_order_is_created_with_server_side_totals(): void
    {
        $variant = $this->variant(['price' => 250000, 'stock' => 10]);

        $response = $this->checkout([$variant->id => 2], ['note' => 'Ring the bell']);

        $order = Order::with('items')->firstOrFail();

        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(500000, $order->subtotal);
        $this->assertSame(45000, $order->shipping);
        $this->assertSame(545000, $order->total);
        $this->assertSame('0771234567', $order->phone, 'digits only');
        $this->assertSame('Colombo', $order->district);
        $this->assertSame('Ring the bell', $order->note);

        $this->assertCount(1, $order->items);
        $item = $order->items[0];
        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertSame($variant->product->name, $item->product_name);
        $this->assertSame($variant->sku, $item->sku);
        $this->assertSame(250000, $item->unit_price);
        $this->assertSame(2, $item->qty);
        $this->assertSame(500000, $item->line_total);

        // Lands on the signed confirmation page, which renders.
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith(url("/orders/{$order->reference}/thank-you"), $location);
        $this->assertStringContainsString('signature=', $location);
        $this->get($location)->assertOk()->assertSee($order->reference);

        $this->assertSame([], session('cart.items'), 'the cart is emptied');
    }

    public function test_the_browser_cannot_set_prices_or_totals(): void
    {
        $variant = $this->variant(['price' => 250000]);

        $this->checkout([$variant->id => 1], ['total' => 1, 'subtotal' => 1, 'shipping' => 0, 'status' => 'delivered', 'payment_status' => 'paid']);

        $order = Order::firstOrFail();
        $this->assertSame(295000, $order->total);
        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_an_order_has_several_lines_and_sums_them(): void
    {
        $a = $this->variant(['price' => 100000]);
        $b = $this->variant(['price' => 150000]);

        $this->checkout([$a->id => 2, $b->id => 1]);

        $order = Order::with('items')->firstOrFail();
        $this->assertCount(2, $order->items);
        $this->assertSame(350000, $order->subtotal);
    }

    // ---------- shipping ----------

    public function test_shipping_is_charged_below_the_free_shipping_threshold_and_waived_at_it(): void
    {
        $below = $this->variant(['price' => 749999]);
        $atThreshold = $this->variant(['price' => 750000]);

        $this->checkout([$below->id => 1]);
        $this->checkout([$atThreshold->id => 1]);

        $orders = Order::orderBy('id')->get();
        $this->assertSame(45000, $orders[0]->shipping);
        $this->assertSame(749999 + 45000, $orders[0]->total);
        $this->assertSame(0, $orders[1]->shipping);
        $this->assertSame(750000, $orders[1]->total);
    }

    public function test_the_shipping_settings_saved_in_the_admin_apply_to_new_orders(): void
    {
        Setting::putMany(['shipping_fee' => '600', 'free_shipping_over' => '2000']);
        (new ReflectionMethod(AppServiceProvider::class, 'bootStoreSettings'))->invoke(new AppServiceProvider($this->app));

        $small = $this->variant(['price' => 100000]);   // Rs 1,000: under the Rs 2,000 threshold
        $big = $this->variant(['price' => 200000]);     // Rs 2,000: free

        $this->checkout([$small->id => 1]);
        $this->checkout([$big->id => 1]);

        $orders = Order::orderBy('id')->get();
        $this->assertSame(60000, $orders[0]->shipping);
        $this->assertSame(160000, $orders[0]->total);
        $this->assertSame(0, $orders[1]->shipping);
    }

    public function test_free_delivery_on_everything_when_the_threshold_is_zero(): void
    {
        Setting::putMany(['free_shipping_over' => '0']);
        (new ReflectionMethod(AppServiceProvider::class, 'bootStoreSettings'))->invoke(new AppServiceProvider($this->app));
        $variant = $this->variant(['price' => 100]);

        $this->checkout([$variant->id => 1]);

        $this->assertSame(0, Order::firstOrFail()->shipping);
    }

    // ---------- stock ----------

    public function test_placing_an_order_takes_the_stock(): void
    {
        $variant = $this->variant(['stock' => 10]);

        $this->checkout([$variant->id => 3]);

        $this->assertSame(7, $variant->fresh()->stock);
    }

    public function test_asking_for_more_than_is_in_stock_never_takes_more_than_is_there(): void
    {
        $variant = $this->variant(['stock' => 2]);

        // The cart quietly caps a line at live stock, so this becomes an order for 2.
        $this->checkout([$variant->id => 5])->assertSessionHasNoErrors();

        $order = Order::with('items')->firstOrFail();
        $this->assertSame(2, $order->items[0]->qty);
        $this->assertSame(0, $variant->fresh()->stock);
    }

    public function test_a_sold_out_variant_cannot_be_ordered(): void
    {
        $variant = $this->variant(['stock' => 0]);

        $this->checkout([$variant->id => 1])->assertStatus(400);

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, $variant->fresh()->stock, 'stock never goes negative');
    }

    public function test_stock_that_runs_out_between_the_cart_and_the_order_is_refused_whole(): void
    {
        $fine = $this->variant(['stock' => 10]);
        $last = $this->variant(['stock' => 1]);

        // The shopper's cart was read while the last unit was still there...
        $cart = app(CartService::class);
        $cart->add($fine, 2);
        $cart->add($last, 1);
        $cart->lines();

        // ...and someone else bought it before this order took its lock.
        ProductVariant::whereKey($last->id)->update(['stock' => 0]);

        try {
            app(OrderService::class)->placeOrder(Arr::except($this->checkoutData(), ['payment_method']), 'cod');
            $this->fail('Expected the order to be refused.');
        } catch (OutOfStockException $e) {
            $this->assertStringContainsString('sold out', $e->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $fine->fresh()->stock, 'the first line was rolled back with the rest');
        $this->assertSame(0, $last->fresh()->stock);
    }

    public function test_the_checkout_controller_sends_an_out_of_stock_shopper_back_to_the_cart(): void
    {
        $variant = $this->variant(['stock' => 3]);

        $this->mock(OrderService::class)
            ->shouldReceive('placeOrder')
            ->andThrow(new OutOfStockException($variant->load('product'), 3));

        $this->checkout([$variant->id => 3])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');
    }

    // ---------- guest and signed-in ----------

    public function test_a_guest_order_has_no_user(): void
    {
        $variant = $this->variant();

        $this->checkout([$variant->id => 1]);

        $this->assertNull(Order::firstOrFail()->user_id);
    }

    public function test_a_signed_in_customer_order_is_linked_to_them(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer);
        $variant = $this->variant();

        $this->checkout([$variant->id => 1])->assertSessionHasNoErrors();

        $this->assertSame($customer->id, Order::firstOrFail()->user_id);
        $this->assertSame(0, \DB::table('cart_items')->where('user_id', $customer->id)->count(), 'their saved cart is emptied');
    }

    public function test_the_user_id_cannot_be_forged_through_the_form(): void
    {
        $someoneElse = $this->customer();
        $variant = $this->variant();

        $this->checkout([$variant->id => 1], ['user_id' => $someoneElse->id]);

        $this->assertNull(Order::firstOrFail()->user_id);
    }

    // ---------- confirmation mail ----------

    public function test_the_customer_is_emailed_a_confirmation_once(): void
    {
        $variant = $this->variant();

        $this->checkout([$variant->id => 1], ['email' => 'buyer@example.test']);

        Mail::assertSent(OrderPlaced::class, fn ($mail) => $mail->hasTo('buyer@example.test'));
        Mail::assertSentCount(1);
        $this->assertNotNull(Order::firstOrFail()->confirmation_sent_at);
    }

    // ---------- payment method ----------

    public function test_choosing_onepay_without_credentials_falls_back_to_cod(): void
    {
        config(['services.onepay.app_id' => null, 'services.onepay.hash_salt' => null]);
        $variant = $this->variant();

        $response = $this->checkout([$variant->id => 1], ['payment_method' => 'onepay']);

        $order = Order::firstOrFail();
        $this->assertSame('cod', $order->payment_method);
        $this->assertStringStartsWith(url("/orders/{$order->reference}/thank-you"), $response->headers->get('Location'));
    }

    public function test_the_checkout_page_only_offers_online_payment_when_it_is_configured(): void
    {
        config(['services.onepay.app_id' => null, 'services.onepay.hash_salt' => null]);
        $variant = $this->variant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id]);

        $this->get(route('checkout.show'))->assertOk()->assertDontSee('value="onepay"', false);

        config(['services.onepay.app_id' => 'APP123', 'services.onepay.hash_salt' => 'salty']);
        $this->app->forgetScopedInstances();

        $this->get(route('checkout.show'))->assertOk()->assertSee('value="onepay"', false);
    }
}
