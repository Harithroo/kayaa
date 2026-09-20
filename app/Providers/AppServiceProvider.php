<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Setting;
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
        $this->bootContactSettings();

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

    /**
     * Admin-managed contact details win over the config/.env defaults, so the
     * storefront can keep reading config('kayaa.*') everywhere.
     */
    private function bootContactSettings(): void
    {
        foreach (['email', 'phone', 'whatsapp'] as $key) {
            $value = Setting::get('contact_'.$key);

            if ($value !== null) {
                config(['kayaa.'.$key => $value]);
            }
        }
    }
}
