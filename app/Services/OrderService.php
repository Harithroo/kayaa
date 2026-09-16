<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private CartService $cart) {}

    /**
     * Turn the session cart into an order. Totals are recalculated here from live
     * prices — nothing from the browser is trusted. Stock is locked and decremented
     * inside the transaction so two checkouts can't oversell the last item.
     *
     * For COD, stock is committed at placement (there is no later "paid" event before
     * dispatch). For PayHere, stock is also reserved here; the webhook flips payment_status.
     *
     * @param  array<string,mixed>  $customer  validated checkout fields
     *
     * @throws OutOfStockException
     */
    public function placeOrder(array $customer, string $paymentMethod): Order
    {
        $lines = $this->cart->lines();
        abort_if($lines->isEmpty(), 400, 'Your cart is empty.');

        return DB::transaction(function () use ($lines, $customer, $paymentMethod) {
            $subtotal = 0;
            $items = [];

            foreach ($lines as $line) {
                /** @var ProductVariant $variant */
                $variant = ProductVariant::with(['product', 'sizeOption', 'color'])
                    ->lockForUpdate()
                    ->findOrFail($line->variant->id);

                if (! $variant->is_active || $variant->stock < $line->qty) {
                    $this->cart->update($variant->id, $variant->stock);
                    throw new OutOfStockException($variant, $line->qty);
                }

                $variant->decrement('stock', $line->qty);

                $lineTotal = $variant->price * $line->qty;
                $subtotal += $lineTotal;
                $items[] = [
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_label' => $variant->label(),
                    'sku' => $variant->sku,
                    'unit_price' => $variant->price,
                    'qty' => $line->qty,
                    'line_total' => $lineTotal,
                ];
            }

            $shipping = $subtotal >= config('kayaa.free_shipping_over') ? 0 : config('kayaa.shipping_fee');

            $order = Order::create([
                'reference' => Order::generateReference(),
                'status' => 'pending',
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                ...$customer,
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'total' => $subtotal + $shipping,
            ]);
            $order->items()->createMany($items);

            $this->cart->clear();

            return $order;
        });
    }
}
