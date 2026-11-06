<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\SiteAvailability;
use App\Models\Setting;
use App\Providers\AppServiceProvider;
use App\Support\SiteMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

class SiteAvailabilityTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    private function boot(): void
    {
        (new ReflectionMethod(AppServiceProvider::class, 'bootSiteMode'))
            ->invoke(new AppServiceProvider($this->app));
    }

    public function test_the_page_saves_the_mode_and_both_notices(): void
    {
        $this->actingAsAdmin();

        Livewire::test(SiteAvailability::class)
            ->fillForm([
                'site_mode' => SiteMode::COMING_SOON,
                'site_mode_admin_bypass' => false,
                'coming_soon_heading' => 'Opening very soon',
                'coming_soon_message' => 'Sewing the last seams.',
                'coming_soon_date' => '2027-01-15 09:00',
                'maintenance_heading' => 'Back in ten minutes',
                'maintenance_message' => 'Carts are safe.',
                'maintenance_back_at' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(SiteMode::COMING_SOON, Setting::get('site_mode'));
        $this->assertSame('0', Setting::get('site_mode_admin_bypass'));
        $this->assertSame('Opening very soon', Setting::get('coming_soon_heading'));
        $this->assertSame('Back in ten minutes', Setting::get('maintenance_heading'));
        $this->assertNull(Setting::get('maintenance_back_at'));
    }

    public function test_the_page_requires_a_mode_and_both_headings(): void
    {
        $this->actingAsAdmin();

        Livewire::test(SiteAvailability::class)
            ->fillForm(['site_mode' => null, 'coming_soon_heading' => '', 'maintenance_heading' => ''])
            ->call('save')
            ->assertHasFormErrors(['site_mode', 'coming_soon_heading', 'maintenance_heading']);
    }

    public function test_saved_settings_override_the_config_defaults_at_boot(): void
    {
        config(['kayaa.site_mode' => SiteMode::LIVE, 'kayaa.site_mode_admin_bypass' => true]);
        Setting::putMany([
            'site_mode' => SiteMode::MAINTENANCE,
            'site_mode_admin_bypass' => '0',
            'maintenance_heading' => 'Short update',
        ]);

        $this->boot();

        $this->assertSame(SiteMode::MAINTENANCE, SiteMode::current());
        // "0" is a real value (show me what a customer sees), not "unset".
        $this->assertFalse(SiteMode::adminBypassEnabled());
        $this->assertSame('Short update', config('kayaa.maintenance.heading'));
    }

    public function test_an_unknown_stored_mode_falls_back_to_live(): void
    {
        config(['kayaa.site_mode' => SiteMode::LIVE]);
        Setting::putMany(['site_mode' => 'nonsense']);

        $this->boot();

        $this->assertTrue(SiteMode::isLive());
    }
}
