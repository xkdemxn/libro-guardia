<?php

use App\Http\Middleware\EstablecerIdioma;
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

        // Detrás de un túnel o del proxy de un hosting: así Laravel sabe que la visita llegó por HTTPS
        // y con qué dominio (enlaces de correo, URLs de las fotos, cookies).
        $middleware->trustProxies(at: '*');

        // Mensajes de la API en el idioma elegido en la SPA (Accept-Language).
        $middleware->append(EstablecerIdioma::class);

        // Sin sesión, las peticiones a la API responden 401 (JSON); cualquier otra va al login de la SPA.
        $middleware->redirectGuestsTo(fn (): string => config('app.frontend_url').'/iniciar-sesion');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['codigo']);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
