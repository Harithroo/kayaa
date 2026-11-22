<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Color;
use App\Models\Collection as Tag;
use App\Models\Product;
use App\Models\SizeOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The shop's filters, read from the query string and applied to a product query.
 *
 *   ?category[]=bodysuits   top-level categories (their subcategories included); /shop only
 *   ?size[]=0–3m            size labels; a plain ?size=0–3m (the links across the site) works too
 *   ?colour[]=butter        colour slugs
 *   ?min=1000&max=4000      price in rupees, against any active variant's price
 *   ?stock=1                at least one variant in stock
 *   ?new=1                  flagged new
 *   ?sale=1                 a variant priced below its compare-at price
 *   ?tag=gift-idea          a collection (the product page's tag chips)
 *
 * Within one filter the choices are alternatives (Butter OR Sage); across filters
 * they all apply. A size only counts when that size is in stock, as before.
 */
class ShopFilters
{
    /** Every parameter that narrows the list (SeoMeta::FILTER_PARAMS lists the same, plus sort). */
    public const PARAMS = ['category', 'size', 'colour', 'min', 'max', 'stock', 'new', 'sale', 'tag'];

    public array $categories;   // slugs

    public array $sizes;        // labels

    public array $colours;      // slugs

    public ?int $min;           // rupees

    public ?int $max;           // rupees

    public bool $stock;

    public bool $new;

    public bool $sale;

    public ?Tag $tag;

    /** @var Collection<int, Category> the top-level categories, with their children */
    private Collection $categoryModels;

    public function __construct(Request $request, Collection $categories)
    {
        $this->categoryModels = $categories;
        $this->categories = $this->list($request, 'category');
        $this->sizes = $this->list($request, 'size');
        $this->colours = $this->list($request, 'colour');
        $this->min = $this->rupees($request->query('min'));
        $this->max = $this->rupees($request->query('max'));
        if ($this->min !== null && $this->max !== null && $this->min > $this->max) {
            [$this->min, $this->max] = [$this->max, $this->min];
        }
        $this->stock = (bool) $request->query('stock');
        $this->new = (bool) $request->query('new');
        $this->sale = (bool) $request->query('sale');
        $tag = $request->query('tag');
        $this->tag = is_string($tag) && $tag !== '' ? Tag::where('slug', $tag)->where('is_active', true)->first() : null;
    }

    /** How many filters are on (each ticked option counts once; a price range counts once). */
    public function count(): int
    {
        return count($this->categories) + count($this->sizes) + count($this->colours)
            + ($this->min !== null || $this->max !== null ? 1 : 0)
            + (int) $this->stock + (int) $this->new + (int) $this->sale + ($this->tag ? 1 : 0);
    }

    /**
     * Narrow $q with every filter except $except (a facet's counts leave out its own
     * filter, so the other options of that facet still show what they would add).
     */
    public function apply(Builder $q, ?string $except = null): Builder
    {
        if ($except !== 'category' && $this->categories) {
            $q->whereIn('category_id', $this->categoryIds($this->categories));
        }

        if ($except !== 'size' && $this->sizes) {
            $q->whereHas('variants', fn ($v) => $v->where('is_active', true)->where('stock', '>', 0)
                ->whereHas('sizeOption', fn ($s) => $s->whereIn('label', $this->sizes)));
        }

        if ($except !== 'colour' && $this->colours) {
            $q->whereHas('variants', fn ($v) => $v->where('is_active', true)
                ->whereHas('color', fn ($c) => $c->whereIn('slug', $this->colours)));
        }

        if ($except !== 'price' && ($this->min !== null || $this->max !== null)) {
            $q->whereHas('variants', function ($v) {
                $v->where('is_active', true);
                if ($this->min !== null) {
                    $v->where('price', '>=', $this->min * 100);
                }
                if ($this->max !== null) {
                    $v->where('price', '<=', $this->max * 100);
                }
            });
        }

        if ($except !== 'stock' && $this->stock) {
            $q->whereHas('variants', fn ($v) => $v->where('is_active', true)->where('stock', '>', 0));
        }

        if ($except !== 'new' && $this->new) {
            $q->where('is_new', true);
        }

        if ($except !== 'sale' && $this->sale) {
            $q->whereHas('variants', fn ($v) => $v->where('is_active', true)
                ->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price'));
        }

        if ($except !== 'tag' && $this->tag) {
            $q->whereHas('collections', fn ($c) => $c->whereKey($this->tag->id));
        }

        return $q;
    }

    /**
     * The options of each facet with the number of products each would show. $scope
     * builds a fresh query of the listing's own scope (the category page, or the shop).
     *
     * @param  \Closure(): Builder  $scope
     */
    public function facets(\Closure $scope, Collection $sizeOptions, bool $withCategories): array
    {
        $ids = fn (string $except) => $this->apply($scope(), $except)->select('products.id');

        $categories = collect();
        if ($withCategories) {
            $byCategory = $this->apply($scope(), 'category')
                ->select('category_id', DB::raw('count(*) as n'))->groupBy('category_id')
                ->pluck('n', 'category_id');
            $categories = $this->categoryModels->map(fn (Category $c) => (object) [
                'value' => $c->slug,
                'label' => $c->name,
                'count' => (int) collect([$c->id, ...$c->children->pluck('id')])->sum(fn ($id) => $byCategory[$id] ?? 0),
                'checked' => in_array($c->slug, $this->categories, true),
            ]);
        }

        $bySize = DB::table('product_variants')
            ->whereIn('product_id', $ids('size'))
            ->where('is_active', true)->where('stock', '>', 0)
            ->select('size_option_id', DB::raw('count(distinct product_id) as n'))->groupBy('size_option_id')
            ->pluck('n', 'size_option_id');
        $sizes = $sizeOptions->map(fn (SizeOption $s) => (object) [
            'value' => $s->label,
            'label' => $s->label,
            'count' => (int) ($bySize[$s->id] ?? 0),
            'checked' => in_array($s->label, $this->sizes, true),
        ]);

        $byColour = DB::table('product_variants')
            ->whereIn('product_id', $ids('colour'))
            ->where('is_active', true)
            ->select('color_id', DB::raw('count(distinct product_id) as n'))->groupBy('color_id')
            ->pluck('n', 'color_id');
        // Only colours something is made in; a colour with no products in this scope is noise.
        $colours = Color::orderBy('name')->get()
            ->map(fn (Color $c) => (object) [
                'value' => $c->slug,
                'label' => $c->name,
                'hex' => $c->hex,
                'count' => (int) ($byColour[$c->id] ?? 0),
                'checked' => in_array($c->slug, $this->colours, true),
            ])
            ->filter(fn ($c) => $c->count > 0 || $c->checked)->values();

        // The price bounds of the whole scope, so the placeholders show what is possible.
        $prices = DB::table('product_variants')
            ->whereIn('product_id', $scope()->select('products.id'))
            ->where('is_active', true)
            ->selectRaw('min(price) as lo, max(price) as hi')->first();

        $flag = fn (string $except, \Closure $where) => $this->apply($scope(), $except)->where($where)->count();

        return [
            'categories' => $categories,
            'sizes' => $sizes,
            'colours' => $colours,
            'price' => (object) [
                'lo' => $prices?->lo !== null ? (int) floor($prices->lo / 100) : null,
                'hi' => $prices?->hi !== null ? (int) ceil($prices->hi / 100) : null,
            ],
            'stock' => $flag('stock', fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('is_active', true)->where('stock', '>', 0))),
            'new' => $flag('new', fn ($q) => $q->where('is_new', true)),
            'sale' => $flag('sale', fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('is_active', true)
                ->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price'))),
        ];
    }

    /**
     * One chip per filter that is on, each with the link that removes it.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function chips(Request $request, string $base, Collection $colourNames): array
    {
        $query = $request->query();
        unset($query['page']);
        $without = function (string $key, ?string $value = null) use ($query, $base) {
            $q = $query;
            if ($value === null) {
                unset($q[$key]);
            } else {
                $q[$key] = array_values(array_diff((array) ($q[$key] ?? []), [$value]));
            }

            return $base.(($q = array_filter($q, fn ($v) => $v !== [] && $v !== null && $v !== '')) ? '?'.http_build_query($q) : '');
        };

        $chips = [];
        foreach ($this->categories as $slug) {
            $chips[] = ['label' => $this->categoryModels->firstWhere('slug', $slug)?->name ?? $slug, 'url' => $without('category', $slug)];
        }
        foreach ($this->sizes as $label) {
            $chips[] = ['label' => 'Size '.$label, 'url' => is_array($query['size'] ?? null) ? $without('size', $label) : $without('size')];
        }
        foreach ($this->colours as $slug) {
            $chips[] = ['label' => $colourNames[$slug] ?? $slug, 'url' => $without('colour', $slug)];
        }
        if ($this->min !== null || $this->max !== null) {
            $label = match (true) {
                $this->min !== null && $this->max !== null => money($this->min * 100).' – '.money($this->max * 100),
                $this->min !== null => 'From '.money($this->min * 100),
                default => 'Up to '.money($this->max * 100),
            };
            $q = $query;
            unset($q['min'], $q['max']);
            $chips[] = ['label' => $label, 'url' => $base.($q ? '?'.http_build_query($q) : '')];
        }
        if ($this->stock) {
            $chips[] = ['label' => 'In stock', 'url' => $without('stock')];
        }
        if ($this->new) {
            $chips[] = ['label' => 'New arrivals', 'url' => $without('new')];
        }
        if ($this->sale) {
            $chips[] = ['label' => 'On sale', 'url' => $without('sale')];
        }
        if ($this->tag) {
            $chips[] = ['label' => $this->tag->name, 'url' => $without('tag')];
        }

        return $chips;
    }

    /** The ids of the given top-level categories and their subcategories. */
    private function categoryIds(array $slugs): array
    {
        return $this->categoryModels->whereIn('slug', $slugs)
            ->flatMap(fn (Category $c) => [$c->id, ...$c->children->pluck('id')])
            ->all();
    }

    /** A query value as a list of non-empty strings: ?size=a and ?size[]=a&size[]=b both work. */
    private function list(Request $request, string $key): array
    {
        $value = $request->query($key);

        return array_values(array_unique(array_filter(
            array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : '', (array) $value),
            fn ($v) => $v !== '',
        )));
    }

    private function rupees(mixed $value): ?int
    {
        if (! is_scalar($value) || ! is_numeric($digits = preg_replace('/[^\d.]/', '', (string) $value))) {
            return null;
        }

        return max(0, (int) round((float) $digits));
    }
}
