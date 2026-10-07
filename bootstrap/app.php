<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/admin.php'));
            Route::middleware('web')->group(base_path('routes/business.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'guest/cart/add',
            'guest/cart/order',
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\NoCacheLivewire::class,
            \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->alias([
            'business.context' => \App\Http\Middleware\EnforceBusinessContext::class,
            'business.scope' => \App\Http\Middleware\BusinessScopeMiddleware::class,
            'admin' => \App\Http\Middleware\EnsureIsAdmin::class,
            'permission' => \App\Http\Middleware\RequirePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ── Handle Livewire routing errors gracefully ──────────────────────
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            // If it's a Livewire route that got a GET instead of POST, redirect back
            if (str_contains($request->path(), 'livewire') && $request->isMethod('get')) {
                return redirect()->back()->with('error', 'Invalid request. Please refresh the page.');
            }
        });

        // ── Consistent JSON errors for API routes ──────────────────────────
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage() ?: 'Forbidden.'], 403);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $model = class_basename($e->getModel());
                return response()->json(['success' => false, 'message' => "{$model} not found."], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage() ?: 'HTTP error.'], $e->getStatusCode());
            }
        });

    })->create();

