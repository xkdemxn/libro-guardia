<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

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
        $transporte = config('mail.mailers.'.config('mail.default').'.transport');

        return match ($transporte) {
            'log', null => false,
            'array' => app()->environment('testing'),
            'brevo' => filled(config('services.brevo.key'))
                && filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false,
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

    public function completar(Request $request, User $user, bool $recordar): RedirectResponse
    {
        $request->session()->forget(self::DESAFIO);
        Auth::guard('web')->login($user, $recordar);
        $request->session()->regenerate();

        return redirect()->intended(route('contactos.index', absolute: false));
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
