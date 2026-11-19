<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegistroRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Registra un nuevo operador (US-01).
     */
    public function store(RegistroRequest $request): RedirectResponse
    {
        $user = $request->crearUsuario();

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('contactos.index', absolute: false));
    }
}
