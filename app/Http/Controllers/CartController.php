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

    public function index(): View
    {
        return view('store.cart', ['cart' => $this->cart]);
    }

    public function add(Request $request): RedirectResponse
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

        return redirect()->route('cart.index')->with('success', $variant->product->name.' added to your cart.');
    }

    public function update(Request $request, ProductVariant $variant): RedirectResponse
    {
        $data = $request->validate(['qty' => ['required', 'integer', 'min:0', 'max:10']]);
        $this->cart->update($variant->id, (int) $data['qty']);

        return redirect()->route('cart.index');
    }

    public function remove(ProductVariant $variant): RedirectResponse
    {
        $this->cart->remove($variant->id);

        return redirect()->route('cart.index');
    }
}
