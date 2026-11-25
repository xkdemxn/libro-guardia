<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\PerfilResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * «Mi perfil» (US-13): datos, estación, portada y privacidad.
 */
class PerfilController extends Controller
{
    /**
     * Imágenes del perfil: campo del formulario => [columna en users, carpeta en el disco público].
     * Cada una se puede reemplazar o quitar con «quitar_{campo}». La foto de la tarjeta QSL
     * se edita en la propia tarjeta, pero se borra aquí junto con la cuenta.
     */
    private const IMAGENES = [
        'foto' => ['foto_path', 'fotos'],
        'portada' => ['portada_path', 'portadas'],
    ];

    private const IMAGEN_QSL = ['qsl_foto_path', 'qsl'];

    public function show(Request $request): JsonResponse
    {
        return (new PerfilResource($request->user()))->response()->header('Cache-Control', 'no-store, private');
    }

    /**
     * Actualiza datos, imágenes (foto y portada) y visibilidad del perfil. La SPA lo envía como
     * POST multipart con _method=PATCH, porque PHP no lee archivos de un PATCH real.
     */
    public function update(ProfileUpdateRequest $request): PerfilResource
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
            $user->fill($request->datosOperador());

            if ($user->isDirty('email')) {
                if ($user->two_factor_email_enabled) {
                    throw ValidationException::withMessages([
                        'email' => __('Desactiva la verificación por correo en Seguridad antes de cambiar tu dirección.'),
                    ]);
                }

                $user->email_verified_at = null;
            }

            foreach (self::IMAGENES as $campo => [$columna, $carpeta]) {
                $user->cambiarImagen($columna, $carpeta, $request->file($campo), $request->boolean('quitar_'.$campo));
            }

            $user->save();
            $user->visibilidad()->updateOrCreate([], $request->visibilidad());

            return $user;
        });

        return new PerfilResource($user->load('visibilidad'));
    }

    /**
     * Borra la cuenta, sus imágenes y su libro de guardia.
     */
    public function destroy(Request $request): Response
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::guard('web')->logout();

        foreach ([...array_values(self::IMAGENES), self::IMAGEN_QSL] as [$columna, $carpeta]) {
            $user->cambiarImagen($columna, $carpeta, null, quitar: true);
        }
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
