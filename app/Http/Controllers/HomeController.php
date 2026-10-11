<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\SizeScale;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $newIn = Product::active()->forCards()
            ->orderByDesc('is_new')->orderByDesc('created_at')
            ->limit(4)->get();

        $bestsellers = Product::active()->forCards()
            ->where('is_featured', true)
            ->whereNotIn('id', $newIn->pluck('id'))
            ->limit(4)->get();

        // The home page always ends on a product row. With nothing flagged as
        // featured (or everything flagged already shown in "New this week"),
        // fall back to the rest of the catalogue rather than dropping the section.
        if ($bestsellers->isEmpty()) {
            $bestsellers = Product::active()->forCards()
                ->whereNotIn('id', $newIn->pluck('id'))
                ->orderByDesc('is_featured')->orderByDesc('created_at')
                ->limit(4)->get();
        }

        $scale = SizeScale::with('options')->where('name', 'Baby age')->first();

        return view('store.home', [
            'newIn' => $newIn,
            'bestsellers' => $bestsellers,
            'sizes' => $scale?->options ?? collect(),
            'tiles' => Category::active()->whereNull('parent_id')->with('department')->orderBy('position')->limit(5)->get(),
            'promos' => Banner::live('home_promo')->get(),
        ]);
    }
}
