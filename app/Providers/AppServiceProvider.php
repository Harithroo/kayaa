<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Color;
use App\Models\Setting;
use App\Models\SizeOption;
use App\Services\CartService;
use App\Services\WishlistService;
use App\Support\SiteMode;
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
        $this->bootSiteMode();

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
     * Site availability (live / coming soon / under maintenance) and the copy
     * shown on each notice. Same pattern as the settings above: whatever the
     * admin saved wins over the config/.env defaults.
     */
    private function bootSiteMode(): void
    {
        $mode = Setting::get('site_mode');

        if ($mode !== null && array_key_exists($mode, SiteMode::LABELS)) {
            config(['kayaa.site_mode' => $mode]);
        }

        // Saved as "1"/"0", so "0" (bypass off) has to beat the config default.
        $bypass = Setting::get('site_mode_admin_bypass');

        if ($bypass !== null) {
            config(['kayaa.site_mode_admin_bypass' => (bool) (int) $bypass]);
        }

        $copy = [
            'coming_soon.heading' => 'coming_soon_heading',
            'coming_soon.message' => 'coming_soon_message',
            'coming_soon.date' => 'coming_soon_date',
            'maintenance.heading' => 'maintenance_heading',
            'maintenance.message' => 'maintenance_message',
            'maintenance.back_at' => 'maintenance_back_at',
        ];

        foreach ($copy as $configKey => $settingKey) {
            $value = Setting::get($settingKey);

            if ($value !== null) {
                config(['kayaa.'.$configKey => $value]);
            }
        }
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

        // Stored as whole rupees in the settings table; config holds cents. "0" is
        // a real value here (free delivery on everything), so compare to null.
        foreach (['shipping_fee', 'free_shipping_over'] as $key) {
            $rupees = Setting::get($key);

            if ($rupees !== null) {
                config(['kayaa.'.$key => (int) $rupees * 100]);
            }
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
