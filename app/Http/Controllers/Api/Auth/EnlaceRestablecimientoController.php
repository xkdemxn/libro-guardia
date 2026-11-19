<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class EnlaceRestablecimientoController extends Controller
{
    /**
     * Envía el enlace para restablecer la contraseña (US-18). La respuesta es la misma
     * exista o no el correo, para no revelar quién está registrado.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        return response()->json(['mensaje' => PasswordResetLinkController::MENSAJE_NEUTRO]);
    }
}
