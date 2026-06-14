<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 1. Prepend Security Headers
        $middleware->prepend(App\Http\Middleware\SecurityHeadersMiddleware::class);

        // 🌟 2. DAFTARKAN ALIAS MIDDLWARE 'role' DI SINI 🌟
        $middleware->alias([
            // Sesuaikan 'RoleMiddleware::class' dengan nama file middleware role asli milikmu!
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // 1. Paksa format JSON jika request mengharapkan JSON ATAU merupakan request AJAX/Fetch dari React
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->expectsJson() || $request->ajax()
        );

        // 2. Menjinakkan AuthenticationException untuk arsitektur API via web.php
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized session. Silakan lakukan autentikasi terlebih dahulu.'
                ], 401);
            }

            // Fallback jika diakses manual lewat browser
            return redirect()->guest(route('login'));
        });
    })->create();
