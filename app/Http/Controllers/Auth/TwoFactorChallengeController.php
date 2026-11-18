<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SegundoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class TwoFactorChallengeController extends Controller
{
    public function __construct(private SegundoFactor $segundoFactor) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $desafio = $request->session()->get(SegundoFactor::DESAFIO);
        $user = $desafio ? User::find($desafio['usuario']) : null;

        if (! $desafio || ! $this->segundoFactor->vigente($user, $desafio)) {
            return redirect()->route('login');
        }

        return response()->view('auth.two-factor-challenge', [
            'appDisponible' => $user->hasEnabledTwoFactorAuthentication(),
            'correoDisponible' => $this->segundoFactor->correoDisponible($user),
            'correoEnviado' => now()->timestamp < ($desafio['correo_vence'] ?? 0),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function send(Request $request): RedirectResponse
    {
        $desafio = $request->session()->get(SegundoFactor::DESAFIO);
        $user = $desafio ? User::find($desafio['usuario']) : null;

        if (! $desafio || ! $this->segundoFactor->vigente($user, $desafio)) {
            return redirect()->route('login');
        }

        abort_unless($this->segundoFactor->correoDisponible($user), 403);

        if (! $this->segundoFactor->envioCorreoDisponible()) {
            return redirect()->route('two-factor.show')->withErrors(['codigo' => 'El envío de correo no está disponible. Usa la app o un código de recuperación.']);
        }

        $limite = 'two-factor:correo:'.$user->getKey();

        if (RateLimiter::increment($limite, 60) > 1) {
            return redirect()->route('two-factor.show')->withErrors(['codigo' => 'Espera un minuto antes de pedir otro código.']);
        }

        do {
            $codigo = (string) random_int(100000, 999999);
            $firma = $this->segundoFactor->firmaCorreo($desafio['nonce'], $codigo);
        } while (hash_equals($desafio['correo_firma'] ?? '', $firma));

        try {
            Mail::raw("Tu código de acceso a Libro Guardia es {$codigo}. Vence en 5 minutos.", function ($mensaje) use ($user): void {
                $mensaje->to($user->email)->subject('Código de acceso a Libro Guardia');
            });
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return redirect()->route('two-factor.show')->withErrors(['codigo' => 'No se pudo enviar el código. Puedes volver a intentarlo en un minuto.']);
        }

        $desafio['correo_firma'] = $firma;
        $desafio['correo_vence'] = now()->addMinutes(5)->timestamp;
        $request->session()->put(SegundoFactor::DESAFIO, $desafio);

        return redirect()->route('two-factor.show')->with('status', 'Enviamos un código a tu correo verificado.');
    }

    public function store(Request $request): RedirectResponse
    {
        $desafio = $request->session()->get(SegundoFactor::DESAFIO);

        if (! $desafio) {
            return redirect()->route('login');
        }

        $datos = $request->validate([
            'metodo' => ['required', 'in:app,correo,recuperacion'],
            'codigo' => ['required', 'string', 'max:40'],
        ]);

        $resultado = DB::transaction(function () use ($request, $desafio, $datos): User|RedirectResponse {
            $user = User::query()->lockForUpdate()->find($desafio['usuario']);

            if (! $this->segundoFactor->vigente($user, $desafio)) {
                $request->session()->forget(SegundoFactor::DESAFIO);

                return redirect()->route('login');
            }

            $limite = 'two-factor:verificar:'.$user->getKey();

            if (RateLimiter::tooManyAttempts($limite, 5)) {
                return redirect()->route('two-factor.show')->withErrors(['codigo' => 'Demasiados intentos. Espera cinco minutos.']);
            }

            if (! $this->verificar($user, $datos['metodo'], $datos['codigo'], $desafio)) {
                RateLimiter::hit($limite, 300);

                return redirect()->route('two-factor.show')->withErrors(['codigo' => 'El código es inválido o ha vencido.'])->withInput(['metodo' => $datos['metodo']]);
            }

            RateLimiter::clear($limite);

            return $user;
        });

        return $resultado instanceof User
            ? $this->segundoFactor->completar($request, $resultado, $desafio['recordar'])
            : $resultado;
    }

    /** @param array{nonce: string, correo_vence?: int, correo_firma?: string} $desafio */
    private function verificar(User $user, string $metodo, #[\SensitiveParameter] string $codigo, array $desafio): bool
    {
        return match ($metodo) {
            'app' => $user->hasEnabledTwoFactorAuthentication() && preg_match('/^[0-9]{6}$/D', $codigo)
                && $this->segundoFactor->verificarApp($user->two_factor_secret, $codigo),
            'correo' => $this->segundoFactor->correoDisponible($user)
                && now()->timestamp < ($desafio['correo_vence'] ?? 0)
                && hash_equals($desafio['correo_firma'], $this->segundoFactor->firmaCorreo($desafio['nonce'], $codigo)),
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
}
