<?php

namespace Tests\Concerns;

use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Testing\TestResponse;

/** Drives the real checkout form the way a shopper would. */
trait PlacesStorefrontOrders
{
    protected function checkoutData(array $overrides = []): array
    {
        return [
            'first_name' => 'Nimali',
            'last_name' => 'Perera',
            'phone' => '077 123 4567',
            'email' => 'nimali@example.test',
            'address' => '12 Temple Road',
            'city' => 'Nugegoda',
            'district' => 'Colombo',
            'note' => 'Ring the bell',
            'payment_method' => 'cod',
            ...$overrides,
        ];
    }

    /**
     * Fills the cart with [variant id => qty] and posts the checkout form.
     *
     * A guest's cart is the session, so it is written there directly (which also
     * lets a test ask for more than is in stock, as a stale cart can). A signed-in
     * customer's cart lives in the database, so it goes through the cart service.
     * Scoped services are dropped first because the test app, unlike production,
     * is shared between requests.
     */
    protected function checkout(array $cart, array $overrides = []): TestResponse
    {
        $this->app->forgetScopedInstances();

        $request = $this;

        if (auth()->check()) {
            foreach ($cart as $variantId => $qty) {
                app(CartService::class)->add(ProductVariant::findOrFail($variantId), $qty);
            }
        } else {
            $request = $this->withSession(['cart.items' => $cart]);
        }

        $this->app->forgetScopedInstances();

        return $request->post(route('checkout.store'), $this->checkoutData($overrides));
    }
}
