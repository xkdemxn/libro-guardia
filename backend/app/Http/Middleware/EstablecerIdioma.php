<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Responde en el idioma que pide la SPA con la cabecera Accept-Language: español (por defecto),
 * inglés o portugués de Brasil.
 */
class EstablecerIdioma
{
    /**
     * Idioma de la SPA => idioma de Laravel (carpetas de lang/).
     */
    public const IDIOMAS = ['es' => 'es', 'en' => 'en', 'pt' => 'pt_BR'];

    public function handle(Request $request, Closure $next): Response
    {
        $idioma = $request->getPreferredLanguage(array_keys(self::IDIOMAS));
        app()->setLocale(self::IDIOMAS[substr((string) $idioma, 0, 2)] ?? 'es');

        return $next($request);
    }
}
