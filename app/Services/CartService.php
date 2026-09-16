<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Session-keyed cart for guests. Stored as [variant_id => qty].
 * Customer accounts (v2) can move this to a carts table without changing callers.
 */
class CartService
{
    private const KEY = 'cart.items';

    private ?Collection $lines = null;

    /** @return array<int,int> */
    public function raw(): array
    {
        return Session::get(self::KEY, []);
    }

    public function add(ProductVariant $variant, int $qty = 1): void
    {
        $items = $this->raw();
        $items[$variant->id] = min(($items[$variant->id] ?? 0) + max(1, $qty), $variant->stock);
        $this->save($items);
    }

    public function update(int $variantId, int $qty): void
    {
        $items = $this->raw();
        if ($qty <= 0) {
            unset($items[$variantId]);
        } else {
            $stock = ProductVariant::whereKey($variantId)->value('stock') ?? 0;
            $items[$variantId] = min($qty, max(1, $stock));
        }
        $this->save($items);
    }

    public function remove(int $variantId): void
    {
        $items = $this->raw();
        unset($items[$variantId]);
        $this->save($items);
    }

    public function clear(): void
    {
        $this->save([]);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Cart lines with live variant data. Variants that were deleted or deactivated are dropped silently.
     *
     * @return Collection<int, object{variant: ProductVariant, qty: int, lineTotal: int}>
     */
    public function lines(): Collection
    {
        if ($this->lines !== null) {
            return $this->lines;
        }

        $items = $this->raw();
        if ($items === []) {
            return $this->lines = collect();
        }

        $variants = ProductVariant::with(['product.images', 'sizeOption', 'color'])
            ->whereKey(array_keys($items))
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $lines = collect();
        foreach ($items as $id => $qty) {
            $variant = $variants->get($id);
            if (! $variant) {
                continue;
            }
            $qty = min($qty, $variant->stock);
            if ($qty < 1) {
                continue;
            }
            $lines->push((object) [
                'variant' => $variant,
                'qty' => $qty,
                'lineTotal' => $variant->price * $qty,
            ]);
        }

        return $this->lines = $lines;
    }

    public function subtotal(): int
    {
        return (int) $this->lines()->sum('lineTotal');
    }

    public function shipping(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }

        return $this->subtotal() >= config('kayaa.free_shipping_over') ? 0 : config('kayaa.shipping_fee');
    }

    public function total(): int
    {
        return $this->subtotal() + $this->shipping();
    }

    /** Amount still needed to unlock free delivery, or 0. */
    public function remainingForFreeShipping(): int
    {
        return max(0, config('kayaa.free_shipping_over') - $this->subtotal());
    }

    private function save(array $items): void
    {
        Session::put(self::KEY, $items);
        $this->lines = null;
    }
}
