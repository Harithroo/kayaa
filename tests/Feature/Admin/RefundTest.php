<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Widgets\TopSellers;
use App\Services\OrderService;
use App\Support\SalesReport;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsShopData;
use Tests\Concerns\FakesOnepay;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use BuildsShopData, FakesOnepay, RefreshDatabase;

    public function test_marking_a_paid_order_refunded_stamps_the_timeline_and_appends_the_note(): void
    {
        $this->actingAsAdmin();
        $order = $this->order(['payment_status' => 'paid', 'admin_note' => 'Call first']);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('refund', ['note' => 'Bank transfer 44'])
            ->assertHasNoActionErrors();

        $order->refresh();
        $this->assertSame('refunded', $order->payment_status);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame("Call first\nRefund: Bank transfer 44", $order->admin_note);
    }

    public function test_the_note_is_optional(): void
    {
        $this->actingAsAdmin();
        $order = $this->order(['payment_status' => 'paid']);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])->callAction('refund');

        $order->refresh();
        $this->assertSame('refunded', $order->payment_status);
        $this->assertNull($order->admin_note);
    }

    public function test_only_paid_orders_can_be_refunded(): void
    {
        $this->actingAsAdmin();
        $service = app(OrderService::class);

        foreach (['pending', 'failed', 'refunded'] as $state) {
            $order = $this->order(['payment_status' => $state]);

            $this->assertFalse($service->refund($order), "{$state} order");
            $this->assertSame($state, $order->fresh()->payment_status);
            $this->assertNull($order->fresh()->refunded_at);

            Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])->assertActionHidden('refund');
        }

        Livewire::test(ViewOrder::class, ['record' => $this->order(['payment_status' => 'paid'])->getRouteKey()])
            ->assertActionVisible('refund');
    }

    public function test_a_cancelled_but_paid_order_can_still_be_refunded(): void
    {
        $this->actingAsAdmin();
        $order = $this->order(['payment_status' => 'paid', 'status' => 'cancelled']);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertActionVisible('refund')
            ->callAction('refund');

        $this->assertSame('refunded', $order->fresh()->payment_status);
    }

    public function test_mark_paid_is_hidden_for_refunded_orders(): void
    {
        $this->actingAsAdmin();

        $refunded = $this->order(['payment_status' => 'refunded']);
        $pending = $this->order();

        Livewire::test(ViewOrder::class, ['record' => $refunded->getRouteKey()])->assertActionHidden('paid');
        Livewire::test(ViewOrder::class, ['record' => $pending->getRouteKey()])->assertActionVisible('paid');
    }

    public function test_the_order_page_shows_the_refund_in_the_timeline(): void
    {
        $this->actingAsAdmin();
        $order = $this->order(['payment_status' => 'refunded', 'refunded_at' => now()]);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])->assertSee('Refunded');
    }

    public function test_cancelling_a_paid_order_warns_that_it_is_not_a_refund(): void
    {
        $this->actingAsAdmin();
        $paid = $this->order(['payment_status' => 'paid']);
        $unpaid = $this->order();

        Livewire::test(ViewOrder::class, ['record' => $paid->getRouteKey()])
            ->assertActionExists('cancel', fn (Action $action) => str_contains((string) $action->getModalDescription(), 'already PAID')
                && str_contains((string) $action->getModalDescription(), 'Mark refunded'));

        Livewire::test(ViewOrder::class, ['record' => $unpaid->getRouteKey()])
            ->assertActionExists('cancel', fn (Action $action) => ! str_contains((string) $action->getModalDescription(), 'PAID'));
    }

    // ---------- a refund is not a sale ----------

    public function test_sales_report_ignores_refunded_orders(): void
    {
        $this->order(['total' => 100000]);
        $this->order(['total' => 700000, 'payment_status' => 'refunded']);

        $today = SalesReport::today();
        $this->assertSame(100000, $today['revenue']);
        $this->assertSame(1, $today['orders']);

        $daily = SalesReport::daily(7);
        $this->assertSame(100000, $daily[now()->toDateString()]['revenue']);
        $this->assertSame(1, $daily[now()->toDateString()]['orders']);
    }

    public function test_top_sellers_ignore_refunded_orders(): void
    {
        $this->actingAsAdmin();
        $kept = $this->variant([], null, ['name' => 'Kept Bib']);
        $refunded = $this->variant([], null, ['name' => 'Refunded Cap']);
        $this->order([], [$kept]);
        $this->order(['payment_status' => 'refunded'], [$refunded]);

        Livewire::test(TopSellers::class)
            ->assertSee('Kept Bib')
            ->assertDontSee('Refunded Cap');
    }

    public function test_customer_totals_ignore_refunded_orders(): void
    {
        $customer = $this->customer();
        $this->order(['user_id' => $customer->id, 'total' => 100000]);
        $this->order(['user_id' => $customer->id, 'total' => 300000, 'payment_status' => 'refunded']);

        $row = CustomerResource::getEloquentQuery()->findOrFail($customer->id);

        $this->assertSame(2, $row->orders_count);
        $this->assertSame(1, (int) $row->kept_orders_count);
        $this->assertSame(100000, (int) $row->total_spent);
    }

    // ---------- a refund sticks ----------

    public function test_a_retried_onepay_callback_does_not_make_a_refunded_order_paid_again(): void
    {
        $this->configureOnepay();
        $this->fakeOnepayStatus();
        $order = $this->order(['payment_method' => 'onepay', 'payment_status' => 'refunded', 'payment_reference' => 'TX-1']);

        $this->postJson(route('payment.callback'), ['transaction_id' => 'TX-1', 'additional_data' => $order->reference])
            ->assertOk();

        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertNull($order->fresh()->paid_at);
    }

    public function test_delivering_a_refunded_cod_order_does_not_make_it_paid_again(): void
    {
        $order = $this->order(['payment_method' => 'cod', 'payment_status' => 'refunded', 'status' => 'shipped']);

        app(OrderService::class)->deliver($order);

        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame('refunded', $order->fresh()->payment_status);
    }
}
