<section class="space-y-6">
    <header>
        <h2 class="text-lg font-semibold text-white">Verificación en dos pasos</h2>
        <p class="mt-1 text-sm text-slate-400">Elige qué métodos podrás usar después de ingresar tu contraseña.</p>
    </header>

    @if (session('seccion') === 'seguridad' && session('status'))
        <p role="status" class="text-sm text-emerald-400">{{ session('status') }}</p>
    @endif

    <x-input-error :messages="$errors->segundoFactor->all()" />

    @if ($codigosRecuperacion)
        <div class="space-y-2">
            <h3 class="font-medium text-white">Códigos de recuperación de un solo uso</h3>
            <p class="text-sm text-slate-400">Guárdalos ahora en un lugar seguro. No volverán a mostrarse y te permitirán entrar si pierdes acceso a tus métodos.</p>
            <ul class="grid gap-1 font-mono text-sm text-slate-200 sm:grid-cols-2">
                @foreach ($codigosRecuperacion as $codigo)
                    <li>{{ $codigo }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-2">
        <h3 class="font-medium text-white">App autenticadora</h3>
        <p class="text-sm text-slate-400">Configura el QR con Google Authenticator, Microsoft Authenticator u otra app compatible. Después de tu contraseña, usarás el código de la app para entrar.</p>
        <p class="text-sm text-slate-400">
            {{ $user->hasEnabledTwoFactorAuthentication() ? 'Activa' : 'Desactivada' }}
        </p>

        @if ($qrSegundoFactor)
            <p class="text-sm text-slate-300">Escanea el QR con tu app autenticadora e ingresa el código de seis dígitos que aparece en la app.</p>
            <div class="inline-block bg-white p-4" role="img" aria-label="QR para configurar la app autenticadora">
                {!! $qrSegundoFactor !!}
            </div>
            <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-3">
                @csrf
                <x-input-label for="configuracion-codigo" value="Código de la app" />
                <x-text-input id="configuracion-codigo" name="codigo" inputmode="numeric" autocomplete="one-time-code"
                    maxlength="6" pattern="[0-9]{6}" required />
                <x-primary-button>Activar app</x-primary-button>
            </form>
        @endif
    </div>

    <div class="space-y-2">
        <h3 class="font-medium text-white">Código por correo</h3>
        <p class="text-sm text-slate-400">
            {{ $user->two_factor_email_enabled ? 'Activo' : 'Desactivado' }} · {{ $user->email }}
        </p>

        @if (! $envioCorreoDisponible)
            <p class="text-sm text-slate-400">El envío de correo todavía no está configurado.</p>
        @elseif (! $user->hasVerifiedEmail())
            <a href="{{ route('verification.notice') }}" class="text-sm text-acento-300 underline">Verificar mi correo</a>
        @endif
    </div>

    <form method="POST" action="{{ route('two-factor.settings') }}" class="space-y-3">
        @csrf
        <x-input-label for="seguridad-password" value="Contraseña actual" />
        <x-text-input id="seguridad-password" name="password" type="password" autocomplete="current-password" required />

        <div class="flex flex-wrap gap-2">
            @if ($user->hasEnabledTwoFactorAuthentication())
                <x-secondary-button type="submit" name="accion" value="desactivar-app">Desactivar app</x-secondary-button>
            @else
                <x-secondary-button type="submit" name="accion" value="preparar-app">
                    {{ $qrSegundoFactor ? 'Generar otro QR' : 'Configurar QR de autenticación' }}
                </x-secondary-button>
            @endif

            @if ($user->two_factor_email_enabled)
                <x-secondary-button type="submit" name="accion" value="desactivar-correo">Desactivar correo</x-secondary-button>
            @elseif ($user->hasVerifiedEmail() && $envioCorreoDisponible)
                <x-secondary-button type="submit" name="accion" value="activar-correo">Activar correo</x-secondary-button>
            @endif

            @if ($user->hasEnabledTwoFactorAuthentication() || $user->two_factor_email_enabled)
                <x-secondary-button type="submit" name="accion" value="recuperacion">Renovar códigos de recuperación</x-secondary-button>
            @endif
        </div>
    </form>
</section>
