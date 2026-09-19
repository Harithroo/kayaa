<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\NoIndex::class);

        // PayHere posts to this URL from outside the browser session.
        $middleware->validateCsrfTokens(except: [
            'payhere/notify',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
