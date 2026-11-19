<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use App\Services\SegundoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Segundo paso del inicio de sesión: el código de la app, del correo o de recuperación.
 */
class DesafioSegundoFactorController extends Controller
{
    public function __construct(private SegundoFactor $segundoFactor) {}

    /**
     * Métodos disponibles para el desafío pendiente. 404 si no hay ninguno vigente
     * (la SPA vuelve entonces al formulario de inicio de sesión).
     */
    public function show(Request $request): JsonResponse
    {
        $desafio = $request->session()->get(SegundoFactor::DESAFIO);
        $user = $desafio ? User::find($desafio['usuario']) : null;

        abort_unless($desafio && $this->segundoFactor->vigente($user, $desafio), 404, 'No hay un inicio de sesión pendiente.');

        return response()->json(['metodos' => $this->segundoFactor->metodos($user, $desafio)])
            ->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request): UsuarioResource
    {
        $desafio = $request->session()->get(SegundoFactor::DESAFIO);

        abort_unless($desafio, 404, 'No hay un inicio de sesión pendiente.');

        $datos = $request->validate([
            'metodo' => ['required', 'in:app,correo,recuperacion'],
            'codigo' => ['required', 'string', 'max:40'],
        ]);

        // Los errores se devuelven desde la transacción y se lanzan afuera: si se lanzaran dentro,
        // con la caché en base de datos el rollback borraría también el intento fallido contado.
        $resultado = DB::transaction(function () use ($request, $desafio, $datos): User|string|null {
            $user = User::query()->lockForUpdate()->find($desafio['usuario']);

            if (! $this->segundoFactor->vigente($user, $desafio)) {
                $request->session()->forget(SegundoFactor::DESAFIO);

                return null;
            }

            $limite = 'two-factor:verificar:'.$user->getKey();

            if (RateLimiter::tooManyAttempts($limite, 5)) {
                return 'Demasiados intentos. Espera cinco minutos.';
            }

            if (! $this->segundoFactor->verificarDesafio($user, $datos['metodo'], $datos['codigo'], $desafio)) {
                RateLimiter::hit($limite, 300);

                return 'El código es inválido o ha vencido.';
            }

            RateLimiter::clear($limite);

            return $user;
        });

        abort_if($resultado === null, 404, 'El inicio de sesión venció. Vuelve a ingresar tu contraseña.');

        if (is_string($resultado)) {
            throw ValidationException::withMessages(['codigo' => $resultado]);
        }

        $user = $resultado;

        $this->segundoFactor->iniciarSesion($request, $user, $desafio['recordar']);

        return new UsuarioResource($user);
    }
}
