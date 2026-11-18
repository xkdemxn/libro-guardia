<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\SegundoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, SegundoFactor $segundoFactor): RedirectResponse
    {
        $request->session()->forget(SegundoFactor::DESAFIO);
        $validado = $request->validarCredenciales();

        return DB::transaction(function () use ($request, $validado, $segundoFactor): RedirectResponse {
            $user = User::query()->lockForUpdate()->find($validado->getKey());

            if (! $user || ! hash_equals($validado->getAuthPassword(), $user->getAuthPassword())) {
                throw ValidationException::withMessages(['login' => 'Indicativo o contraseña incorrectos.']);
            }

            if (config('hashing.rehash_on_login', true)) {
                Auth::guard('web')->getProvider()->rehashPasswordIfRequired($user, $request->credenciales());
            }

            if ($segundoFactor->activo($user)) {
                $segundoFactor->iniciar($request, $user);

                return redirect()->route('two-factor.show');
            }

            return $segundoFactor->completar($request, $user, $request->boolean('remember'));
        });
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
