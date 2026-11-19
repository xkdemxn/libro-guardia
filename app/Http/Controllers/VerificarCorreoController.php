<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;

/**
 * Enlace del correo de verificación. La firma de la URL ya prueba que el enlace es auténtico, así que
 * no hace falta tener la sesión abierta en ese navegador. Al terminar abre la SPA de React.
 */
class VerificarCorreoController extends Controller
{
    public function __invoke(string $id, string $hash): RedirectResponse
    {
        $user = User::query()->find($id);
        $destino = config('app.frontend_url').'/contactos';

        if ($user === null || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect()->away($destino.'?verificado=0');
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->away($destino.'?verificado=1');
    }
}
