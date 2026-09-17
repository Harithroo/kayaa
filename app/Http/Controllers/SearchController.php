<?php

namespace App\Http\Controllers;

use App\Models\Product;
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
            $results = Product::active()->forCards()
                ->where(fn ($w) => $w
                    ->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like))
                    ->orWhereHas('variants.color', fn ($c) => $c->where('name', 'like', $like)))
                ->limit(48)->get();
        }

        return view('store.search', compact('q', 'results'));
    }
}
