<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $results = collect();

        if ($q !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
            $query = Product::active()->forCards()
                ->where(fn ($w) => $w
                    ->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like))
                    ->orWhereHas('variants.color', fn ($c) => $c->where('name', 'like', $like)));

            // The toolbar on the results page offers the same sort and sale switch
            // as the shop listing, so they work the same way here.
            if ($request->query('sale')) {
                $query->whereHas('variants', fn ($v) => $v->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price'));
            }

            $minPrice = fn ($q) => $q->selectRaw('min(price)')->from('product_variants')->whereColumn('product_id', 'products.id');

            match ($request->query('sort')) {
                'price_asc' => $query->orderBy($minPrice),
                'price_desc' => $query->orderByDesc($minPrice),
                'new' => $query->orderByDesc('created_at'),
                default => $query->orderByDesc('is_featured')->orderByDesc('is_new')->orderByDesc('created_at'),
            };

            $results = $query->limit(48)->get();
        }

        // "Also matches": categories whose name contains the query, shown as chips
        // under the heading (prototype: the also-matches block on search.html).
        $alsoMatches = $q === '' ? collect() : Category::active()
            ->where('name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%')
            ->with('department')
            ->limit(6)->get();

        return view('store.search', compact('q', 'results', 'alsoMatches'));
    }

    /**
     * Suggestions for the search box in the header and on the search page
     * (resources/js/store/search-suggest.js). Read-only, and the same matching
     * as the results page above, so the two never disagree.
     */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['query' => $q, 'products' => [], 'categories' => [], 'tags' => []]);
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';

        $products = Product::active()->forCards()
            ->where(fn ($w) => $w
                ->where('name', 'like', $like)
                ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like)))
            ->orderByDesc('is_featured')
            ->limit(6)->get()
            ->map(fn (Product $p) => [
                'name' => $p->name,
                'url' => route('products.show', $p),
                'category' => $p->category?->name,
                // The suggestion list formats money itself, so send whole rupees.
                'price' => (int) ($p->minPrice() / 100),
                'compare_at' => $p->compareAtPrice() ? (int) ($p->compareAtPrice() / 100) : null,
            ])->values();

        $categories = Category::active()
            ->where('name', 'like', $like)
            ->with('department')
            ->limit(4)->get()
            ->map(fn (Category $c) => ['name' => $c->name, 'url' => $c->url(), 'count' => null])
            ->values();

        return response()->json([
            'query' => $q,
            'products' => $products,
            'categories' => $categories,
            'tags' => [],
        ]);
    }
}
