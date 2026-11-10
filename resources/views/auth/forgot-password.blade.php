<x-guest-layout>
    <x-ceja class="mb-6">Recuperar acceso</x-ceja>
    <h2 class="font-display text-2xl font-semibold text-white">¿Olvidaste tu contraseña?</h2>
    <p class="mt-1.5 text-sm text-slate-400">Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva.</p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-primary-button class="w-full !py-3">Enviar enlace</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-400">
        <a class="font-medium text-acento-300 transition hover:text-acento-200" href="{{ route('login') }}">&lsaquo; Volver a iniciar sesión</a>
    </p>
</x-guest-layout>
