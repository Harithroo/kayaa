<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\StockMovementResource\Pages\ListStockMovements;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

class StockHistoryTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    public function test_a_new_variant_logs_its_initial_stock_once(): void
    {
        $variant = $this->variant(['stock' => 12]);

        $rows = StockMovement::where('product_variant_id', $variant->id)->get();

        $this->assertCount(1, $rows);
        $this->assertSame('initial', $rows[0]->reason);
        $this->assertSame(12, $rows[0]->delta);
        $this->assertSame(12, $rows[0]->stock_after);
        $this->assertSame($variant->sku, $rows[0]->sku);
        $this->assertSame($variant->product_id, $rows[0]->product_id);
        $this->assertStringContainsString($variant->product->name, $rows[0]->label);
    }

    public function test_a_variant_with_no_stock_logs_nothing_yet(): void
    {
        $variant = $this->variant(['stock' => 0]);

        $this->assertSame(0, StockMovement::where('product_variant_id', $variant->id)->count());
    }

    public function test_placing_an_order_logs_one_movement_per_line_and_nothing_else(): void
    {
        $variant = $this->variant(['stock' => 10]);
        $this->placeOrderFor([$variant->id => 3]);

        $movements = StockMovement::where('product_variant_id', $variant->id)->orderBy('id')->get();

        $this->assertCount(2, $movements, 'initial + order_placed, not a duplicate from the decrement');
        $placed = $movements[1];
        $this->assertSame('order_placed', $placed->reason);
        $this->assertSame(-3, $placed->delta);
        $this->assertSame(7, $placed->stock_after);
        $this->assertSame(7, $variant->fresh()->stock);
        $this->assertNotNull($placed->order_id);
    }

    public function test_cancelling_an_order_logs_the_restock_once_and_a_second_cancel_adds_nothing(): void
    {
        $variant = $this->variant(['stock' => 10]);
        $order = $this->placeOrderFor([$variant->id => 3]);
        $service = app(OrderService::class);

        $service->cancel($order);
        $service->cancel($order->fresh());

        $cancelled = StockMovement::where('reason', 'order_cancelled')->get();

        $this->assertCount(1, $cancelled);
        $this->assertSame(3, $cancelled[0]->delta);
        $this->assertSame(10, $cancelled[0]->stock_after);
        $this->assertSame($order->id, $cancelled[0]->order_id);
        $this->assertSame(10, $variant->fresh()->stock);
    }

    public function test_the_signed_in_user_is_recorded(): void
    {
        $admin = $this->actingAsAdmin();
        $variant = $this->variant(['stock' => 10]);
        $order = $this->placeOrderFor([$variant->id => 1]);

        app(OrderService::class)->cancel($order, 'Cancelled in the admin panel.');

        $this->assertSame($admin->id, StockMovement::where('reason', 'order_cancelled')->value('user_id'));
    }

    public function test_editing_stock_in_the_product_form_logs_an_admin_edit_with_the_user(): void
    {
        $admin = $this->actingAsAdmin();
        $variant = $this->variant(['stock' => 10]);

        $page = Livewire::test(EditProduct::class, ['record' => $variant->product->getRouteKey()]);
        $key = array_key_first($page->get('data.variants'));

        $page->set("data.variants.{$key}.stock", 4)->call('save')->assertHasNoFormErrors();

        $this->assertSame(4, $variant->fresh()->stock);

        $edits = StockMovement::where('reason', 'admin_edit')->get();
        $this->assertCount(1, $edits);
        $this->assertSame(-6, $edits[0]->delta);
        $this->assertSame(4, $edits[0]->stock_after);
        $this->assertSame($admin->id, $edits[0]->user_id);
    }

    public function test_saving_the_form_without_touching_stock_logs_nothing(): void
    {
        $this->actingAsAdmin();
        $variant = $this->variant(['stock' => 10]);

        Livewire::test(EditProduct::class, ['record' => $variant->product->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(0, StockMovement::where('reason', 'admin_edit')->count());
    }

    public function test_the_history_survives_a_deleted_variant(): void
    {
        $variant = $this->variant(['stock' => 5]);
        $sku = $variant->sku;

        $variant->product->delete();

        $row = StockMovement::first();
        $this->assertNotNull($row);
        $this->assertNull($row->product_variant_id);
        $this->assertSame($sku, $row->sku);
    }

    // ---------- the screen ----------

    public function test_the_stock_history_screen_is_read_only_and_filterable(): void
    {
        $this->actingAsAdmin();
        $romper = $this->variant(['stock' => 10], null, ['name' => 'Romper']);
        $bib = $this->variant(['stock' => 20], null, ['name' => 'Bib']);
        $this->placeOrderFor([$romper->id => 2]);

        $initialRomper = StockMovement::where('product_variant_id', $romper->id)->where('reason', 'initial')->first();
        $placedRomper = StockMovement::where('reason', 'order_placed')->first();
        $initialBib = StockMovement::where('product_variant_id', $bib->id)->first();

        Livewire::test(ListStockMovements::class)
            ->assertCanSeeTableRecords([$initialRomper, $placedRomper, $initialBib])
            ->filterTable('reason', 'order_placed')
            ->assertCanSeeTableRecords([$placedRomper])
            ->assertCanNotSeeTableRecords([$initialRomper, $initialBib])
            ->removeTableFilters()
            ->filterTable('product_id', $bib->product_id)
            ->assertCanSeeTableRecords([$initialBib])
            ->assertCanNotSeeTableRecords([$initialRomper, $placedRomper]);

        $this->get('/admin/stock-movements')->assertOk()->assertSee('Stock history');
        $this->get('/admin/stock-movements/create')->assertNotFound();
    }

    /** Fills the cart (the signed-in cart if someone is signed in, else the session) and checks out. */
    private function placeOrderFor(array $cart): Order
    {
        foreach ($cart as $variantId => $qty) {
            app(CartService::class)->add(ProductVariant::findOrFail($variantId), $qty);
        }

        return app(OrderService::class)->placeOrder([
            'first_name' => 'Nimali', 'last_name' => 'Perera', 'phone' => '0771234567',
            'email' => 'nimali@example.test', 'address' => '12 Temple Road', 'city' => 'Nugegoda',
            'district' => 'Colombo',
        ], 'cod');
    }
}
