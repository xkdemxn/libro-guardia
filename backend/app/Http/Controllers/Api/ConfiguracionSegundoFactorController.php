<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SegundoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;

/**
 * Métodos de verificación en dos pasos del operador: app autenticadora (QR) y código por correo.
 *
 * Las respuestas incluyen el estado actual y, cuando corresponde, el QR por configurar o los
 * códigos de recuperación recién generados (solo se muestran una vez).
 */
class ConfiguracionSegundoFactorController extends Controller
{
    public function __construct(
        private SegundoFactor $segundoFactor,
        private GenerateNewRecoveryCodes $generarCodigos,
    ) {}

    public function update(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'accion' => ['required', 'in:preparar-app,desactivar-app,activar-correo,desactivar-correo,recuperacion'],
            'password' => ['required', 'current_password'],
        ]);

        return DB::transaction(function () use ($request, $datos): JsonResponse {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());

            if (! hash_equals($request->user()->getAuthPassword(), $user->getAuthPassword())) {
                throw ValidationException::withMessages(['password' => __('Confirma tu contraseña actual.')]);
            }

            if ($datos['accion'] === 'preparar-app') {
                abort_if($user->hasEnabledTwoFactorAuthentication(), 409);
                $this->segundoFactor->prepararApp($request, $user);

                return $this->respuesta($request, $user, __('Escanea el QR con tu app autenticadora.'));
            }

            if ($datos['accion'] === 'activar-correo') {
                if (! $user->hasVerifiedEmail()) {
                    throw ValidationException::withMessages(['accion' => __('Verifica tu correo antes de activar este método.')]);
                }

                if (! $this->segundoFactor->envioCorreoDisponible()) {
                    throw ValidationException::withMessages(['accion' => __('El envío de correo todavía no está configurado.')]);
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
                ($this->generarCodigos)($user);

                return $this->respuesta($request, $user, __('Guarda tus códigos de recuperación en un lugar seguro.'), codigos: true);
            }

            return $this->respuesta($request, $user, __('Métodos de verificación actualizados.'));
        });
    }

    /**
     * Activa la app con el primer código que muestra, para comprobar que el QR se escaneó bien.
     */
    public function confirmar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'regex:/^[0-9]{6}$/D'],
        ], ['codigo.regex' => __('El código tiene 6 dígitos.')]);

        // El error se devuelve desde la transacción y se lanza afuera: así el rollback no borra
        // el intento fallido cuando la caché está en la base de datos.
        /** @var array{0: User, 1: bool}|string $resultado */
        $resultado = DB::transaction(function () use ($request, $datos): array|string {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
            $secreto = $this->segundoFactor->secretoPendiente($request, $user);

            if (! $secreto || $user->hasEnabledTwoFactorAuthentication()) {
                return __('Vuelve a iniciar la configuración de la app.');
            }

            $limite = 'two-factor:configurar:'.$user->getKey();
            if (RateLimiter::tooManyAttempts($limite, 5)) {
                return __('Demasiados intentos. Espera cinco minutos.');
            }

            if (! $this->segundoFactor->verificarApp($secreto, $datos['codigo'])) {
                RateLimiter::hit($limite, 300);

                return __('El código de la app es inválido.');
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

            if (! $antes) {
                ($this->generarCodigos)($user);
            }

            return [$user, ! $antes];
        });

        if (is_string($resultado)) {
            throw ValidationException::withMessages(['codigo' => $resultado]);
        }

        [$user, $codigosNuevos] = $resultado;

        return $codigosNuevos
            ? $this->respuesta($request, $user, __('Guarda tus códigos de recuperación en un lugar seguro.'), codigos: true)
            : $this->respuesta($request, $user, __('App autenticadora activada.'));
    }

    private function respuesta(Request $request, User $user, string $mensaje, bool $codigos = false): JsonResponse
    {
        $secreto = $this->segundoFactor->secretoPendiente($request, $user);

        return response()->json([
            'mensaje' => $mensaje,
            'app' => $user->hasEnabledTwoFactorAuthentication(),
            'correo' => (bool) $user->two_factor_email_enabled,
            'qr_svg' => $secreto && ! $user->hasEnabledTwoFactorAuthentication()
                ? (clone $user)->forceFill(['two_factor_secret' => $secreto])->twoFactorQrCodeSvg()
                : null,
            'clave_manual' => $secreto && ! $user->hasEnabledTwoFactorAuthentication()
                ? Fortify::currentEncrypter()->decrypt($secreto)
                : null,
            'codigos_recuperacion' => $codigos ? $user->recoveryCodes() : null,
        ])->header('Cache-Control', 'no-store, private');
    }
}
