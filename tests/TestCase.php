<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** A URL path in the admin panel, wherever config/kayaa.php admin_path puts it: adminPath('orders') → "/kdash/orders". */
    protected function adminPath(string $path = ''): string
    {
        return '/'.trim(config('kayaa.admin_path').'/'.ltrim($path, '/'), '/');
    }
}
