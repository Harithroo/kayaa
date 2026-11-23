<?php

namespace App\Support;

/**
 * Drops a config or route cache that is older than the files it was built from.
 *
 * The deploy is an FTP sync: it uploads new code but leaves the server's
 * bootstrap/cache/config.php and routes-*.php (written by `config:cache` and
 * `route:cache` on the previous deploy) in place. Until the deploy hook rebuilds
 * them, the app runs new code against old config, so a config key added in this
 * deploy reads as null. When that key is needed while the app boots (the admin
 * panel's path, say), every request fails, the deploy hook included, so the
 * caches can never be rebuilt. Run from bootstrap/app.php before the app is
 * created, this deletes a stale cache instead and Laravel reads the files
 * directly until `config:cache` and `route:cache` run again.
 *
 * Cost: one stat per config and route file (about twenty) per request, and only
 * when a cache exists.
 */
final class StaleCache
{
    public static function clear(string $basePath): void
    {
        $cache = $basePath.'/bootstrap/cache';
        $config = $cache.'/config.php';
        $routes = glob($cache.'/routes-*.php') ?: [];

        $configStale = is_file($config)
            && self::newerThan(filemtime($config), [...self::files($basePath.'/config'), $basePath.'/.env']);

        if ($configStale) {
            @unlink($config);
        }

        foreach ($routes as $file) {
            // Routes read config (the admin path), so a stale config cache means stale routes too.
            if ($configStale || self::newerThan(filemtime($file), self::files($basePath.'/routes'))) {
                @unlink($file);
            }
        }
    }

    /** @param  list<string>  $files */
    private static function newerThan(int|false $built, array $files): bool
    {
        if ($built === false) {
            return false;
        }
        foreach ($files as $file) {
            $changed = @filemtime($file);
            if ($changed !== false && $changed > $built) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> every .php file under $dir */
    private static function files(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }
        $files = [];
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($items as $item) {
            if ($item->isFile() && $item->getExtension() === 'php') {
                $files[] = $item->getPathname();
            }
        }

        return $files;
    }
}
