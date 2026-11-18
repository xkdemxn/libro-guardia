<x-guest-layout>
    <div class="space-y-5">
        <header>
            <h1 class="text-xl font-semibold text-white">Verificación en dos pasos</h1>
            <p class="mt-1 text-sm text-slate-400">Confirma tu identidad para acceder a tu bitácora.</p>
        </header>

        @if (session('status'))
            <p role="status" class="text-sm text-emerald-400">{{ session('status') }}</p>
        @endif

        <x-input-error :messages="$errors->all()" />

        @if ($correoDisponible)
            <form method="POST" action="{{ route('two-factor.send') }}">
                @csrf
                <x-secondary-button type="submit">
                    {{ $correoEnviado ? 'Reenviar código por correo' : 'Enviar un código a mi correo' }}
                </x-secondary-button>
            </form>
        @endif

        <form method="POST" action="{{ route('two-factor.store') }}" class="space-y-4">
            @csrf
            <div>
                <x-input-label for="metodo" value="Método" />
                <select id="metodo" name="metodo" class="mt-1 w-full rounded-md border-white/10 bg-slate-950 text-slate-100">
                    @if ($appDisponible)
                        <option value="app" @selected(old('metodo', session('status') ? 'correo' : 'app') === 'app')>App autenticadora</option>
                    @endif
                    @if ($correoDisponible && $correoEnviado)
                        <option value="correo" @selected(old('metodo', session('status') ? 'correo' : '') === 'correo')>Código por correo</option>
                    @endif
                    <option value="recuperacion" @selected(old('metodo') === 'recuperacion')>Código de recuperación</option>
                </select>
            </div>

            <div>
                <x-input-label for="codigo" value="Código" />
                <x-text-input id="codigo" name="codigo" class="mt-1 block w-full"
                    autocomplete="one-time-code" maxlength="40" required />
            </div>

            <x-primary-button>Verificar</x-primary-button>
        </form>

        <a href="{{ route('login') }}" class="text-sm text-acento-300 underline">Volver al inicio de sesión</a>
    </div>
</x-guest-layout>
