<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrefijoPais;
use App\Support\Ubicaciones;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class PrefijosController extends Controller
{
    /**
     * Prefijos de indicativo por país, para la página «¿Qué es una QSL?». Públicos.
     */
    public function __invoke(): JsonResponse
    {
        $paises = PrefijoPais::query()
            ->orderBy('pais')
            ->orderBy('prefijo')
            ->get()
            ->groupBy('pais')
            ->map(fn (Collection $prefijos, string $pais): array => [
                'pais' => $pais,
                'codigo_bandera' => $prefijos->first()->codigo_bandera,
                'continente' => __(Ubicaciones::continente($prefijos->first()->continente) ?? 'Otros'),
                'prefijos' => $prefijos->pluck('prefijo')->sortBy(fn (string $prefijo): array => [strlen($prefijo), $prefijo])->values(),
            ])
            ->values();

        return response()->json(['data' => $paises])->header('Cache-Control', 'public, max-age=3600');
    }
}
