<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Request $request, Product $product): View
    {
        abort_unless($product->status === 'active', 404);

        $product->load([
            'images', 'category.department', 'sizeScale.options',
            'variants.sizeOption', 'variants.color',
        ]);

        $related = Product::active()->forCards()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->inRandomOrder()->limit(4)->get();

        // Variant matrix for the size/colour picker: [color_id|0][size_option_id] => {id, price, compare_at_price, stock}
        $matrix = [];
        foreach ($product->activeVariants() as $v) {
            $matrix[$v->color_id ?? 0][$v->size_option_id] = [
                'id' => $v->id,
                'price' => $v->price,
                'compare' => $v->compare_at_price,
                'stock' => $v->stock,
                'priceText' => money($v->price),
                'compareText' => $v->compare_at_price ? money($v->compare_at_price) : null,
            ];
        }

        $reviews = $product->reviews()->approved()->latest()->limit(20)->get();
        $ratingBreakdown = $product->reviews()->approved()
            ->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating');

        $faqs = Faq::active()->forProduct($product)->get();

        // One review per signed-in customer; guests can always leave one.
        $hasReviewed = $request->user()
            ? $product->reviews()->where('user_id', $request->user()->id)->exists()
            : false;

        return view('store.product', compact(
            'product', 'related', 'matrix', 'reviews', 'ratingBreakdown', 'faqs', 'hasReviewed'
        ));
    }
}
