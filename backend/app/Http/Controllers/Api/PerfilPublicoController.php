<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrefijoPais;
use App\Models\User;
use App\Rules\IndicativoValido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Perfil público de un operador (US-15). No necesita sesión y solo devuelve lo que el
 * operador decidió hacer público.
 */
class PerfilPublicoController extends Controller
{
    /**
     * Si el indicativo no está registrado responde 404 con el indicativo normalizado y,
     * si se reconoce su prefijo, el país al que pertenece.
     */
    public function show(Request $request, string $indicativo): JsonResponse
    {
        $indicativo = IndicativoValido::normalizar($indicativo);
        $operador = User::query()->with('visibilidad')->where('indicativo', $indicativo)->first();

        if ($operador === null) {
            $prefijo = PrefijoPais::paraIndicativo($indicativo);

            return response()->json([
                'message' => __('Este indicativo no está registrado.'),
                'buscado' => $indicativo,
                'prefijo' => $prefijo ? ['pais' => $prefijo->pais, 'codigo_bandera' => $prefijo->codigo_bandera] : null,
            ], 404);
        }

        return response()->json([
            'data' => [
                ...$operador->datosPublicos(),
                'es_propio' => $request->user('sanctum')?->is($operador) ?? false,
            ],
        ]);
    }
}
