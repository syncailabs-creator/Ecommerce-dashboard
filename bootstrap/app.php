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
        $middleware->validateCsrfTokens(except: [
            '/shipway/webhook',
            '/shopify-orders/fetch-recent',
            '/shopify/fetch-orders',
            '/meta-ads/fetch-campaigns',
            '/meta-ads/fetch-adsets',
            '/meta-ads/fetch-ads',
            '/job-fire',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
