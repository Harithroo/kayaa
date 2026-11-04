<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\SizeScale;
use App\Services\Seo\JsonLd;
use App\Services\Seo\SeoMeta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function sizeGuide(Request $request): View
    {
        return view('store.pages.size-guide', [
            'scale' => SizeScale::with('options')->where('name', 'Baby age')->first(),
            'meta' => SeoMeta::forPage('size-guide', $request),
        ]);
    }

    public function contact(Request $request): View
    {
        $faqs = Faq::active()->forContact()->get();

        return view('store.pages.contact', [
            'faqs' => $faqs,
            'meta' => SeoMeta::forPage('contact', $request),
            // Built from the same collection the page renders, so the markup
            // cannot describe questions a visitor cannot see.
            'jsonLd' => JsonLd::faqPage($faqs),
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

    public function show(Request $request, string $page): View
    {
        abort_unless(in_array($page, ['delivery', 'returns', 'about', 'privacy', 'terms']), 404);

        return view("store.pages.$page", [
            'meta' => SeoMeta::forPage($page, $request),
        ]);
    }
}
