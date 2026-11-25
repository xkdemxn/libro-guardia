<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SegundoFactor
{
    public const DESAFIO = 'two_factor.challenge';

    public const CONFIGURACION = 'two_factor.setup';

    public function __construct(private TwoFactorAuthenticationProvider $totp) {}

    public function activo(User $user): bool
    {
        return $user->hasEnabledTwoFactorAuthentication() || $user->two_factor_email_enabled;
    }

    public function correoDisponible(User $user): bool
    {
        return $user->two_factor_email_enabled && $user->hasVerifiedEmail();
    }

    public function envioCorreoDisponible(): bool
    {
        $configuracion = config('mail.mailers.'.config('mail.default'), []);
        $transporte = $configuracion['transport'] ?? null;

        return match ($transporte) {
            'log', null => false,
            'array' => app()->environment('testing'),
            'brevo' => filled(config('services.brevo.key'))
                && filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false,
            'smtp' => ($configuracion['host'] ?? null) !== 'smtp.gmail.com'
                || (filled($configuracion['username'] ?? null)
                    && filled($configuracion['password'] ?? null)
                    && filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false),
            default => true,
        };
    }

    public function iniciar(Request $request, User $user): void
    {
        $request->session()->regenerate(true);
        $request->session()->put(self::DESAFIO, [
            'usuario' => $user->getKey(),
            'firma' => $this->firma($user),
            'vence' => now()->addMinutes(10)->timestamp,
            'recordar' => $request->boolean('remember'),
            'nonce' => bin2hex(random_bytes(16)),
        ]);
    }

    /** @param array{usuario: int, firma: string, vence: int, recordar: bool, nonce: string} $desafio */
    public function vigente(?User $user, array $desafio): bool
    {
        return $user && $this->activo($user) && now()->timestamp < $desafio['vence']
            && hash_equals($desafio['firma'], $this->firma($user));
    }

    /**
     * Cierra el desafío y deja la sesión iniciada.
     */
    public function iniciarSesion(Request $request, User $user, bool $recordar): void
    {
        $request->session()->forget(self::DESAFIO);
        Auth::guard('web')->login($user, $recordar);
        $request->session()->regenerate();
    }

    /**
     * Métodos que el operador puede usar para pasar el desafío.
     *
     * @param  array{correo_vence?: int}  $desafio
     * @return array{app: bool, correo: bool, correo_enviado: bool, envio_correo_disponible: bool}
     */
    public function metodos(User $user, array $desafio): array
    {
        return [
            'app' => $user->hasEnabledTwoFactorAuthentication(),
            'correo' => $this->correoDisponible($user),
            'correo_enviado' => now()->timestamp < ($desafio['correo_vence'] ?? 0),
            'envio_correo_disponible' => $this->envioCorreoDisponible(),
        ];
    }

    public function prepararApp(Request $request, User $user): void
    {
        $request->session()->put(self::CONFIGURACION, [
            'firma' => $this->firma($user),
            'secreto' => Fortify::currentEncrypter()->encrypt($this->totp->generateSecretKey(32)),
            'vence' => now()->addMinutes(10)->timestamp,
        ]);
    }

    public function secretoPendiente(Request $request, User $user): ?string
    {
        $configuracion = $request->session()->get(self::CONFIGURACION);

        return $configuracion && now()->timestamp < $configuracion['vence']
            && hash_equals($configuracion['firma'], $this->firma($user))
            ? $configuracion['secreto']
            : null;
    }

    public function verificarApp(string $secreto, #[\SensitiveParameter] string $codigo): bool
    {
        return $this->totp->verify(Fortify::currentEncrypter()->decrypt($secreto), $codigo);
    }

    public function firmaCorreo(string $nonce, #[\SensitiveParameter] string $codigo): string
    {
        return hash_hmac('sha256', $nonce.':'.$codigo, config('app.key'));
    }

    /**
     * Envía un código de un solo uso al correo verificado y lo guarda (firmado) en el desafío de la sesión.
     * Devuelve el mensaje de error para el operador, o null si se envió.
     *
     * @param  array{nonce: string, correo_firma?: string}  $desafio
     */
    public function enviarCodigoCorreo(Request $request, User $user, array $desafio): ?string
    {
        if (! $this->envioCorreoDisponible()) {
            return __('El envío de correo no está disponible. Usa la app o un código de recuperación.');
        }

        if (RateLimiter::increment('two-factor:correo:'.$user->getKey(), 60) > 1) {
            return __('Espera un minuto antes de pedir otro código.');
        }

        do {
            $codigo = (string) random_int(100000, 999999);
            $firma = $this->firmaCorreo($desafio['nonce'], $codigo);
        } while (hash_equals($desafio['correo_firma'] ?? '', $firma));

        try {
            Mail::raw("Tu código de acceso a Libro Guardia es {$codigo}. Vence en 5 minutos.", function ($mensaje) use ($user): void {
                $mensaje->to($user->email)->subject('Código de acceso a Libro Guardia');
            });
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return __('No se pudo enviar el código. Puedes volver a intentarlo en un minuto.');
        }

        $desafio['correo_firma'] = $firma;
        $desafio['correo_vence'] = now()->addMinutes(5)->timestamp;
        $request->session()->put(self::DESAFIO, $desafio);

        return null;
    }

    /**
     * Comprueba el código del desafío según el método elegido (app, correo o código de recuperación).
     * Un código de recuperación válido se consume.
     *
     * @param  array{nonce: string, correo_vence?: int, correo_firma?: string}  $desafio
     */
    public function verificarDesafio(User $user, string $metodo, #[\SensitiveParameter] string $codigo, array $desafio): bool
    {
        return match ($metodo) {
            'app' => $user->hasEnabledTwoFactorAuthentication() && preg_match('/^[0-9]{6}$/D', $codigo)
                && $this->verificarApp($user->two_factor_secret, $codigo),
            'correo' => $this->correoDisponible($user)
                && now()->timestamp < ($desafio['correo_vence'] ?? 0)
                && hash_equals($desafio['correo_firma'], $this->firmaCorreo($desafio['nonce'], $codigo)),
            'recuperacion' => $this->consumirCodigoRecuperacion($user, $codigo),
        };
    }

    private function consumirCodigoRecuperacion(User $user, #[\SensitiveParameter] string $codigo): bool
    {
        foreach ($user->two_factor_recovery_codes ? $user->recoveryCodes() : [] as $codigoGuardado) {
            if (hash_equals($codigoGuardado, $codigo)) {
                $user->replaceRecoveryCode($codigoGuardado);

                return true;
            }
        }

        return false;
    }

    private function firma(User $user): string
    {
        return hash('sha256', json_encode([
            $user->getKey(),
            $user->getAuthPassword(),
            $user->getRememberToken(),
            $user->email,
            $user->hasVerifiedEmail(),
            $user->two_factor_secret,
            (bool) $user->two_factor_email_enabled,
        ], JSON_THROW_ON_ERROR));
    }
}
