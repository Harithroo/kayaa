<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Returns the tail of the Laravel log, for hosts without SSH.
 *
 * This host (cPanel, port 21 only) gives no shell, so a 500 on the server used
 * to be a dead end: APP_DEBUG is off in staging, the browser shows the generic
 * error page, and the stack trace sits in a file nobody can read. This hands
 * back the end of that file as plain text.
 *
 * Guarded exactly like DeployController: the DEPLOY_TOKEN from the server .env,
 * sent as the X-Deploy-Token header. With no token configured the route behaves
 * as if it does not exist, so an unconfigured server never exposes its log.
 */
class DeployLogController extends Controller
{
    /** Never return more than this, however large the log has grown. */
    private const MAX_BYTES = 262144;

    public function __invoke(Request $request): Response
    {
        $expected = (string) config('kayaa.deploy_token');
        $given = (string) $request->header('X-Deploy-Token', '');

        // 404 rather than 403: a wrong token should not confirm the route exists.
        if ($expected === '' || $given === '' || ! hash_equals($expected, $given)) {
            abort(404);
        }

        $path = storage_path('logs/laravel.log');

        if (! is_file($path) || ! is_readable($path)) {
            return response("No log file at {$path}\n", 404)
                ->header('Content-Type', 'text/plain');
        }

        $bytes = min(
            self::MAX_BYTES,
            max(1, (int) $request->integer('bytes', self::MAX_BYTES)),
        );

        $size = (int) filesize($path);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return response("Could not open {$path}\n", 500)
                ->header('Content-Type', 'text/plain');
        }

        if ($size > $bytes) {
            fseek($handle, -$bytes, SEEK_END);
        }

        $tail = (string) stream_get_contents($handle);
        fclose($handle);

        return response(
            '--- last '.strlen($tail)." of {$size} bytes of storage/logs/laravel.log ---\n\n".$tail,
        )->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
