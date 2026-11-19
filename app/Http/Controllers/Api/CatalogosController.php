<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banda;
use App\Models\Modo;
use Illuminate\Http\JsonResponse;

class CatalogosController extends Controller
{
    /**
     * Bandas y modos para el formulario de contacto y los filtros del libro.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'bandas' => Banda::query()->orderBy('frecuencia_min_mhz')->get()->map(fn (Banda $banda): array => [
                'id' => $banda->id,
                'nombre' => $banda->nombre,
                'min' => $banda->frecuencia_min_mhz,
                'max' => $banda->frecuencia_max_mhz,
                'rango' => $banda->rango,
            ]),
            'modos' => Modo::query()->orderBy('nombre')->get()->map(fn (Modo $modo): array => [
                'id' => $modo->id,
                'nombre' => $modo->nombre,
                'etiqueta' => $modo->etiqueta,
                'tipo' => $modo->tipo_reporte->value,
                'ejemplo' => $modo->tipo_reporte->ejemplo(),
            ]),
            'hoy_utc' => now('UTC')->toDateString(),
        ]);
    }
}
