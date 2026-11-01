<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Mail\OrderShipped;
use App\Services\OrderService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    // ---------- the rules ----------

    public function test_orders_move_forward_one_legal_step_at_a_time(): void
    {
        Mail::fake();
        $service = app(OrderService::class);
        $order = $this->order();

        $this->assertFalse($service->deliver($order), 'cannot deliver before shipping');
        $this->assertTrue($service->confirm($order));
        $this->assertFalse($service->confirm($order->fresh()), 'already confirmed');
        $this->assertTrue($service->ship($order->fresh(), 'Domex', 'TRK-1'));
        $this->assertTrue($service->deliver($order->fresh()));

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertNotNull($order->shipped_at);
        $this->assertNotNull($order->delivered_at);
    }

    public function test_shipping_records_courier_and_tracking_and_emails_the_customer_once(): void
    {
        Mail::fake();
        $order = $this->order();

        app(OrderService::class)->ship($order, '  Domex ', 'DX123');
        $order->refresh();

        $this->assertSame('Domex', $order->courier);
        $this->assertSame('DX123', $order->tracking_number);
        $this->assertNotNull($order->shipped_email_sent_at);
        Mail::assertSent(OrderShipped::class, fn ($m) => $m->hasTo('nimali@example.test'));
        Mail::assertSentCount(1);
    }

    public function test_no_shipped_email_without_an_address_on_the_order(): void
    {
        Mail::fake();
        $order = $this->order(['email' => null]);

        $this->assertTrue(app(OrderService::class)->ship($order));

        Mail::assertNothingSent();
        $this->assertSame('shipped', $order->fresh()->status);
    }

    public function test_a_cancelled_order_cannot_be_confirmed_or_shipped(): void
    {
        Mail::fake();
        $service = app(OrderService::class);
        $order = $this->order(['status' => 'cancelled']);

        $this->assertFalse($service->confirm($order));
        $this->assertFalse($service->ship($order));
        $this->assertSame('cancelled', $order->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_delivering_a_cod_order_marks_it_paid(): void
    {
        Mail::fake();
        $service = app(OrderService::class);
        $order = $this->order(['status' => 'shipped', 'payment_method' => 'cod']);

        $service->deliver($order);

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_whatsapp_link_uses_the_sri_lankan_country_code(): void
    {
        $this->assertSame('https://wa.me/94771234567', $this->order(['phone' => '077 123 4567'])->whatsappUrl());
        $this->assertNull($this->order(['phone' => '123'])->whatsappUrl());
    }

    // ---------- the screens ----------

    public function test_list_tabs_filter_by_stage_and_show_counts(): void
    {
        $this->actingAsAdmin();
        $pending = $this->order();
        $confirmed = $this->order(['status' => 'confirmed']);
        $cancelled = $this->order(['status' => 'cancelled']);

        Livewire::test(ListOrders::class)
            ->assertCanSeeTableRecords([$pending, $confirmed, $cancelled])
            ->set('activeTab', 'pending')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$confirmed, $cancelled])
            ->set('activeTab', 'confirmed')
            ->assertCanSeeTableRecords([$confirmed])
            ->assertCanNotSeeTableRecords([$pending]);
    }

    public function test_list_filters_by_payment_status_and_date(): void
    {
        $this->actingAsAdmin();
        $paid = $this->order(['payment_status' => 'paid']);
        $failed = $this->order(['payment_status' => 'failed']);
        $old = $this->order(['created_at' => now()->subDays(40)]);

        Livewire::test(ListOrders::class)
            ->filterTable('payment_status', 'failed')
            ->assertCanSeeTableRecords([$failed])
            ->assertCanNotSeeTableRecords([$paid])
            ->removeTableFilters()
            ->filterTable('placed', ['from' => now()->subDays(7)->toDateString(), 'until' => null])
            ->assertCanSeeTableRecords([$paid, $failed])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_bulk_confirm_skips_orders_that_cannot_be_confirmed(): void
    {
        $this->actingAsAdmin();
        $pending = $this->order();
        $shipped = $this->order(['status' => 'shipped']);

        Livewire::test(ListOrders::class)
            ->selectTableRecords([$pending->getKey(), $shipped->getKey()])
            ->callAction(TestAction::make('confirm')->table()->bulk());

        $this->assertSame('confirmed', $pending->fresh()->status);
        $this->assertSame('shipped', $shipped->fresh()->status);
    }

    public function test_view_page_ships_an_order_from_the_header_action(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $order = $this->order(['status' => 'confirmed']);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertSee($order->reference)
            ->callAction('ship', ['courier' => 'Pronto', 'tracking_number' => 'PR-77'])
            ->assertHasNoActionErrors();

        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertSame('PR-77', $order->tracking_number);
    }

    public function test_view_page_saves_an_internal_note(): void
    {
        $this->actingAsAdmin();
        $order = $this->order();

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('note', ['admin_note' => 'Call before delivery'])
            ->assertHasNoActionErrors();

        $this->assertSame('Call before delivery', $order->fresh()->admin_note);
    }

    public function test_cancelling_from_the_view_page_restocks(): void
    {
        $this->actingAsAdmin();
        $variant = $this->variant(['stock' => 4]);
        $order = $this->order([], [$variant]);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])->callAction('cancel');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, $variant->fresh()->stock);
    }

    // ---------- packing slip ----------

    public function test_packing_slip_is_for_admins_only(): void
    {
        $order = $this->order();
        $url = route('admin.orders.packing-slip', $order);

        $this->get($url)->assertRedirect();                                   // guest
        $this->actingAs($this->customer())->get($url)->assertForbidden();     // customer
    }

    public function test_packing_slip_shows_what_to_pack_and_collect(): void
    {
        $this->actingAsAdmin();
        $order = $this->order(['note' => 'Leave with the guard']);

        $this->get(route('admin.orders.packing-slip', $order))
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee('12 Temple Road')
            ->assertSee('Leave with the guard')
            ->assertSee('Cash on delivery: collect');
    }
}
