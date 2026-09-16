<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SizeScale;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $newIn = Product::active()->forCards()
            ->orderByDesc('is_new')->orderByDesc('created_at')
            ->limit(8)->get();

        $scale = SizeScale::with('options')->where('name', 'Baby age')->first();

        return view('store.home', [
            'newIn' => $newIn,
            'sizes' => $scale?->options ?? collect(),
        ]);
    }
}
