<?php

namespace App\Http\Middleware;

use App\Support\SiteMode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the whole storefront behind the coming-soon or under-maintenance notice
 * when the admin has turned one on (Admin > Storefront > Site availability).
 *
 * What still answers while a notice is up:
 *   - /admin and everything it needs (Livewire, Filament assets), so the owner
 *     can turn the notice back off;
 *   - /up, the health check the host polls;
 *   - the Onepay callback and the deploy hook, which are server-to-server and
 *     must keep reconciling payments made just before the notice went up;
 *   - the real storefront for a signed-in admin, unless the bypass is switched
 *     off (so the notice can be checked exactly as a customer sees it).
 */
class SiteAvailability
{
    /** Paths that answer normally whatever the mode is. */
    private const ALWAYS_OPEN = [
        'admin',
        'admin/*',
        'livewire/*',
        'filament/*',
        'up',
        'deploy/*',
        'payment/callback',
        'orders/*/payment/return',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldPass($request)) {
            return $next($request);
        }

        $notice = SiteMode::notice();

        $response = response()
            ->view('store.site-notice', ['notice' => $notice], $this->status($notice['mode']))
            // A placeholder must never be cached as if it were the shop, and
            // never indexed in place of the real pages.
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');

        if ($notice['date'] && $notice['date']->isFuture()) {
            $response->header('Retry-After', (string) $notice['date']->toRfc7231String());
        }

        return $response;
    }

    private function shouldPass(Request $request): bool
    {
        return SiteMode::isLive()
            || $request->is(...self::ALWAYS_OPEN)
            || SiteMode::viewerBypasses();
    }

    /**
     * Maintenance is a 503 so crawlers and monitors read it as temporary.
     * A coming-soon page is the site's real public face before launch, so it
     * answers 200 and carries the noindex header instead.
     */
    private function status(string $mode): int
    {
        return $mode === SiteMode::MAINTENANCE ? 503 : 200;
    }
}
