<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\SegundoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Imágenes del perfil: campo del formulario => [columna en users, carpeta en el disco público].
     * Cada una se puede reemplazar o quitar con la casilla «quitar_{campo}». La foto de la tarjeta QSL
     * se edita en la propia tarjeta, pero se borra aquí junto con la cuenta.
     */
    private const IMAGENES = [
        'foto' => ['foto_path', 'fotos'],
        'portada' => ['portada_path', 'portadas'],
    ];

    private const IMAGEN_QSL = ['qsl_foto_path', 'qsl'];

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, SegundoFactor $segundoFactor): Response
    {
        $user = $request->user();
        $secreto = $segundoFactor->secretoPendiente($request, $user);
        $codigos = $request->session()->pull('two_factor_recovery');

        return response()->view('profile.edit', [
            'user' => $user,
            'envioCorreoDisponible' => $segundoFactor->envioCorreoDisponible(),
            'qrSegundoFactor' => $secreto
                ? (clone $user)->forceFill(['two_factor_secret' => $secreto])->twoFactorQrCodeSvg()
                : null,
            'codigosRecuperacion' => $codigos && $user->two_factor_recovery_codes
                && hash_equals($codigos, hash('sha256', $user->two_factor_recovery_codes))
                ? $user->recoveryCodes()
                : [],
        ])->header('Cache-Control', 'no-store, private');
    }

    /**
     * Actualiza datos, imágenes (foto y portada) y visibilidad del perfil (US-13).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
            $user->fill($request->datosOperador());

            if ($user->isDirty('email')) {
                if ($user->two_factor_email_enabled) {
                    throw ValidationException::withMessages([
                        'email' => 'Desactiva la verificación por correo en Seguridad antes de cambiar tu dirección.',
                    ]);
                }

                $user->email_verified_at = null;
            }

            foreach (self::IMAGENES as $campo => [$columna, $carpeta]) {
                $user->cambiarImagen($columna, $carpeta, $request->file($campo), $request->boolean('quitar_'.$campo));
            }

            $user->save();
            $user->visibilidad()->updateOrCreate([], $request->visibilidad());
        });

        return Redirect::route('profile.edit')
            ->with('status', 'Perfil actualizado.')
            ->with('seccion', $request->string('seccion')->toString());
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        foreach ([...array_values(self::IMAGENES), self::IMAGEN_QSL] as [$columna, $carpeta]) {
            $user->cambiarImagen($columna, $carpeta, null, quitar: true);
        }
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
