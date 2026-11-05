<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs migrations after a deploy, for hosts without SSH.
 *
 * The FTP deploy can only copy files, so schema changes used to be applied by
 * hand and were easy to forget — a missed migration 500s every page that reads
 * the new tables. The deploy workflow calls this instead.
 *
 * Guarded by DEPLOY_TOKEN in the server .env, sent as the X-Deploy-Token
 * header. With no token configured the route behaves as if it does not exist,
 * so an unconfigured server never exposes it.
 */
class DeployController extends Controller
{
    /**
     * The non-default extensions this app needs at runtime, as required in
     * composer.json. Keep the two lists in step.
     *
     * @var list<string>
     */
    private const REQUIRED_EXTENSIONS = ['intl'];

    public function __invoke(Request $request): Response
    {
        $expected = (string) config('kayaa.deploy_token');
        $given = (string) $request->header('X-Deploy-Token', '');

        // 404 rather than 403: a wrong token should not confirm the route exists.
        if ($expected === '' || $given === '' || ! hash_equals($expected, $given)) {
            abort(404);
        }

        // view:cache compiles every Blade file; the default limit is tight for that.
        @set_time_limit(300);

        $log = [];

        // composer.json requires these, but `composer install` runs on the GitHub
        // runner, so its check never sees this server. The FTP sync then copies a
        // vendor/ that needs an extension the host does not load, and the failure
        // only shows up as a 500 on the pages that happen to use it — a missing
        // intl took out every Filament table page while the dashboard stayed up,
        // because Number::format() renders the row count. Say so here instead.
        $missing = array_values(array_filter(
            self::REQUIRED_EXTENSIONS,
            fn (string $extension): bool => ! extension_loaded($extension),
        ));

        if ($missing !== []) {
            Log::error('Deploy aborted: missing PHP extensions', ['missing' => $missing]);

            return response(
                'FAILED: this server is missing the PHP extension(s): '
                .implode(', ', $missing)
                ."\nEnable them for PHP ".PHP_VERSION
                ." (cPanel > Select PHP Version > Extensions), then re-run the deploy.\n",
                500,
            )->header('Content-Type', 'text/plain');
        }

        $log[] = 'PHP '.PHP_VERSION.' — required extensions present: '
            .implode(', ', self::REQUIRED_EXTENSIONS);

        try {
            foreach ([
                'migrate' => ['--force' => true],
                'config:cache' => [],
                'route:cache' => [],
                'view:cache' => [],
            ] as $command => $options) {
                Artisan::call($command, $options);
                $log[] = '$ php artisan '.$command."\n".trim(Artisan::output());
            }
        } catch (Throwable $e) {
            Log::error('Deploy migrate failed', ['exception' => $e]);

            return response(
                implode("\n\n", $log)."\n\nFAILED: ".$e->getMessage()."\n",
                500,
            )->header('Content-Type', 'text/plain');
        }

        return response(implode("\n\n", $log)."\n\nOK\n")
            ->header('Content-Type', 'text/plain');
    }
}
