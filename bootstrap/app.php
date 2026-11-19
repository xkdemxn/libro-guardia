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
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // La SPA de React (frontend/) usa la sesión de Laravel por cookie a través de Sanctum.
        $middleware->statefulApi();

        // Sin sesión, las peticiones a la API responden 401 (JSON); cualquier otra va al login de la SPA.
        $middleware->redirectGuestsTo(fn (): string => config('app.frontend_url').'/iniciar-sesion');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['codigo']);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
