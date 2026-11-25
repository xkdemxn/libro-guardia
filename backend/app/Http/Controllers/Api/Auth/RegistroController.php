<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegistroRequest;
use App\Http\Resources\UsuarioResource;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegistroController extends Controller
{
    /**
     * Registra un nuevo operador (US-01) y deja su sesión iniciada.
     */
    public function store(RegistroRequest $request): JsonResponse
    {
        $user = $request->crearUsuario();

        event(new Registered($user));

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return (new UsuarioResource($user))->response()->setStatusCode(201);
    }
}
