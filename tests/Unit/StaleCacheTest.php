<?php

namespace Tests\Unit;

use App\Support\StaleCache;
use PHPUnit\Framework\TestCase;

/** The FTP deploy leaves old caches behind; StaleCache drops them when their sources are newer. */
class StaleCacheTest extends TestCase
{
    private string $base;

    protected function setUp(): void
    {
        $this->base = sys_get_temp_dir().'/stale-cache-'.uniqid();
        foreach (['bootstrap/cache', 'config', 'routes'] as $dir) {
            mkdir($this->base.'/'.$dir, 0777, true);
        }
        foreach (['config/app.php', 'config/kayaa.php', 'routes/web.php', '.env', 'bootstrap/cache/config.php', 'bootstrap/cache/routes-v7.php', 'bootstrap/cache/packages.php'] as $file) {
            file_put_contents($this->base.'/'.$file, '<?php return [];');
            touch($this->base.'/'.$file, 1_000_000);
        }
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->base));
    }

    private function exists(string $file): bool
    {
        return is_file($this->base.'/bootstrap/cache/'.$file);
    }

    public function test_fresh_caches_are_kept(): void
    {
        StaleCache::clear($this->base);

        $this->assertTrue($this->exists('config.php'));
        $this->assertTrue($this->exists('routes-v7.php'));
    }

    public function test_a_newer_config_file_drops_the_config_and_route_caches(): void
    {
        touch($this->base.'/config/kayaa.php', 2_000_000);

        StaleCache::clear($this->base);

        $this->assertFalse($this->exists('config.php'));
        $this->assertFalse($this->exists('routes-v7.php'));
        $this->assertTrue($this->exists('packages.php'), 'Only the config and route caches are touched.');
    }

    public function test_a_newer_env_file_drops_the_config_cache(): void
    {
        touch($this->base.'/.env', 2_000_000);

        StaleCache::clear($this->base);

        $this->assertFalse($this->exists('config.php'));
    }

    public function test_a_newer_route_file_drops_only_the_route_cache(): void
    {
        touch($this->base.'/routes/web.php', 2_000_000);

        StaleCache::clear($this->base);

        $this->assertTrue($this->exists('config.php'));
        $this->assertFalse($this->exists('routes-v7.php'));
    }

    public function test_no_caches_is_fine(): void
    {
        unlink($this->base.'/bootstrap/cache/config.php');
        unlink($this->base.'/bootstrap/cache/routes-v7.php');

        StaleCache::clear($this->base);

        $this->assertFalse($this->exists('config.php'));
    }
}
