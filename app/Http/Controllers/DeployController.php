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

    /**
     * Which PHP actually served this request, and where it reads its config.
     *
     * cPanel can offer several builds of the same version (ea-php84 and
     * alt-php84, say) with separate extension lists, and a domain can be
     * pinned to one by MultiPHP Manager or an AddHandler line in .htaccess.
     * When "Select PHP Version" shows an extension ticked but the request
     * still cannot load it, the two are not the same build — the binary path
     * and the ini files below name the one that counts. That is exactly how
     * the missing intl hid for a while: the site runs ea-php84, while the
     * Selector was enabling intl on alt-php84. A stray php.ini or .user.ini
     * in the account can also drop an extension on its own, which is why the
     * scanned files are listed and not just the loaded one.
     */
    private static function phpEnvironmentReport(): string
    {
        $scanned = trim((string) php_ini_scanned_files());

        $lines = [
            'php_sapi_name(): '.php_sapi_name(),
            'PHP_BINARY:      '.(PHP_BINARY !== '' ? PHP_BINARY : '(unknown)'),
            'PHP_VERSION:     '.PHP_VERSION,
            'Loaded php.ini:  '.(php_ini_loaded_file() ?: '(none)'),
            'extension_dir:   '.(ini_get('extension_dir') ?: '(unset)'),
            'Scanned .ini:    '.($scanned !== '' ? $scanned : '(none)'),
        ];

        return implode("\n", $lines)."\n";
    }

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
                ."\nThis request ran PHP ".PHP_VERSION.".\n\n"
                ."Careful which cPanel screen you use. This account serves its domains\n"
                ."with ea-php (MultiPHP Manager, and the AddHandler line in\n"
                ."public/.htaccess), but the \"Select PHP Version\" extension list is the\n"
                ."CloudLinux PHP Selector, which only configures alt-php builds. Ticking\n"
                ."an extension there does nothing for an ea-php site — it looks enabled\n"
                ."and stays missing. Installing an ea-php package (ea-php84-php-intl,\n"
                ."say) needs root, so it is a request to the host.\n\n"
                ."The PHP that actually served this request:\n\n"
                .self::phpEnvironmentReport(),
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
