<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // 1. Enable Sanctum Stateful Middleware for API
        $middleware->statefulApi();

        // 2. Register Custom Middleware Aliases
        $middleware->alias([
            'vendor' => \App\Http\Middleware\VendorMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {

        /**
         * BUG 4 FIX: Custom Debug Catch-all
         * If APP_DEBUG is true, we intercept exceptions and return
         * JSON details instead of a generic 500 error.
         */
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') && config('app.debug')) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->take(5), // Limited trace for readability
                ], 500);
            }
        });

    })->create();
