<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ContrasenaController extends Controller
{
    /**
     * Cambia la contraseña del operador con sesión iniciada.
     */
    public function update(Request $request): JsonResponse
    {
        $validado = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update(['password' => $validado['password']]);

        return response()->json(['mensaje' => 'Contraseña actualizada.']);
    }
}
