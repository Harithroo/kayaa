<?php

namespace Tests\Feature\Storefront;

use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

class CartTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_adding_puts_the_variant_in_the_cart_and_adding_again_adds_up(): void
    {
        $variant = $this->variant(['stock' => 10]);

        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'qty' => 2])->assertRedirect();
        $this->assertSame([$variant->id => 2], session('cart.items'));

        $this->post(route('cart.add'), ['variant_id' => $variant->id])->assertRedirect();
        $this->assertSame([$variant->id => 3], session('cart.items'));
    }

    public function test_the_cart_page_lists_what_was_added_with_the_right_totals(): void
    {
        $variant = $this->variant(['stock' => 10, 'price' => 250000]);

        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'qty' => 2]);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee($variant->product->name)
            ->assertSee('Rs. 5,000');
    }

    public function test_adding_more_than_is_in_stock_is_capped_at_stock(): void
    {
        $variant = $this->variant(['stock' => 3]);

        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'qty' => 10]);
        $this->assertSame([$variant->id => 3], session('cart.items'));

        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'qty' => 5]);
        $this->assertSame([$variant->id => 3], session('cart.items'), 'a second add cannot pass stock either');
    }

    public function test_updating_a_quantity_is_capped_at_stock(): void
    {
        $variant = $this->variant(['stock' => 4]);
        $this->post(route('cart.add'), ['variant_id' => $variant->id]);

        $this->patch(route('cart.update', $variant), ['qty' => 9])->assertRedirect();

        $this->assertSame([$variant->id => 4], session('cart.items'));
    }

    public function test_updating_to_zero_removes_the_line(): void
    {
        $variant = $this->variant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'qty' => 2]);

        $this->patch(route('cart.update', $variant), ['qty' => 0])->assertRedirect();

        $this->assertSame([], session('cart.items'));
    }

    public function test_removing_drops_only_that_line(): void
    {
        $keep = $this->variant();
        $drop = $this->variant();
        $this->post(route('cart.add'), ['variant_id' => $keep->id]);
        $this->post(route('cart.add'), ['variant_id' => $drop->id]);

        $this->delete(route('cart.remove', $drop))->assertRedirect();

        $this->assertSame([$keep->id => 1], session('cart.items'));
    }

    public function test_a_sold_out_variant_cannot_be_added(): void
    {
        $variant = $this->variant(['stock' => 0]);

        $this->post(route('cart.add'), ['variant_id' => $variant->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEmpty(session('cart.items', []));
    }

    public function test_an_inactive_variant_cannot_be_added(): void
    {
        $variant = $this->variant(['is_active' => false]);

        $this->post(route('cart.add'), ['variant_id' => $variant->id])->assertSessionHas('error');

        $this->assertEmpty(session('cart.items', []));
    }

    public function test_the_quick_add_sheet_resolves_product_size_and_colour(): void
    {
        $variant = $this->variant(['stock' => 5]);

        $this->post(route('cart.quick-add'), [
            'product' => $variant->product_id,
            'size' => $variant->size_option_id,
            'colour' => $variant->color_id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->assertSame([$variant->id => 2], session('cart.items'));
    }

    public function test_a_variant_deactivated_after_adding_is_dropped_from_the_cart(): void
    {
        $stays = $this->variant(['price' => 100000]);
        $goes = $this->variant(['price' => 900000]);
        // The last add is the one whose "added to your cart" flash the next page shows.
        $this->post(route('cart.add'), ['variant_id' => $goes->id]);
        $this->post(route('cart.add'), ['variant_id' => $stays->id]);

        $goes->update(['is_active' => false]);
        $this->app->forgetScopedInstances();

        $cart = app(CartService::class);
        $this->assertSame([$stays->id], $cart->lines()->map(fn ($l) => $l->variant->id)->all());
        $this->assertSame(100000, $cart->subtotal());

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee($stays->product->name)
            ->assertDontSee($goes->product->name);
    }

    public function test_stock_that_shrinks_after_adding_lowers_the_quantity_shown(): void
    {
        $variant = $this->variant(['stock' => 5, 'price' => 100000]);
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'qty' => 5]);

        $variant->update(['stock' => 2]);
        $this->app->forgetScopedInstances();

        $this->assertSame(200000, app(CartService::class)->subtotal());
    }

    public function test_the_cart_panel_is_returned_for_the_drawer(): void
    {
        $variant = $this->variant();

        $this->withHeader('X-Cart-Panel', '1')
            ->post(route('cart.add'), ['variant_id' => $variant->id])
            ->assertOk()
            ->assertSee($variant->product->name);
    }
}
