<?php

namespace App\Support\Seo;

use App\Models\Category;
use App\Models\Collection as ProductCollection;
use App\Models\Product;

/**
 * Breadcrumb trails, built once and used twice: the visible <nav> renders from
 * the same array the BreadcrumbList markup is generated from.
 *
 * That is deliberate. Marking up a trail that differs from the one on the page
 * is exactly the kind of mismatch Google treats as misleading structured data,
 * and keeping two hand-written copies in step is a losing game.
 *
 * Each entry is ['name' => string, 'url' => ?string]. The last entry has a null
 * url: it is the page you are on.
 */
final class Breadcrumbs
{
    /** Home > Department > Category > Product, matching the product page. */
    public static function forProduct(Product $product): array
    {
        $trail = [['name' => 'Home', 'url' => route('home')]];

        $category = $product->category;

        // The department has no listing page of its own, so it points at the
        // category index — a real page, which an intermediate crumb must be.
        $trail[] = [
            'name' => $category?->department?->name ?? 'Shop',
            'url' => route('categories.index'),
        ];

        if ($category) {
            $trail[] = ['name' => $category->name, 'url' => $category->url()];
        }

        $trail[] = ['name' => $product->name, 'url' => null];

        return $trail;
    }

    /** Home > Shop > [parent] > Category, matching the listing page. */
    public static function forCategory(Category $category): array
    {
        return [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Shop', 'url' => route('shop.index')],
            ...($category->parent
                ? [['name' => $category->parent->name, 'url' => $category->parent->url()]]
                : []),
            ['name' => $category->name, 'url' => null],
        ];
    }

    /** The unfiltered listing, a collection page, or Sale. */
    public static function forListing(?ProductCollection $collection = null, bool $sale = false): array
    {
        return [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => $collection?->name ?? ($sale ? 'Sale' : 'Shop'), 'url' => null],
        ];
    }

    /** Home > <page>, for the written pages. */
    public static function forPage(string $name): array
    {
        return [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => $name, 'url' => null],
        ];
    }
}
