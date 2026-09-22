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
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            '/api/*',
        ]);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\ValidateApiOrigin::class);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }
            return '/login';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $firstError = collect($e->errors())->flatten()->first() ?: 'ទិន្នន័យដែលបានបញ្ចូលមិនត្រឹមត្រូវ (Invalid input data).';
                return response()->json([
                    'message' => $firstError,
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\TypeError $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'ទម្រង់ទិន្នន័យមិនត្រឹមត្រូវ (Invalid parameter format).'
                ], 422);
            }
        });

        $exceptions->render(function (\ErrorException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                if (str_contains($e->getMessage(), 'Array to string conversion') || str_contains($e->getMessage(), 'must be of type string')) {
                    return response()->json([
                        'message' => 'ទម្រង់ទិន្នន័យមិនត្រឹមត្រូវ (Invalid parameter format).'
                    ], 422);
                }
            }
        });
    })->create();
