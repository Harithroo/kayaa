<?php

namespace App\Providers;

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
            $view->with('navCategories', \App\Models\Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('position')
                ->get());
            $view->with('cartCount', app(CartService::class)->count());
            $view->with('topbar', \App\Models\Banner::live('topbar')->first());
        });
    }
}
