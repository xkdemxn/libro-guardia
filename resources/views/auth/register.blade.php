<x-guest-layout>
    <x-ceja class="mb-6">Nuevo operador</x-ceja>
    <h2 class="font-display text-2xl font-semibold text-white">Crear cuenta</h2>
    <p class="mt-1.5 text-sm text-slate-400">Regístrate con tu indicativo para tener tu propio libro de guardia.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="indicativo" value="Indicativo" />
            <x-text-input id="indicativo" class="font-mono text-lg uppercase tracking-wider" type="text" name="indicativo" :value="old('indicativo')" required autofocus placeholder="HC1QRC" maxlength="20" />
            <x-input-error :messages="$errors->get('indicativo')" />
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="nombres" value="Nombres" />
                <x-text-input id="nombres" type="text" name="nombres" :value="old('nombres')" required autocomplete="given-name" />
                <x-input-error :messages="$errors->get('nombres')" />
            </div>
            <div>
                <x-input-label for="apellidos" value="Apellidos" />
                <x-text-input id="apellidos" type="text" name="apellidos" :value="old('apellidos')" required autocomplete="family-name" />
                <x-input-error :messages="$errors->get('apellidos')" />
            </div>
        </div>

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="password" value="Contraseña" />
                <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Confirmar" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" />
            </div>
        </div>
        <p class="-mt-2 text-xs text-slate-500">Mínimo 8 caracteres, con letras y números.</p>

        <x-primary-button class="w-full !py-3">Crear mi cuenta</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-400">
        ¿Ya tienes cuenta?
        <a class="font-medium text-acento-300 transition hover:text-acento-200" href="{{ route('login') }}">Inicia sesión</a>
    </p>
</x-guest-layout>
