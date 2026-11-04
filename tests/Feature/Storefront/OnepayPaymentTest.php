<?php

namespace Tests\Feature\Storefront;

use App\Models\Order;
use App\Services\OnepayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\BuildsShopData;
use Tests\Concerns\FakesOnepay;
use Tests\Concerns\PlacesStorefrontOrders;
use Tests\TestCase;

class OnepayPaymentTest extends TestCase
{
    use BuildsShopData, FakesOnepay, PlacesStorefrontOrders, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Mail::fake();
        $this->configureOnepay();
        config(['kayaa.shipping_fee' => 45000, 'kayaa.free_shipping_over' => 750000]);
    }

    // ---------- starting a payment ----------

    public function test_checking_out_with_onepay_asks_for_a_link_and_sends_the_customer_there(): void
    {
        $this->fakeOnepayLink('https://pay.onepay.test/checkout/abc');
        $variant = $this->variant(['price' => 500000, 'stock' => 5]);

        $response = $this->checkout([$variant->id => 1], ['payment_method' => 'onepay']);

        $response->assertRedirect('https://pay.onepay.test/checkout/abc');

        $order = Order::firstOrFail();
        $this->assertSame('onepay', $order->payment_method);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(545000, $order->total);
        $this->assertSame(4, $variant->fresh()->stock, 'stock is held while the customer pays');

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($order) {
            $body = $request->data();

            return $request->url() === 'https://api.onepay.test/v3/checkout/link/'
                && $request->hasHeader('Authorization', 'tok_test')
                && $body['app_id'] === 'APP123'
                && $body['amount'] == 5450.0
                && $body['currency'] === 'LKR'
                && $body['reference'] === $order->reference
                && $body['additionalData'] === $order->reference
                && $body['customer_phone_number'] === '+94771234567'
                && $body['customer_email'] === 'nimali@example.test'
                && $body['callback_url'] === route('payment.callback')
                && str_starts_with($body['transaction_redirect_url'], route('payment.return', $order))
                && str_contains($body['transaction_redirect_url'], 'signature=');
        });
    }

    public function test_the_request_hash_is_sha256_of_app_id_currency_amount_and_salt(): void
    {
        $this->fakeOnepayLink();
        $order = $this->order(['payment_method' => 'onepay', 'total' => 545000]);

        app(OnepayGateway::class)->createCheckoutLink($order, 'https://shop.test/return', 'https://shop.test/callback');

        $expected = hash('sha256', 'APP123'.'LKR'.'5450.00'.'salty');
        $this->assertSame($expected, app(OnepayGateway::class)->hash('LKR', '5450.00'));

        Http::assertSent(fn (Request $request) => $request->data()['hash'] === $expected
            && ! str_contains($request->body(), 'salty'));   // the salt never travels
    }

    public function test_amounts_with_cents_are_sent_to_the_cent(): void
    {
        $this->assertSame('1234.50', $this->order(['total' => 123450])->amountForGateway());
        $this->assertSame('0.05', $this->order(['total' => 5])->amountForGateway());
    }

    public function test_a_refused_link_keeps_the_order_and_offers_a_retry(): void
    {
        Http::fake(['api.onepay.test/*' => Http::response(['error' => 'nope'], 500)]);
        $variant = $this->variant(['stock' => 5]);

        $response = $this->checkout([$variant->id => 1], ['payment_method' => 'onepay']);

        $order = Order::firstOrFail();
        $this->assertStringStartsWith(url("/orders/{$order->reference}/thank-you"), $response->headers->get('Location'));
        $response->assertSessionHas('error');
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(4, $variant->fresh()->stock, 'the order, and its stock, are kept');
        $this->assertTrue($order->isPayable());
        $this->assertSame([], session('cart.items'), 'the shopper is not asked to fill the cart again');
    }

    public function test_an_unreachable_gateway_keeps_the_order_too(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $variant = $this->variant();

        $this->checkout([$variant->id => 1], ['payment_method' => 'onepay'])->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_a_response_without_a_checkout_url_is_treated_as_a_failure(): void
    {
        Http::fake(['api.onepay.test/*' => Http::response(['status' => 1, 'data' => []])]);
        $variant = $this->variant();

        $this->checkout([$variant->id => 1], ['payment_method' => 'onepay'])->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_the_checkout_url_is_found_wherever_onepay_nests_it(): void
    {
        foreach ([
            ['redirect_url' => 'https://pay.test/a'],
            ['data' => ['gateway' => ['redirect_url' => 'https://pay.test/a']]],
            ['result' => ['payment_url' => 'https://pay.test/a']],
        ] as $body) {
            $this->resetHttpFakes();
            Http::fake(['api.onepay.test/*' => Http::response($body)]);

            $url = app(OnepayGateway::class)->createCheckoutLink($this->order(['payment_method' => 'onepay']), 'r', 'c');

            $this->assertSame('https://pay.test/a', $url);
        }
    }

    public function test_retrying_from_the_order_page_starts_a_new_payment(): void
    {
        $this->fakeOnepayLink('https://pay.onepay.test/retry');
        $order = $this->order(['payment_method' => 'onepay', 'payment_status' => 'failed']);

        $this->post($order->payUrl())->assertRedirect('https://pay.onepay.test/retry');
    }

    public function test_retry_is_not_offered_for_paid_cod_or_cancelled_orders(): void
    {
        $this->fakeOnepayLink();

        $paid = $this->order(['payment_method' => 'onepay', 'payment_status' => 'paid']);
        $this->post($paid->payUrl())->assertRedirect();
        Http::assertNothingSent();

        $cod = $this->order(['payment_method' => 'cod']);
        $this->post($cod->payUrl())->assertNotFound();

        $cancelled = $this->order(['payment_method' => 'onepay', 'status' => 'cancelled']);
        $this->post($cancelled->payUrl())->assertNotFound();
    }

    public function test_retry_needs_the_signed_link_or_the_owner(): void
    {
        $order = $this->order(['payment_method' => 'onepay']);

        $this->post(URL::to("/orders/{$order->reference}/pay"))->assertNotFound();
    }

    public function test_retry_without_credentials_explains_instead_of_failing(): void
    {
        config(['services.onepay.app_id' => null, 'services.onepay.hash_salt' => null]);
        $order = $this->order(['payment_method' => 'onepay']);

        $this->post($order->payUrl())->assertRedirect()->assertSessionHas('error');
    }

    // ---------- the server-to-server callback ----------

    public function test_the_callback_marks_the_order_paid_only_after_a_successful_status_lookup(): void
    {
        $this->fakeOnepayStatus();
        $order = $this->order(['payment_method' => 'onepay']);

        $this->postJson(route('payment.callback'), ['transaction_id' => 'TX-1', 'additional_data' => $order->reference])
            ->assertOk()
            ->assertJson(['message' => 'ok']);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('TX-1', $order->payment_reference);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('confirmed', $order->status, 'a paid pending order is confirmed');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.onepay.test/v3/transaction/status/'
            && $request->data() === ['app_id' => 'APP123', 'onepay_transaction_id' => 'TX-1']
            && $request->hasHeader('Authorization', 'tok_test'));
    }

    public function test_the_callback_alone_proves_nothing(): void
    {
        // Onepay says the transaction did not succeed, whatever the callback claimed.
        $this->fakeOnepayStatus(status: 0);
        $order = $this->order(['payment_method' => 'onepay']);

        $this->postJson(route('payment.callback'), [
            'transaction_id' => 'TX-FAKE', 'additional_data' => $order->reference, 'status' => 1, 'paid' => true,
        ])->assertOk();

        $order->refresh();
        $this->assertSame('failed', $order->payment_status);
        $this->assertNull($order->paid_at);
        $this->assertSame('pending', $order->status);
        $this->assertTrue($order->isPayable(), 'a failed payment can be retried');
    }

    public function test_an_unavailable_status_lookup_leaves_the_order_untouched(): void
    {
        Http::fake(['api.onepay.test/*' => Http::response('boom', 502)]);
        $order = $this->order(['payment_method' => 'onepay']);

        $this->postJson(route('payment.callback'), ['transaction_id' => 'TX-1', 'additional_data' => $order->reference])
            ->assertOk();

        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
        $this->assertNull($order->payment_reference);
    }

    public function test_the_status_values_onepay_uses_for_success_are_recognised(): void
    {
        foreach ([1, '1', 1000, 'SUCCESS', 'success'] as $i => $status) {
            $this->resetHttpFakes();
            Http::fake(['api.onepay.test/*' => Http::response(['status' => $status])]);
            $order = $this->order(['payment_method' => 'onepay']);

            $this->postJson(route('payment.callback'), ['transaction_id' => "TX-{$i}", 'additional_data' => $order->reference]);

            $this->assertSame('paid', $order->fresh()->payment_status, 'status '.var_export($status, true));
        }

        foreach ([0, 2, 'FAILED', 'pending'] as $i => $status) {
            $this->resetHttpFakes();
            Http::fake(['api.onepay.test/*' => Http::response(['status' => $status])]);
            $order = $this->order(['payment_method' => 'onepay']);

            $this->postJson(route('payment.callback'), ['transaction_id' => "TX-N{$i}", 'additional_data' => $order->reference]);

            $this->assertSame('failed', $order->fresh()->payment_status, 'status '.var_export($status, true));
        }
    }

    public function test_a_callback_for_an_unknown_order_is_acknowledged_and_ignored(): void
    {
        $this->fakeOnepayStatus();
        $order = $this->order(['payment_method' => 'onepay']);

        $this->postJson(route('payment.callback'), ['transaction_id' => 'TX-1', 'additional_data' => 'KY-000000-NOPE'])
            ->assertOk()
            ->assertJson(['message' => 'unknown order']);

        Http::assertNothingSent();
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_a_callback_without_a_transaction_id_is_rejected(): void
    {
        $this->fakeOnepayStatus();
        $order = $this->order(['payment_method' => 'onepay']);

        $this->postJson(route('payment.callback'), ['additional_data' => $order->reference])->assertStatus(422);
        $this->postJson(route('payment.callback'), ['transaction_id' => '', 'additional_data' => $order->reference])->assertStatus(422);

        Http::assertNothingSent();
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_the_order_is_found_by_transaction_id_when_the_reference_is_missing(): void
    {
        $this->fakeOnepayStatus();
        $order = $this->order(['payment_method' => 'onepay', 'payment_status' => 'failed', 'payment_reference' => 'TX-9']);

        $this->postJson(route('payment.callback'), ['transaction_id' => 'TX-9'])->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_repeated_callback_does_not_pay_twice(): void
    {
        $this->fakeOnepayStatus();
        $order = $this->order(['payment_method' => 'onepay']);
        $payload = ['transaction_id' => 'TX-1', 'additional_data' => $order->reference];

        $this->postJson(route('payment.callback'), $payload)->assertOk();
        $paidAt = $order->fresh()->paid_at;

        $this->travel(5)->minutes();
        $this->postJson(route('payment.callback'), $payload)->assertOk();

        Http::assertSentCount(1);
        $this->assertEquals($paidAt, $order->fresh()->paid_at, 'the payment time is not rewritten');
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_late_failure_callback_cannot_undo_a_payment(): void
    {
        $this->fakeOnepayStatus();
        $order = $this->order(['payment_method' => 'onepay']);
        $this->postJson(route('payment.callback'), ['transaction_id' => 'TX-1', 'additional_data' => $order->reference]);

        $this->resetHttpFakes();
        $this->fakeOnepayStatus(status: 0);
        $this->postJson(route('payment.callback'), ['transaction_id' => 'TX-2', 'additional_data' => $order->reference]);

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('TX-1', $order->fresh()->payment_reference);
    }

    // ---------- the browser return ----------

    public function test_the_return_url_needs_a_signature_or_the_owner(): void
    {
        $this->fakeOnepayStatus();
        $order = $this->order(['payment_method' => 'onepay']);

        // Not signed, not signed in: indistinguishable from an order that does not exist.
        $this->get(URL::to("/orders/{$order->reference}/payment/return?transaction_id=TX-1"))->assertNotFound();

        // Signed in, but as somebody else.
        $this->actingAs($this->customer())
            ->get(URL::to("/orders/{$order->reference}/payment/return?transaction_id=TX-1"))
            ->assertNotFound();

        Http::assertNothingSent();
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_a_forged_signature_is_refused(): void
    {
        $order = $this->order(['payment_method' => 'onepay']);
        $url = $order->paymentReturnUrl();

        $this->get(preg_replace('/signature=[^&]+/', 'signature=forged', $url))->assertNotFound();
        $this->get(str_replace($order->reference, $this->order(['payment_method' => 'onepay'])->reference, $url))->assertNotFound();
    }

    public function test_returning_with_the_signed_link_and_no_transaction_just_shows_the_order(): void
    {
        $this->fakeOnepayStatus();
        $order = $this->order(['payment_method' => 'onepay']);

        $response = $this->get($order->paymentReturnUrl());

        $response->assertRedirect();
        $this->assertStringStartsWith(url("/orders/{$order->reference}/thank-you"), $response->headers->get('Location'));
        Http::assertNothingSent();
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_the_signed_in_owner_returning_with_a_transaction_settles_the_order(): void
    {
        $this->fakeOnepayStatus();
        $owner = $this->customer();
        $order = $this->order(['payment_method' => 'onepay', 'user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('payment.return', $order).'?transaction_id=TX-1')
            ->assertRedirect();

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_the_confirmation_page_reflects_the_outcome(): void
    {
        $this->fakeOnepayStatus();
        $owner = $this->customer();
        $order = $this->order(['payment_method' => 'onepay', 'user_id' => $owner->id]);

        $this->actingAs($owner)->get(route('payment.return', $order).'?transaction_id=TX-1');

        $this->get($order->fresh()->thanksUrl())->assertOk()->assertSee($order->reference);
    }
}
