<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Services\CartService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CartService::class);
    }

    public function boot(): void
    {
        // Every storefront view gets the nav categories and the cart count.
        View::composer('components.layouts.store', function ($view) {
            $view->with('navCategories', Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('position')
                ->get());
            $view->with('cart', app(CartService::class));
            $view->with('cartCount', app(CartService::class)->count());
            $view->with('topbar', Banner::live('topbar')->first());
        });
    }
}
