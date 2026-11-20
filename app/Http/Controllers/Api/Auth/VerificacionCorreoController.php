<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificacionCorreoController extends Controller
{
    /**
     * Reenvía el enlace de verificación del correo.
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['mensaje' => __('Tu correo ya está verificado.')]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['mensaje' => __('Te enviamos un nuevo enlace de verificación.')], 202);
    }
}
