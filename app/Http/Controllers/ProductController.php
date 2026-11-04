<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Product;
use App\Services\Seo\JsonLd;
use App\Services\Seo\SeoMeta;
use App\Services\WishlistService;
use App\Support\Seo\Breadcrumbs;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Request $request, Product $product): View
    {
        abort_unless($product->status === 'active', 404);

        $product->load([
            'images', 'category.department', 'sizeScale.options',
            'variants.sizeOption', 'variants.color', 'collections',
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

        // Five reviews, then a "See more reviews" link that drops the cap.
        $reviews = $product->reviews()->approved()->latest()
            ->limit($request->query('reviews') === 'all' ? 100 : 5)
            ->get();
        $ratingBreakdown = $product->reviews()->approved()
            ->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating');

        $faqs = Faq::active()->forProduct($product)->get();

        // One review per signed-in customer; guests can always leave one.
        $hasReviewed = $request->user()
            ? $product->reviews()->where('user_id', $request->user()->id)->exists()
            : false;

        // The product page's Save button renders in its saved state.
        $isWishlisted = app(WishlistService::class)->has($product->id);

        $breadcrumbs = Breadcrumbs::forProduct($product);

        // $reviews is what the page actually renders, and it is what the rating
        // markup is built from: no approved reviews on the page means no
        // aggregateRating in the JSON-LD. Marking up a rating a visitor cannot
        // see is the most commonly penalised structured-data mistake there is.
        //
        // Product and BreadcrumbList only. The page does render FAQs, but
        // FAQPage markup is for pages whose subject IS a list of questions;
        // declaring it on a product page is schema Google is entitled to
        // ignore. The contact page carries that block instead.
        $jsonLd = [
            JsonLd::product($product, $reviews),
            JsonLd::breadcrumbs($breadcrumbs),
        ];

        $meta = SeoMeta::forProduct($product, $request);

        return view('store.product', compact(
            'product', 'related', 'matrix', 'reviews', 'ratingBreakdown', 'faqs', 'hasReviewed', 'isWishlisted',
            'breadcrumbs', 'jsonLd', 'meta'
        ));
    }
}
