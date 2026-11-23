<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use App\Http\Middleware\NoIndexAdmin;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            // config/kayaa.php admin_path (ADMIN_PATH), "kdash" unless set.
            ->path(config('kayaa.admin_path') ?: 'kdash')
            ->login()
            // Adds /<admin_path>/profile: the signed-in admin can change their name,
            // email and password (the current password is required to do it).
            ->profile(isSimple: false)
            ->brandName('Kayaa')
            // Solid-colour version of the footer wordmark (the footer one is white).
            ->brandLogo(fn () => asset('assets/img/admin/logo.svg'))
            ->brandLogoHeight('1.75rem')
            ->favicon(fn () => asset('favicon.svg'))
            // Same Deep Wisteria the storefront uses for buttons and links (--primary-deep).
            ->colors([
                'primary' => Color::hex('#6E5A8C'),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->unsavedChangesAlerts()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce('300ms')
            // Menu order: what is done daily first, set-and-forget last.
            ->navigationGroups([
                NavigationGroup::make('Sales')->icon(Heroicon::OutlinedBanknotes),
                NavigationGroup::make('Catalog')->icon(Heroicon::OutlinedTag),
                NavigationGroup::make('Engagement')->icon(Heroicon::OutlinedChatBubbleLeftRight),
                NavigationGroup::make('Storefront')->icon(Heroicon::OutlinedBuildingStorefront),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            // The dashboard is App\Filament\Pages\Dashboard (discovered above), at
            // /<admin_path>/overview; the panel root redirects there.
            // The dashboard widgets (stats, chart, latest orders, low stock, top
            // sellers) live in app/Filament/Widgets and are ordered by their $sort.
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // Nothing in the panel, the login page included, belongs in a search index.
                NoIndexAdmin::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
