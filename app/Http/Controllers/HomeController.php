<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\SizeOption;
use App\Models\SizeScale;
use App\Services\Seo\JsonLd;
use App\Services\Seo\SeoMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The home page, from the prototype's html/index.html (frontend branch). The prototype
 * wrote its data into the page with tools/catalogue.mjs (the `gen: home-*` and
 * `product-grid` regions); here the same data comes from the real records, and the
 * scripts in resources/js/store/home only draw and move it.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        // "New this week": the products flagged new, newest first, topped up with the
        // newest of the rest so the tab is never short.
        $newIn = Product::active()->forCards()
            ->orderByDesc('is_new')->orderByDesc('created_at')
            ->limit(4)->get();

        // "Bestsellers": the products with the admin Featured toggle on (the prototype's
        // `featured:4`). The two lists are tabs, so a product may be in both. The tab
        // is left out when nothing is featured.
        $bestsellers = Product::active()->forCards()
            ->where('is_featured', true)
            ->orderBy('id')
            ->limit(4)->get();

        $sizes = SizeScale::with('options')->where('name', 'Baby age')->first()?->options ?? collect();

        return view('store.home', [
            'newIn' => $newIn,
            'bestsellers' => $bestsellers,
            'sizes' => $sizes,
            'ages' => $this->ages($sizes),
            'rail' => $this->rail(),
            'fabrics' => $this->fabrics(),
            'marquee' => array_values(array_filter((array) config('kayaa.home.marquee', []))),
            'promos' => Banner::live('home_promo')->get(),
            'reviews' => $this->reviews(),
            'meta' => SeoMeta::forPage('home', $request),
            // Organization and WebSite belong on one page only, and this is it.
            'jsonLd' => [JsonLd::organization(), JsonLd::webSite()],
        ]);
    }

    /**
     * Shop by age: each size with its long name, its shop-by-size link, and the height
     * and weight ranges from the size chart (the same records as the size guide). `h`
     * and `w` are the numbers the ruler draws; "Up to X" starts at the ruler's minimum
     * (45 cm, 2 kg), as the prototype's homeAges() does. `g` is the outfit drawn for it.
     */
    private function ages(Collection $sizes): array
    {
        $garments = ['newborn', 'newborn', 'bodysuit', 'bodysuit', 'bodysuit', 'set', 'set', 'set', 'set'];
        $num = fn ($v) => $v === null ? null : (float) $v;

        return $sizes->values()->map(function (SizeOption $s, int $i) use ($garments, $num) {
            [$hMin, $hMax] = [$num($s->min_height_cm), $num($s->max_height_cm)];
            [$wMin, $wMax] = [$num($s->min_weight_kg), $num($s->max_weight_kg)];

            return [
                'slug' => $s->label,
                'label' => $s->label,
                'name' => $s->longLabel(),
                'href' => route('shop.index', ['size' => $s->label]),
                'height' => $s->heightRange() ?? '—',
                'weight' => $s->weightRange() ?? '—',
                'h' => [$hMin ?? 45, $hMax ?? $hMin ?? 45],
                'w' => [$wMin ?? 2, $wMax ?? $wMin ?? 2],
                'g' => $garments[$i] ?? 'set',
            ];
        })->all();
    }

    /**
     * The category rail: one hanger per active top-level category (the same list as the
     * header's category row), with the number of styles and the size range across them
     * ("NB–3Y"), counting the products of its subcategories as the category page does.
     * A category with nothing in it yet still hangs on the rail, as "Coming soon".
     */
    private function rail(): Collection
    {
        $categories = Category::active()->whereNull('parent_id')
            ->with(['department', 'children'])
            ->orderBy('position')->get();

        $ids = $categories->flatMap(fn (Category $c) => [$c->id, ...$c->children->pluck('id')]);
        $products = Product::active()
            ->whereIn('category_id', $ids)
            ->with('variants.sizeOption')
            ->get();

        $drawings = (array) config('kayaa.home.rail', []);

        return $categories->map(function (Category $c) use ($products, $drawings) {
            $own = [$c->id, ...$c->children->pluck('id')];
            $items = $products->whereIn('category_id', $own);
            $sizes = $items->flatMap(fn (Product $p) => $p->sizeOptions())->sortBy('position')->values();
            $from = $sizes->first()?->shortFrom();
            $to = $sizes->last()?->shortTo();
            $range = $from === null ? null : ($from === $to ? $from : "{$from}–{$to}");

            $count = $items->count();
            $styles = $count === 0 ? 'Coming soon' : $count.' '.($count === 1 ? 'style' : 'styles');
            $drawing = $drawings[$c->slug] ?? [];
            $photo = 'assets/img/rail/'.$c->slug.'.webp';

            return (object) [
                'name' => $c->name,
                'url' => $c->url(),
                'count' => $count,
                'range' => $range,
                'styles' => $styles,
                'label' => $c->name.', '.($count === 0 ? 'coming soon' : $styles).($range ? ', sizes '.$range : ''),
                'meta' => $styles.($range ? ' · '.$range : ''),
                'type' => $drawing['type'] ?? 'bodysuit',
                'c' => $drawing['c'] ?? 'white',
                'c2' => $drawing['c2'] ?? null,
                'photo' => is_file(public_path($photo)) ? asset($photo) : null,
            ];
        })->values();
    }

    /**
     * The fabric swatch tabs (config/kayaa.php home.fabrics) and, for each, up to four
     * real products that are made of it: "tag:<slug>" matches a product collection,
     * "name:<word>" a word in the product name. Featured products first.
     */
    private function fabrics(): array
    {
        $fabrics = (array) config('kayaa.home.fabrics', []);
        if (! $fabrics) {
            return [];
        }

        $products = Product::active()
            ->with(['collections' => fn ($q) => $q->where('collections.is_active', true)])
            ->orderByDesc('is_featured')->orderBy('id')
            ->get(['id', 'name', 'slug', 'is_featured']);

        return array_map(function (array $f) use ($products) {
            $f['products'] = $products->filter(function (Product $p) use ($f) {
                foreach ((array) ($f['match'] ?? []) as $rule) {
                    [$kind, $value] = array_pad(explode(':', $rule, 2), 2, '');
                    $value = trim($value);
                    if ($value === '') {
                        continue;
                    }
                    if ($kind === 'tag' && $p->collections->contains('slug', $value)) {
                        return true;
                    }
                    if ($kind === 'name' && str_contains(mb_strtolower($p->name), mb_strtolower($value))) {
                        return true;
                    }
                }

                return false;
            })->take(4)->values();

            return $f;
        }, $fabrics);
    }

    /**
     * The home reviews feed: approved, verified-purchase reviews rated 4 or more, the
     * latest three (one row of the design). The section is left out when there are none.
     */
    private function reviews(): Collection
    {
        return Review::approved()
            ->where('is_verified_purchase', true)
            ->where('rating', '>=', 4)
            ->whereHas('product', fn ($q) => $q->active())
            ->with(['product', 'user'])
            ->latest()->limit(3)->get();
    }
}
