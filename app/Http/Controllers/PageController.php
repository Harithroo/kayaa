<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\SizeScale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function sizeGuide(): View
    {
        return view('store.pages.size-guide', [
            'scale' => SizeScale::with('options')->where('name', 'Baby age')->first(),
        ]);
    }

    public function contact(): View
    {
        return view('store.pages.contact', [
            'faqs' => Faq::active()->forContact()->get(),
        ]);
    }

    public function sendMessage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'contact' => ['required', 'string', 'max:120'],
            'order_reference' => ['nullable', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        ContactMessage::create($data);

        return redirect()->route('pages.contact')->with('success', 'Message sent. We reply within a working day.');
    }

    public function show(string $page): View
    {
        abort_unless(in_array($page, ['delivery', 'returns', 'about', 'privacy', 'terms']), 404);

        return view("store.pages.$page");
    }
}
