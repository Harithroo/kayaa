<?php

namespace Tests\Feature\Storefront;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShopData;
use Tests\Concerns\FakesOnepay;
use Tests\TestCase;

/**
 * A successful transaction proves that *some* payment happened, not that it was
 * for *this* order. The attack: pay for a cheap order, then present that
 * transaction id with an expensive order's reference.
 */
class OnepaySecurityTest extends TestCase
{
    use BuildsShopData, FakesOnepay, RefreshDatabase;

    private Order $cheap;

    private Order $expensive;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->configureOnepay();

        $this->cheap = $this->onepayOrder(['total' => 100000]);       // Rs 1,000
        $this->expensive = $this->onepayOrder(['total' => 900000]);   // Rs 9,000
    }

    public function test_a_transaction_already_used_for_one_order_cannot_pay_another(): void
    {
        $this->fakeOnepayStatus();

        // The honest payment settles the cheap order.
        $this->postCallback('TX-CHEAP', $this->cheap)->assertOk();
        $this->assertSame('paid', $this->cheap->fresh()->payment_status);

        // The same transaction id, replayed against the expensive order.
        $this->postCallback('TX-CHEAP', $this->expensive)->assertOk();

        $this->assertSame('pending', $this->expensive->fresh()->payment_status);
        $this->assertNull($this->expensive->fresh()->payment_reference);
    }

    public function test_the_replay_is_also_refused_through_the_browser_return_url(): void
    {
        $this->fakeOnepayStatus();
        $this->postCallback('TX-CHEAP', $this->cheap);

        // The signed-in owner of the expensive order, coming back from "payment".
        $owner = $this->customer();
        $this->expensive->update(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('payment.return', $this->expensive).'?transaction_id=TX-CHEAP')
            ->assertRedirect();

        $this->assertSame('pending', $this->expensive->fresh()->payment_status);
    }

    public function test_a_transaction_for_a_smaller_amount_cannot_pay_a_bigger_order(): void
    {
        // The cheap payment has not been recorded on the cheap order yet, so the
        // only thing that can stop the swap is the amount Onepay reports.
        $this->fakeOnepayStatus(['amount' => '1000.00']);

        $this->postCallback('TX-CHEAP', $this->expensive)->assertOk();

        $order = $this->expensive->fresh();
        $this->assertSame('pending', $order->payment_status, 'not paid, and not failed either');
        $this->assertNull($order->paid_at);
        $this->assertNull($order->payment_reference);
    }

    public function test_a_transaction_that_names_a_different_order_cannot_pay_this_one(): void
    {
        $this->fakeOnepayStatus(['additional_data' => $this->cheap->reference]);

        $this->postCallback('TX-CHEAP', $this->expensive);

        $this->assertSame('pending', $this->expensive->fresh()->payment_status);
    }

    public function test_the_amount_must_match_to_the_cent_in_any_common_notation(): void
    {
        foreach (['9000.00', '9000', 9000, 9000.0, '9,000.00'] as $i => $amount) {
            $order = $this->onepayOrder(['total' => 900000]);
            $this->resetHttpFakes();
            $this->fakeOnepayStatus(['amount' => $amount]);

            $this->postCallback("TX-OK-{$i}", $order);

            $this->assertSame('paid', $order->fresh()->payment_status, 'amount '.var_export($amount, true));
        }

        $short = $this->onepayOrder(['total' => 900000]);
        $this->resetHttpFakes();
        $this->fakeOnepayStatus(['amount' => '8999.99']);
        $this->postCallback('TX-SHORT', $short);

        $this->assertSame('pending', $short->fresh()->payment_status);
    }

    public function test_a_payment_that_matches_amount_and_reference_is_accepted(): void
    {
        $this->fakeOnepayStatus(['amount' => '9000.00', 'additional_data' => $this->expensive->reference]);

        $this->postCallback('TX-GOOD', $this->expensive);

        $order = $this->expensive->fresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('TX-GOOD', $order->payment_reference);
    }

    public function test_a_response_without_amount_or_reference_is_still_accepted(): void
    {
        // Onepay's response shape is not fully pinned down, so absent fields are
        // not held against the payment; only fields that are present are checked.
        $this->fakeOnepayStatus();

        $this->postCallback('TX-BARE', $this->expensive);

        $this->assertSame('paid', $this->expensive->fresh()->payment_status);
    }

    public function test_a_cash_on_delivery_order_cannot_be_marked_paid_through_the_gateway(): void
    {
        $this->fakeOnepayStatus();
        $cod = $this->order(['payment_method' => 'cod', 'total' => 900000]);

        $this->postCallback('TX-ANY', $cod)->assertOk();

        $this->assertSame('pending', $cod->fresh()->payment_status);
    }

    public function test_a_refused_attempt_does_not_mark_the_victim_order_failed(): void
    {
        $this->fakeOnepayStatus(['amount' => '1000.00']);

        $this->postCallback('TX-CHEAP', $this->expensive);

        $this->assertNotSame('failed', $this->expensive->fresh()->payment_status);
        $this->assertTrue($this->expensive->fresh()->isPayable(), 'the real customer can still pay');
    }

    private function postCallback(string $transactionId, Order $forOrder)
    {
        return $this->postJson(route('payment.callback'), [
            'transaction_id' => $transactionId,
            'additional_data' => $forOrder->reference,
        ]);
    }

    private function onepayOrder(array $overrides = []): Order
    {
        return $this->order(['payment_method' => 'onepay', ...$overrides]);
    }
}
