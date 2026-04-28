<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;



return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/orange/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
       $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
        
        // Force JSON for API routes
        if ($request->is('api/*')) {
            return response()->json([
                'status' => false,
                'error' => 'Token expired or invalid'
            ], 401);
        }

        // Default behavior for web
        return redirect()->guest('login');
    });
    })->create();
