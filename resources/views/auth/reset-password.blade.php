<x-guest-layout>
    <x-ceja class="mb-6">Recuperar acceso</x-ceja>
    <h2 class="font-display text-2xl font-semibold text-white">Nueva contraseña</h2>
    <p class="mt-1.5 text-sm text-slate-400">Elige una contraseña nueva para tu cuenta.</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="Nueva contraseña" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <p class="mt-1.5 text-xs text-slate-500">Mínimo 8 caracteres, con letras y números.</p>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button class="w-full !py-3">Guardar contraseña</x-primary-button>
    </form>
</x-guest-layout>
