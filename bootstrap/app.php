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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(\App\Http\Middleware\RedirectStallBookingDomain::class);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'front_desk' => \App\Http\Middleware\EnsureUserIsFrontDesk::class,
            'security_desk' => \App\Http\Middleware\EnsureUserIsSecurityDesk::class,
        ]);

        // Exclude CCAvenue callback routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'payment/response',
            'payment/cancel',
            'visitor-payment/response',
            'visitor-payment/cancel',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
