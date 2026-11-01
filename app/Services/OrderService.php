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
     * dispatch). For Onepay, stock is also reserved here; the callback flips payment_status.
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

    // ---------- admin status transitions ----------
    // One place for the rules, shared by the single-order buttons and the bulk
    // actions on the list. Each returns false (and changes nothing) when the
    // order is not in a state the move makes sense from.

    public function confirm(Order $order): bool
    {
        if ($order->status !== 'pending') {
            return false;
        }

        $order->update(['status' => 'confirmed']);

        return true;
    }

    public function ship(Order $order, ?string $courier = null, ?string $trackingNumber = null): bool
    {
        if (! in_array($order->status, ['pending', 'confirmed'], true)) {
            return false;
        }

        $order->update([
            'status' => 'shipped',
            'shipped_at' => now(),
            'courier' => filled($courier) ? trim($courier) : null,
            'tracking_number' => filled($trackingNumber) ? trim($trackingNumber) : null,
        ]);

        app(OrderMailer::class)->sendShipped($order);

        return true;
    }

    public function deliver(Order $order): bool
    {
        if ($order->status !== 'shipped') {
            return false;
        }

        $order->update(['status' => 'delivered', 'delivered_at' => now()]);

        // Cash is collected at the door, so a delivered COD order is a paid one.
        if ($order->payment_method === 'cod' && $order->payment_status !== 'paid') {
            $order->markPaid();
        }

        return true;
    }

    /**
     * Cancel an order and put its stock back. Used by the customer (while the
     * order is still pending) and by the admin panel, so the restock rule lives
     * in one place. Locks each variant so a concurrent checkout can't read a
     * stale count while we are adding to it.
     */
    public function cancel(Order $order, ?string $reason = null): void
    {
        if ($order->status === 'cancelled') {
            return;
        }

        DB::transaction(function () use ($order, $reason) {
            $order->loadMissing('items');

            foreach ($order->items as $item) {
                if ($item->product_variant_id === null) {
                    continue;
                }

                $variant = ProductVariant::lockForUpdate()->find($item->product_variant_id);
                $variant?->increment('stock', $item->qty);
            }

            $order->forceFill([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'note' => $reason === null
                    ? $order->note
                    : trim(($order->note ? $order->note."\n" : '').$reason),
            ])->save();
        });
    }
}
