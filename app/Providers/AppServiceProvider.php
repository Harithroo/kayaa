<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Color;
use App\Models\Setting;
use App\Models\SizeOption;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CartService::class);
        $this->app->scoped(WishlistService::class);
    }

    public function boot(): void
    {
        $this->bootContactSettings();
        $this->bootStoreSettings();

        // Signing in (or registering) folds whatever was saved as a guest into the account,
        // so the cart and wishlist carry over instead of disappearing. Fires for remember-me
        // logins too. The event runs before Auth::user() is set, hence the explicit $user.
        Event::listen(Login::class, function (Login $event) {
            if (! $event->user instanceof \App\Models\User) {
                return;
            }
            app(CartService::class)->mergeGuestCart($event->user);
            app(WishlistService::class)->mergeGuestWishlist($event->user);
        });

        // Every storefront view gets the nav categories, cart and wishlist counts.
        // The store pages are composed too, not just the shell: several of them
        // (the empty bag, the search page) use the same size and category lists.
        View::composer(['components.layouts.store', 'store.*'], function ($view) {
            $view->with('navCategories', Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('position')
                ->get());
            $view->with('cart', app(CartService::class));
            $view->with('cartCount', app(CartService::class)->count());
            $view->with('wishCount', app(WishlistService::class)->count());
            $view->with('topbar', Banner::live('topbar')->first());

            // The shell's "Shop by size" chips (header mega menu, menu drawer, footer),
            // and the size and colour labels the quick-add sheet fills itself from.
            $view->with('navSizes', SizeOption::query()
                ->orderBy('size_scale_id')
                ->orderBy('position')
                ->get());
            $view->with('navColors', Color::query()->orderBy('name')->get());
            $view->with('popularSearches', []);
        });
    }

    /**
     * Admin-managed store numbers win over the config/.env defaults, same as
     * the contact details above.
     */
    private function bootStoreSettings(): void
    {
        $threshold = Setting::get('low_stock_threshold');

        if ($threshold !== null) {
            config(['kayaa.low_stock_threshold' => (int) $threshold]);
        }
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
