<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SegundoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

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

        $error = $this->segundoFactor->enviarCodigoCorreo($request, $user, $desafio);

        if ($error !== null) {
            return redirect()->route('two-factor.show')->withErrors(['codigo' => $error]);
        }

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

            if (! $this->segundoFactor->verificarDesafio($user, $datos['metodo'], $datos['codigo'], $desafio)) {
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
}
