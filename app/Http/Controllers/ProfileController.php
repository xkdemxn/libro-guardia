<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

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
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Actualiza datos, imágenes (foto y portada) y visibilidad del perfil (US-13).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->datosOperador());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        foreach (self::IMAGENES as $campo => [$columna, $carpeta]) {
            $user->cambiarImagen($columna, $carpeta, $request->file($campo), $request->boolean('quitar_'.$campo));
        }

        $user->save();
        $user->visibilidad()->updateOrCreate([], $request->visibilidad());

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
