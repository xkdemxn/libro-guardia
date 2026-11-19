<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Ubicaciones;
use Illuminate\Http\JsonResponse;

class UbicacionesController extends Controller
{
    /**
     * Listas para los desplegables de país, provincia y ciudad del perfil.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            ...Ubicaciones::paraFormulario(),
            'provincias' => Ubicaciones::provincias(),
        ]);
    }
}
