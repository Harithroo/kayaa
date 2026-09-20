<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Reviews are submitted by anyone (signed in or not) and stay hidden until an
     * admin approves them in the panel.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'active', 404);

        $user = $request->user();

        $data = $request->validate([
            'name' => [$user ? 'nullable' : 'required', 'string', 'max:80'],
            'email' => [$user ? 'nullable' : 'nullable', 'email', 'max:190'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if ($user && $product->reviews()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'You have already reviewed this product.')->withFragment('reviews');
        }

        Review::create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'name' => $user?->name ?? $data['name'],
            'email' => $user?->email ?? ($data['email'] ?? null),
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
            'is_verified_purchase' => (bool) $user?->hasPurchased($product),
            'is_approved' => false,
        ]);

        return back()
            ->with('success', 'Thanks — your review is with us and appears once it is approved.')
            ->withFragment('reviews');
    }
}
