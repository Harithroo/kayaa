<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function __construct(private WishlistService $wishlist) {}

    public function index(): View
    {
        return view('store.wishlist', ['products' => $this->wishlist->products()]);
    }

    /** Heart button. Works as a plain form post; answers JSON when called from app.js. */
    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        abort_unless($product->status === 'active', 404);

        $saved = $this->wishlist->toggle($product);

        if ($request->expectsJson()) {
            return response()->json(['saved' => $saved, 'count' => $this->wishlist->count()]);
        }

        return back()->with('success', $saved
            ? $product->name.' saved to your wishlist.'
            : $product->name.' removed from your wishlist.');
    }
}
