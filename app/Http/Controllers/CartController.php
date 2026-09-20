<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    /**
     * The full cart page. It is the no-JS fallback for the slide-in cart drawer,
     * and the page the drawer's "View full cart" link points at.
     */
    public function index(): View
    {
        return view('store.cart', ['cart' => $this->cart]);
    }

    /** Just the drawer contents — fetched by the front end after every cart change. */
    public function panel(): View
    {
        return view('components.store.cart-panel', ['cart' => $this->cart]);
    }

    public function add(Request $request): RedirectResponse|View
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $variant = ProductVariant::with('product')->findOrFail($data['variant_id']);
        if (! $variant->inStock()) {
            return back()->with('error', 'Sorry, that size has just sold out.');
        }

        $this->cart->add($variant, (int) ($data['qty'] ?? 1));

        if ($this->wantsPanel($request)) {
            return $this->panel();
        }

        // Without JS: stay on the page and open the cart drawer on the next render.
        return back()->with('cart_open', true)->with('success', $variant->product->name.' added to your cart.');
    }

    public function update(Request $request, ProductVariant $variant): RedirectResponse|View
    {
        $data = $request->validate(['qty' => ['required', 'integer', 'min:0', 'max:10']]);
        $this->cart->update($variant->id, (int) $data['qty']);

        return $this->wantsPanel($request) ? $this->panel() : back()->with('cart_open', $this->cameFromAnotherPage());
    }

    public function remove(Request $request, ProductVariant $variant): RedirectResponse|View
    {
        $this->cart->remove($variant->id);

        return $this->wantsPanel($request) ? $this->panel() : back()->with('cart_open', $this->cameFromAnotherPage());
    }

    /** The drawer posts with this header so it can swap in fresh HTML without a page load. */
    private function wantsPanel(Request $request): bool
    {
        return $request->header('X-Cart-Panel') === '1';
    }

    /** Don't pop the drawer open over the full cart page — that page shows the same thing. */
    private function cameFromAnotherPage(): bool
    {
        return parse_url(url()->previous(), PHP_URL_PATH) !== '/cart';
    }
}
