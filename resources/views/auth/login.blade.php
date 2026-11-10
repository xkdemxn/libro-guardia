<x-guest-layout>
    <x-ceja class="mb-6">Acceso de operadores</x-ceja>
    <h2 class="font-display text-2xl font-semibold text-white">Iniciar sesión</h2>
    <p class="mt-1.5 text-sm text-slate-400">Entra a tu libro de guardia.</p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="login" value="Indicativo o correo" />
            <x-text-input id="login" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" placeholder="HC1QRC" />
            <x-input-error :messages="$errors->get('login')" />
        </div>

        <div>
            <div class="flex items-baseline justify-between">
                <x-input-label for="password" value="Contraseña" />
                @if (Route::has('password.request'))
                    <a class="text-[13px] text-acento-300 transition hover:text-acento-200" href="{{ route('password.request') }}">¿La olvidaste?</a>
                @endif
            </div>
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label for="remember_me" class="flex items-center gap-2.5 text-sm text-slate-400">
            <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded border-white/20 bg-white/5 text-acento-500 focus:ring-acento-400/30 focus:ring-offset-0">
            Recordarme en este equipo
        </label>

        <x-primary-button class="w-full !py-3">Entrar</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-400">
        ¿No tienes cuenta?
        <a class="font-medium text-acento-300 transition hover:text-acento-200" href="{{ route('register') }}">Regístrate con tu indicativo</a>
    </p>
</x-guest-layout>
