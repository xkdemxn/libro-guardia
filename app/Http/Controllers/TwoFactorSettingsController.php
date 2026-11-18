<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SegundoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class TwoFactorSettingsController extends Controller
{
    public function __construct(
        private SegundoFactor $segundoFactor,
        private GenerateNewRecoveryCodes $generarCodigos,
    ) {}

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validateWithBag('segundoFactor', [
            'accion' => ['required', 'in:preparar-app,desactivar-app,activar-correo,desactivar-correo,recuperacion'],
            'password' => ['required', 'current_password'],
        ]);

        return DB::transaction(function () use ($request, $datos): RedirectResponse {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());

            if (! hash_equals($request->user()->getAuthPassword(), $user->getAuthPassword())) {
                $this->error('password', 'Confirma tu contraseña actual.');
            }

            if ($datos['accion'] === 'preparar-app') {
                abort_if($user->hasEnabledTwoFactorAuthentication(), 409);
                $this->segundoFactor->prepararApp($request, $user);

                return $this->perfil();
            }

            if ($datos['accion'] === 'activar-correo') {
                if (! $user->hasVerifiedEmail()) {
                    $this->error('accion', 'Verifica tu correo antes de activar este método.');
                }

                if (! $this->segundoFactor->envioCorreoDisponible()) {
                    $this->error('accion', 'El envío de correo todavía no está configurado.');
                }
            }

            $antes = $this->segundoFactor->activo($user);

            match ($datos['accion']) {
                'activar-correo' => $user->two_factor_email_enabled = true,
                'desactivar-correo' => $user->two_factor_email_enabled = false,
                'desactivar-app' => $user->forceFill([
                    'two_factor_secret' => null,
                    'two_factor_confirmed_at' => null,
                ]),
                'recuperacion' => abort_unless($antes, 409),
            };

            if ($user->isDirty(['two_factor_email_enabled', 'two_factor_secret'])) {
                $user->setRememberToken(Str::random(60));
            }

            $activo = $this->segundoFactor->activo($user);
            if (! $activo) {
                $user->two_factor_recovery_codes = null;
            }
            $user->save();

            if ($activo && (! $antes || $datos['accion'] === 'recuperacion')) {
                return $this->mostrarCodigos($user);
            }

            return $this->perfil()->with('status', 'Métodos de verificación actualizados.');
        });
    }

    public function confirm(Request $request): RedirectResponse
    {
        $datos = $request->validateWithBag('segundoFactor', [
            'codigo' => ['required', 'string', 'regex:/^[0-9]{6}$/D'],
        ]);

        return DB::transaction(function () use ($request, $datos): RedirectResponse {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
            $secreto = $this->segundoFactor->secretoPendiente($request, $user);

            if (! $secreto || $user->hasEnabledTwoFactorAuthentication()) {
                $this->error('accion', 'Vuelve a iniciar la configuración de la app.');
            }

            $limite = 'two-factor:configurar:'.$user->getKey();
            if (RateLimiter::tooManyAttempts($limite, 5)) {
                $this->error('codigo', 'Demasiados intentos. Espera cinco minutos.');
            }

            if (! $this->segundoFactor->verificarApp($secreto, $datos['codigo'])) {
                RateLimiter::hit($limite, 300);

                return $this->perfil()->withErrors(['codigo' => 'El código de la app es inválido.'], 'segundoFactor');
            }

            $antes = $this->segundoFactor->activo($user);
            $user->forceFill([
                'two_factor_secret' => $secreto,
                'two_factor_confirmed_at' => now(),
            ]);
            $user->setRememberToken(Str::random(60));
            $user->save();

            RateLimiter::clear($limite);
            $request->session()->forget(SegundoFactor::CONFIGURACION);

            return $antes
                ? $this->perfil()->with('status', 'App autenticadora activada.')
                : $this->mostrarCodigos($user);
        });
    }

    private function mostrarCodigos(User $user): RedirectResponse
    {
        ($this->generarCodigos)($user);

        return $this->perfil()
            ->with('status', 'Guarda tus códigos de recuperación en un lugar seguro.')
            ->with('two_factor_recovery', hash('sha256', $user->two_factor_recovery_codes));
    }

    private function perfil(): RedirectResponse
    {
        return redirect()->route('profile.edit')->with('seccion', 'seguridad');
    }

    private function error(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje])->errorBag('segundoFactor');
    }
}
