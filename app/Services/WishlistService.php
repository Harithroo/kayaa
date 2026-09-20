<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

/**
 * The wishlist, as product ids, newest first.
 * Guests: a long-lived (encrypted) cookie — sessions expire after two hours, a wishlist should last months.
 * Signed-in customers: wishlist_items, so it follows them across devices.
 * On sign-in the guest list is merged into the account's (mergeGuestWishlist).
 */
class WishlistService
{
    private const COOKIE = 'kayaa_wishlist';

    private const MINUTES = 60 * 24 * 365;

    private const MAX = 60;

    /** @var array<int,int>|null */
    private ?array $ids = null;

    /** @return array<int,int> */
    public function ids(): array
    {
        if ($this->ids !== null) {
            return $this->ids;
        }

        if ($user = Auth::user()) {
            return $this->ids = DB::table('wishlist_items')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $this->ids = $this->guestIds();
    }

    /** Fold the guest cookie list into the customer's saved wishlist, called on sign-in. */
    public function mergeGuestWishlist(User $user): void
    {
        $guest = $this->guestIds();

        if ($guest !== []) {
            $saved = DB::table('wishlist_items')->where('user_id', $user->id)->pluck('product_id')->all();
            $new = array_diff($guest, $saved);
            $live = Product::active()->whereKey($new)->pluck('id')->all();

            // Oldest first, so the ids — and the newest-first order — match the guest list.
            foreach (array_reverse(array_values(array_intersect($new, $live))) as $productId) {
                DB::table('wishlist_items')->insertOrIgnore([
                    'user_id' => $user->id, 'product_id' => $productId, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // The list now lives on the account; don't leave a copy behind on a shared device.
        Cookie::queue(Cookie::forget(self::COOKIE));
        $this->ids = null;
    }

    /** @return array<int,int> */
    private function guestIds(): array
    {
        $raw = json_decode((string) request()->cookie(self::COOKIE, '[]'), true);

        return is_array($raw)
            ? array_values(array_unique(array_filter(array_map('intval', $raw))))
            : [];
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    /** Adds or removes the product. Returns true when it is now saved. */
    public function toggle(Product $product): bool
    {
        $ids = $this->ids();

        if ($this->has($product->id)) {
            $ids = array_values(array_diff($ids, [$product->id]));
            $saved = false;
        } else {
            array_unshift($ids, $product->id);
            $ids = array_slice($ids, 0, self::MAX);
            $saved = true;
        }

        $this->save($ids);

        return $saved;
    }

    public function remove(int $productId): void
    {
        $this->save(array_values(array_diff($this->ids(), [$productId])));
    }

    /** Saved products that are still on sale, in the order they were saved. Drops ids that no longer exist. */
    public function products(): Collection
    {
        $ids = $this->ids();
        if ($ids === []) {
            return collect();
        }

        $products = Product::active()->forCards()->whereKey($ids)->get()->keyBy('id');

        if ($products->count() !== count($ids)) {
            $this->save(array_values(array_filter($ids, fn ($id) => $products->has($id))));
        }

        return collect($this->ids())->map(fn ($id) => $products->get($id))->filter()->values();
    }

    private function save(array $ids): void
    {
        $user = Auth::user();

        if (! $user) {
            $this->ids = $ids;
            Cookie::queue(self::COOKIE, json_encode($ids), self::MINUTES);

            return;
        }

        DB::transaction(function () use ($user, $ids) {
            DB::table('wishlist_items')->where('user_id', $user->id)
                ->whereNotIn('product_id', $ids ?: [0])
                ->delete();

            $saved = DB::table('wishlist_items')->where('user_id', $user->id)->pluck('product_id')->all();
            // Insert oldest first so newer saves get higher ids (the list is read newest-id first).
            foreach (array_reverse(array_diff($ids, $saved)) as $productId) {
                DB::table('wishlist_items')->insert([
                    'user_id' => $user->id, 'product_id' => $productId, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        $this->ids = $ids;
    }
}
