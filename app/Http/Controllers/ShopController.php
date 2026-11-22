<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Product;
use App\Models\SizeOption;
use App\Services\Seo\JsonLd;
use App\Services\Seo\SeoMeta;
use App\Support\ShopFilters;
use App\Support\Seo\Breadcrumbs;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    /** /shop — everything, optionally ?size=0-3m&sort=new */
    public function index(Request $request): View
    {
        return $this->listing($request, null, null);
    }

    /** /{department}/{category} — e.g. /baby/bodysuits */
    public function category(Request $request, Department $department, string $categorySlug): View
    {
        $category = Category::active()
            ->where('department_id', $department->id)
            ->where('slug', $categorySlug)
            ->firstOrFail();

        return $this->listing($request, $department, $category);
    }

    private function listing(Request $request, ?Department $department, ?Category $category): View
    {
        // The listing's own scope: a category (with its subcategories), a department or everything.
        $scope = function () use ($category, $department) {
            $q = Product::active();
            if ($category) {
                $q->whereIn('category_id', [$category->id, ...$category->children->pluck('id')]);
            } elseif ($department) {
                $q->whereHas('category', fn ($c) => $c->where('department_id', $department->id));
            }

            return $q;
        };

        // The category filter is for /shop: a category page has its subcategory chips instead.
        $topCategories = $category
            ? collect()
            : Category::active()->whereNull('parent_id')->with(['department', 'children'])->orderBy('position')->get();

        $filters = new ShopFilters($request, $topCategories);
        $query = $filters->apply($scope())->with(['images', 'variants.sizeOption', 'variants.color']);

        $sort = $request->query('sort');
        match ($sort) {
            'price_asc' => $query->orderBy(fn ($q) => $q->selectRaw('min(price)')->from('product_variants')->whereColumn('product_id', 'products.id')->where('is_active', true)),
            'price_desc' => $query->orderByDesc(fn ($q) => $q->selectRaw('min(price)')->from('product_variants')->whereColumn('product_id', 'products.id')->where('is_active', true)),
            'new' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('is_featured')->orderByDesc('is_new')->orderByDesc('created_at'),
        };
        $query->orderBy('products.id');   // a stable order, so pages never repeat or skip a product

        $products = $query->paginate(24)->withQueryString();

        // Size chips come from the scale in use. With one department that is always "Baby age".
        $sizes = SizeOption::whereHas('scale', fn ($q) => $q->where('name', 'Baby age'))->orderBy('position')->get();

        $facets = $filters->facets($scope, $sizes, ! $category);
        $base = $category ? $category->url() : route('shop.index');
        $chips = $filters->chips($request, $base, $facets['colours']->pluck('label', 'value'));

        $sale = $filters->sale;
        $collection = $filters->tag;
        $sizeLabel = count($filters->sizes) === 1 ? $filters->sizes[0] : null;

        // A real category gets its own metadata; /shop and the collection and
        // sale views fall back to the config page map. Either way the canonical
        // drops ?size=/?sort=/?tag=/?sale= and the robots tag turns to
        // noindex,follow the moment one of them is present — that single rule is
        // what stops this listing minting thousands of thin duplicate URLs.
        $meta = $category
            ? SeoMeta::forCategory($category, $request)
            : SeoMeta::forPage($sale ? 'sale' : 'shop', $request);

        $breadcrumbs = $category
            ? Breadcrumbs::forCategory($category)
            : Breadcrumbs::forListing($collection, $sale);

        return view('store.shop', [
            'department' => $department,
            'category' => $category,
            'products' => $products,
            'sizes' => $sizes,
            'activeSize' => $sizeLabel,
            'filters' => $filters,
            'facets' => $facets,
            'chips' => $chips,
            'base' => $base,
            'collection' => $collection,
            'title' => $category?->name ?? $collection?->name ?? ($sale ? 'Sale' : 'All products'),
            'meta' => $meta,
            'breadcrumbs' => $breadcrumbs,
            'jsonLd' => JsonLd::breadcrumbs($breadcrumbs),
        ]);
    }
}
