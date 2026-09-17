<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Product;
use App\Models\SizeOption;
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
        $query = Product::active()->forCards();

        if ($category) {
            $ids = [$category->id, ...$category->children->pluck('id')];
            $query->whereIn('category_id', $ids);
        } elseif ($department) {
            $query->whereHas('category', fn ($q) => $q->where('department_id', $department->id));
        }

        $sizeLabel = $request->query('size');
        $size = $sizeLabel ? SizeOption::where('label', $sizeLabel)->first() : null;
        if ($size) {
            $query->whereHas('variants', fn ($q) => $q
                ->where('size_option_id', $size->id)
                ->where('is_active', true)
                ->where('stock', '>', 0));
        }

        if ($request->query('sale')) {
            $query->whereHas('variants', fn ($q) => $q->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price'));
        }

        match ($request->query('sort')) {
            'price_asc' => $query->orderBy(fn ($q) => $q->selectRaw('min(price)')->from('product_variants')->whereColumn('product_id', 'products.id')),
            'price_desc' => $query->orderByDesc(fn ($q) => $q->selectRaw('min(price)')->from('product_variants')->whereColumn('product_id', 'products.id')),
            'new' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('is_featured')->orderByDesc('is_new')->orderByDesc('created_at'),
        };

        $products = $query->paginate(24)->withQueryString();

        // Size chips come from the scale in use. With one department that is always "Baby age".
        $sizes = SizeOption::whereHas('scale', fn ($q) => $q->where('name', 'Baby age'))->orderBy('position')->get();

        return view('store.shop', [
            'department' => $department,
            'category' => $category,
            'products' => $products,
            'sizes' => $sizes,
            'activeSize' => $sizeLabel,
            'title' => $category?->name ?? ($request->query('sale') ? 'Sale' : 'All products'),
        ]);
    }
}
