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
    ->withMiddleware(function (Middleware $middleware) { // <-- La función a modificar
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // 👇 --- LÍNEA A AÑADIR --- 👇
        // Aquí registramos el alias para nuestro middleware de administrador.
        $middleware->alias([
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
            'isTeamLead' => \App\Http\Middleware\IsTeamLead::class, // <-- Añade esta línea
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();