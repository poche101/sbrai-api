<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        // ── API Versioning ──────────────────────────────────────────────────
        // This prepends 'api/v1' to all routes defined in api.php
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ── Custom middleware aliases ──────────────────────────────────────
        $middleware->alias([
            'vendor' => \App\Http\Middleware\EnsureVendor::class,
        ]);

        // ── Sanctum stateful API middleware ────────────────────────────────
        $middleware->statefulApi();

        // ── Append CORS headers to every response ──────────────────────────
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // ── Trust proxies (important when behind Nginx / load balancer) ────
        $middleware->trustProxies(at: '*');

        // ── JSON response for unauthenticated API requests ─────────────────
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please provide a valid Bearer token.',
                ], 401));
            }
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ── Return JSON for all API exceptions ─────────────────────────────

        // 401 - Authentication
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        // 404 - Model Not Found
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'message' => "{$model} not found.",
                ], 404);
            }
        });

        // 404 - Endpoint Not Found
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Endpoint not found.',
                ], 404);
            }
        });

        // 422 - Validation Errors
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // 405 - Method Not Allowed
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'HTTP method not allowed.',
                ], 405);
            }
        });
    })
    ->create();
