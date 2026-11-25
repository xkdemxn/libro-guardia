<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SegundoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Envía el código del desafío al correo verificado del operador.
 */
class CodigoCorreoSegundoFactorController extends Controller
{
    public function store(Request $request, SegundoFactor $segundoFactor): JsonResponse
    {
        $desafio = $request->session()->get(SegundoFactor::DESAFIO);
        $user = $desafio ? User::find($desafio['usuario']) : null;

        abort_unless($desafio && $segundoFactor->vigente($user, $desafio), 404, __('No hay un inicio de sesión pendiente.'));
        abort_unless($segundoFactor->correoDisponible($user), 403);

        $error = $segundoFactor->enviarCodigoCorreo($request, $user, $desafio);

        if ($error !== null) {
            throw ValidationException::withMessages(['codigo' => $error]);
        }

        return response()->json(['mensaje' => __('Enviamos un código a tu correo verificado.')], 202);
    }
}
