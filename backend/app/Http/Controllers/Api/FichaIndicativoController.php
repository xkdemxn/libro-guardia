<?php

namespace App\Http\Controllers\Api;

use App\Enums\ZonaEcuador;
use App\Http\Controllers\Controller;
use App\Models\PrefijoPais;
use App\Models\User;
use App\Rules\IndicativoValido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Innovación 3: ficha rápida que aparece al escribir un indicativo en el formulario de contacto.
 *
 * Muestra el país, si ya hablaste antes con esa estación y, si es usuario del sistema,
 * solo los datos que esa persona decidió hacer públicos.
 */
class FichaIndicativoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $indicativo = IndicativoValido::normalizar($request->query('indicativo'));

        if (! IndicativoValido::esValido($indicativo)) {
            return response()->json(['valido' => false]);
        }

        $previos = $request->user()->contactos()
            ->with(['banda', 'modo'])
            ->where('estacion_llamada', $indicativo)
            ->orderByDesc('inicio_utc');

        $ultimo = (clone $previos)->first();
        $operador = User::query()->where('indicativo', $indicativo)->first();
        $prefijo = PrefijoPais::paraIndicativo($indicativo);

        return response()->json([
            'valido' => true,
            'indicativo' => $indicativo,
            'pais' => $prefijo?->pais,
            'zona' => ZonaEcuador::paraIndicativo($indicativo)?->descripcion(),
            'codigo_bandera' => $prefijo?->codigo_bandera,
            'contactos_previos' => (clone $previos)->count(),
            'ultimo' => $ultimo === null ? null : [
                'fecha' => $ultimo->inicio_utc->format('Y-m-d'),
                'hace' => $ultimo->inicio_utc->locale(app()->getLocale())->diffForHumans(),
                'banda' => $ultimo->banda->nombre,
                'modo' => $ultimo->modo->nombre,
            ],
            'registrado' => $operador !== null && $operador->isNot($request->user()),
            'perfil' => $operador !== null && $operador->isNot($request->user()) ? [
                ...array_intersect_key($operador->datosPublicos(), array_flip(['nombre', 'ciudad', 'foto_url'])),
            ] : null,
        ]);
    }
}
