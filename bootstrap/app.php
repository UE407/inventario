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
        // Laravel 11 ya registra los alias 'auth', 'guest', etc. por defecto.
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'suscripcion' => \App\Http\Middleware\EnsureSuscripcionActiva::class,
            'superadmin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);

        // Identifica la empresa (tenant) activa en cada petición web,
        // después de que la sesión resuelva al usuario autenticado.
        $middleware->web(append: [
            \App\Http\Middleware\IdentifyTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
