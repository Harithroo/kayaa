<?php

use Illuminate\Support\Facades\Schedule;

// Scheduled tasks go here (cPanel cron: * * * * * php /path/to/artisan schedule:run)

// Rebuild public/sitemap.xml overnight, so new products and categories are
// discoverable the next morning without anyone remembering to run it.
Schedule::command('kayaa:sitemap')->dailyAt('03:17');
