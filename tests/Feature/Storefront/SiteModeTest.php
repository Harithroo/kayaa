<?php

namespace Tests\Feature\Storefront;

use App\Http\Middleware\SiteAvailability;
use App\Models\User;
use App\Support\SiteMode;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

class SiteModeTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_a_live_site_serves_the_storefront(): void
    {
        config(['kayaa.site_mode' => SiteMode::LIVE]);

        $this->get('/')->assertOk()->assertDontSee('Coming soon');
    }

    public function test_coming_soon_replaces_every_storefront_page(): void
    {
        config([
            'kayaa.site_mode' => SiteMode::COMING_SOON,
            'kayaa.coming_soon.heading' => 'Opening very soon',
            'kayaa.coming_soon.date' => '2027-01-15 09:00',
        ]);
        $variant = $this->variant();

        foreach (['/', '/shop', '/cart', '/checkout', '/track', '/account/login', '/products/'.$variant->product->slug] as $path) {
            $response = $this->get($path);

            // A launch page is the site's public face, so it answers 200,
            // but it must never be indexed in place of the real pages.
            $response->assertOk()
                ->assertSee('Opening very soon')
                ->assertSee('Coming soon')
                ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }

        $this->get('/')->assertSee('15 January 2027');
    }

    public function test_maintenance_answers_503_with_a_retry_after(): void
    {
        config([
            'kayaa.site_mode' => SiteMode::MAINTENANCE,
            'kayaa.maintenance.heading' => 'Back in ten minutes',
            'kayaa.maintenance.back_at' => now()->addHour()->toDateTimeString(),
        ]);

        $this->get('/shop')
            ->assertStatus(503)
            ->assertSee('Back in ten minutes')
            ->assertSee('Under maintenance')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertNotNull($this->get('/')->headers->get('Retry-After'));
    }

    public function test_a_notice_does_not_block_ordering_endpoints_that_run_outside_the_browser(): void
    {
        config(['kayaa.site_mode' => SiteMode::MAINTENANCE]);

        // The health check the host polls, and the Onepay callback, which has to
        // keep reconciling payments started just before the notice went up.
        $this->get('/up')->assertOk();
        $this->post('/payment/callback', [])->assertStatus(422);
    }

    public function test_the_admin_panel_stays_reachable_behind_a_notice(): void
    {
        config(['kayaa.site_mode' => SiteMode::MAINTENANCE]);

        $this->get('/admin/login')->assertOk()->assertDontSee('Under maintenance');
    }

    public function test_a_signed_in_admin_sees_the_real_shop_with_a_band(): void
    {
        config(['kayaa.site_mode' => SiteMode::COMING_SOON, 'kayaa.site_mode_admin_bypass' => true]);
        $this->actingAsAdmin();

        $this->get('/')->assertOk()->assertSee('Coming soon mode is on');
    }

    public function test_with_the_bypass_off_an_admin_sees_the_notice_too(): void
    {
        config([
            'kayaa.site_mode' => SiteMode::COMING_SOON,
            'kayaa.site_mode_admin_bypass' => false,
            'kayaa.coming_soon.heading' => 'Opening very soon',
        ]);
        $this->actingAsAdmin();

        $this->get('/')->assertOk()->assertSee('Opening very soon');
    }

    public function test_a_customer_never_bypasses_a_notice(): void
    {
        config([
            'kayaa.site_mode' => SiteMode::COMING_SOON,
            'kayaa.site_mode_admin_bypass' => true,
            'kayaa.coming_soon.heading' => 'Opening very soon',
        ]);
        $this->actingAs($this->customer());

        $this->get('/account')->assertOk()->assertSee('Opening very soon');
    }

    /**
     * The admin bypass asks who is signed in, which only works once the session
     * has started. In the global stack this middleware would run before that and
     * shut admins out of their own shop, so pin where it is registered.
     */
    public function test_the_middleware_runs_inside_the_web_group_not_globally(): void
    {
        $router = $this->app['router'];

        $this->assertContains(SiteAvailability::class, $router->getMiddlewareGroups()['web']);
        $this->assertNotContains(SiteAvailability::class, $this->app[Kernel::class]->getGlobalMiddleware());
    }

    /**
     * The same bypass, but over a real session rather than actingAs(): signing in
     * is what the admin actually does, and it is the path the ordering bug broke.
     */
    public function test_the_bypass_works_for_an_admin_signed_in_through_the_session(): void
    {
        config(['kayaa.site_mode' => SiteMode::LIVE]);
        $admin = $this->adminAccount();

        $this->post('/account/login', ['email' => $admin->email, 'password' => 'secret-pass-123'])
            ->assertRedirect();

        config([
            'kayaa.site_mode' => SiteMode::COMING_SOON,
            'kayaa.site_mode_admin_bypass' => true,
        ]);

        $this->get('/')->assertOk()->assertSee('Coming soon mode is on');
    }

    /** An admin account created the ordinary way, so it can sign in with a password. */
    private function adminAccount(): User
    {
        return User::create([
            'name' => 'Shop Owner',
            'email' => 'owner-session@example.test',
            'password' => 'secret-pass-123',
            'is_admin' => true,
        ]);
    }
}
