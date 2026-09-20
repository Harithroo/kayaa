<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Product;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /** /categories — every active category, grouped by department, with live product counts. */
    public function index(): View
    {
        $departments = Department::where('is_active', true)
            ->orderBy('position')
            ->with(['categories' => fn ($q) => $q->where('is_active', true)->whereNull('parent_id')
                ->with(['children' => fn ($c) => $c->where('is_active', true)])])
            ->get();

        // One query for all counts: active products per category id.
        $counts = Product::active()
            ->selectRaw('category_id, count(*) as n')
            ->groupBy('category_id')
            ->pluck('n', 'category_id');

        foreach ($departments as $department) {
            foreach ($department->categories as $category) {
                $category->setRelation('department', $department);
                $category->children->each(fn ($child) => $child->setRelation('department', $department));
                $category->product_count = $counts->get($category->id, 0)
                    + $category->children->sum(fn ($child) => $counts->get($child->id, 0));
            }
        }

        return view('store.categories', ['departments' => $departments]);
    }
}
