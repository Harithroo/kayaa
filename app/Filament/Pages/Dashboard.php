<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * The admin dashboard, at /<admin_path>/overview instead of the panel root. The
 * widgets are the ones discovered in app/Filament/Widgets.
 */
class Dashboard extends BaseDashboard
{
    protected static string $routePath = '/overview';
}
