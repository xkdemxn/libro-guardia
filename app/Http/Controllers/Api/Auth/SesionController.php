<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use App\Services\SegundoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Inicio y cierre de sesión de la SPA (US-02). La sesión vive en la cookie de Laravel (Sanctum).
 */
class SesionController extends Controller
{
    /**
     * Si el operador tiene verificación en dos pasos, la sesión queda pendiente del desafío
     * y la respuesta dice qué métodos puede usar; si no, la sesión queda iniciada.
     */
    public function store(LoginRequest $request, SegundoFactor $segundoFactor): JsonResponse
    {
        $request->session()->forget(SegundoFactor::DESAFIO);
        $validado = $request->validarCredenciales();

        return DB::transaction(function () use ($request, $validado, $segundoFactor): JsonResponse {
            $user = User::query()->lockForUpdate()->find($validado->getKey());

            if (! $user || ! hash_equals($validado->getAuthPassword(), $user->getAuthPassword())) {
                throw ValidationException::withMessages(['login' => 'Indicativo o contraseña incorrectos.']);
            }

            if (config('hashing.rehash_on_login', true)) {
                Auth::guard('web')->getProvider()->rehashPasswordIfRequired($user, $request->credenciales());
            }

            if ($segundoFactor->activo($user)) {
                $segundoFactor->iniciar($request, $user);

                return response()->json([
                    'segundo_factor' => true,
                    'metodos' => $segundoFactor->metodos($user, $request->session()->get(SegundoFactor::DESAFIO)),
                ]);
            }

            $segundoFactor->iniciarSesion($request, $user, $request->boolean('remember'));

            return response()->json([
                'segundo_factor' => false,
                'usuario' => new UsuarioResource($user),
            ]);
        });
    }

    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
