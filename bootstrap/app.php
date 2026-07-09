<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/admin.php',        // ← admin panel (correct method names)
            __DIR__.'/../routes/web_parts.php',    // ← public parts listing
            __DIR__.'/../routes/parts_detail.php', // ← public part detail page
            __DIR__.'/../routes/checkout.php',     // ← public checkout
            __DIR__.'/../routes/receipt.php',      // ← public receipt
            __DIR__.'/../routes/ai.php',           // ← AI/autocomplete endpoints
            __DIR__.'/../routes/web.php',          // ← new Powerlink routes (ROI, barcode, bin labels etc.)
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin.auth'     => \App\Http\Middleware\AdminAuth::class,
            'stocking-clerk' => \App\Http\Middleware\RestrictStockingClerk::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
