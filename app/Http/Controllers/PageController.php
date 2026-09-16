<?php

namespace App\Http\Controllers;

use App\Models\SizeScale;
use Illuminate\View\View;

class PageController extends Controller
{
    public function sizeGuide(): View
    {
        return view('store.pages.size-guide', [
            'scale' => SizeScale::with('options')->where('name', 'Baby age')->first(),
        ]);
    }

    public function show(string $page): View
    {
        abort_unless(in_array($page, ['delivery', 'returns', 'about', 'privacy', 'terms']), 404);

        return view("store.pages.$page");
    }
}
