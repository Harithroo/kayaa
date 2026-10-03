<?php

use App\Http\Middleware\NoIndex;
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
        $middleware->append(NoIndex::class);

        // Storefront accounts live under /account, not Laravel's default /login.
        $middleware->redirectGuestsTo(fn () => route('account.login'));
        $middleware->redirectUsersTo(fn () => route('account.index'));

        // Onepay posts to this URL from its own servers, outside the browser
        // session. It authenticates by the transaction id surviving a
        // server-to-server status lookup, not by a session token.
        $middleware->validateCsrfTokens(except: [
            'payment/callback',
            // Called by GitHub Actions, not a browser; guarded by DEPLOY_TOKEN.
            'deploy/migrate',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
