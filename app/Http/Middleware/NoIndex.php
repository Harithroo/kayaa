<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// On staging (APP_NOINDEX=true), keeps every page — storefront and admin — out of search engines.
class NoIndex
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (config('kayaa.noindex')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
