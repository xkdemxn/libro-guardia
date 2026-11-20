<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Entrega la SPA de React.
 *
 * En producción React está compilado en public/spa (npm run build): cualquier dirección que no sea
 * de la API devuelve su index.html y React Router muestra la página. En desarrollo no hay
 * compilación, así que se redirige al servidor de Vite con la misma dirección.
 */
class SpaController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $indice = config('app.spa_index');

        if (is_file($indice)) {
            return response(file_get_contents($indice), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                // El index.html cambia en cada compilación: nunca se guarda en caché (los assets sí, llevan hash).
                'Cache-Control' => 'no-cache, private',
            ]);
        }

        return redirect()->away(rtrim(config('app.frontend_url'), '/').$request->getRequestUri());
    }
}
