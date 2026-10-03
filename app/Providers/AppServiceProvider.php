<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Setting;
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
        View::composer('components.layouts.store', function ($view) {
            $view->with('navCategories', Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('position')
                ->get());
            $view->with('cart', app(CartService::class));
            $view->with('cartCount', app(CartService::class)->count());
            $view->with('wishCount', app(WishlistService::class)->count());
            $view->with('topbar', Banner::live('topbar')->first());
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
