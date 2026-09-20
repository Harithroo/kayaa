<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * The cart, as [variant_id => qty].
 * Guests: kept in the session. Signed-in customers: kept in cart_items, so it follows
 * them across devices. On sign-in the guest cart is merged into the account's (mergeGuestCart).
 */
class CartService
{
    private const KEY = 'cart.items';

    private ?Collection $lines = null;

    /** @var array<int,int>|null Per-request cache of the account cart. */
    private ?array $accountItems = null;

    /** @return array<int,int> */
    public function raw(): array
    {
        $user = Auth::user();
        if (! $user) {
            return Session::get(self::KEY, []);
        }

        return $this->accountItems ??= DB::table('cart_items')
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->pluck('qty', 'product_variant_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();
    }

    /**
     * Fold the guest session cart into the customer's saved cart, called on sign-in.
     * Quantities for the same size are added together and capped at stock.
     */
    public function mergeGuestCart(User $user): void
    {
        $guest = Session::get(self::KEY, []);
        if ($guest !== []) {
            $stock = ProductVariant::whereKey(array_keys($guest))->where('is_active', true)->pluck('stock', 'id');
            $saved = DB::table('cart_items')->where('user_id', $user->id)->pluck('qty', 'product_variant_id');

            foreach ($guest as $variantId => $qty) {
                if (! $stock->has($variantId) || $stock[$variantId] < 1) {
                    continue;
                }
                $merged = min((int) ($saved[$variantId] ?? 0) + (int) $qty, (int) $stock[$variantId]);
                DB::table('cart_items')->updateOrInsert(
                    ['user_id' => $user->id, 'product_variant_id' => $variantId],
                    ['qty' => $merged, 'updated_at' => now()],
                );
            }
        }

        Session::forget(self::KEY);
        $this->accountItems = null;
        $this->lines = null;
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
        $this->lines = null;

        $user = Auth::user();
        if (! $user) {
            Session::put(self::KEY, $items);

            return;
        }

        DB::transaction(function () use ($user, $items) {
            DB::table('cart_items')->where('user_id', $user->id)
                ->whereNotIn('product_variant_id', array_keys($items) ?: [0])
                ->delete();

            foreach ($items as $variantId => $qty) {
                DB::table('cart_items')->updateOrInsert(
                    ['user_id' => $user->id, 'product_variant_id' => $variantId],
                    ['qty' => $qty, 'updated_at' => now()],
                );
            }
        });

        $this->accountItems = $items;
    }
}
