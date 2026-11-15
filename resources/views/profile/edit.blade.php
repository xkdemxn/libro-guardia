{{--
    Mi perfil: menú lateral con secciones; solo se ve una a la vez para que la página no sea un solo scroll largo.
    La sección inicial viene del servidor (errores o la sección donde se guardó) o del #hash de la URL.
--}}
@php
    $secciones = [
        'datos' => ['Datos personales', 'Nombre, correo, país y foto', 'usuario'],
        'estacion' => ['Mi estación', 'Grid, equipo y antena', 'antena'],
        'portada' => ['Portada', 'Fondo de tu perfil público', 'imagen'],
        'tarjeta' => ['Tarjeta QSL', 'Foto de tus tarjetas', 'tarjeta'],
        'privacidad' => ['Privacidad', 'Lo que ven los demás', 'ojo'],
        'contrasena' => ['Contraseña', 'Cambia tu clave de acceso', 'candado'],
        'cuenta' => ['Eliminar cuenta', 'Borrar la cuenta y su libro', 'papelera'],
    ];

    $seccionInicial = match (true) {
        $errors->updatePassword->isNotEmpty() => 'contrasena',
        $errors->userDeletion->isNotEmpty() => 'cuenta',
        $errors->hasAny(['foto', 'nombres', 'apellidos', 'email', 'pais', 'ciudad']) => 'datos',
        $errors->hasAny(['grid_locator', 'equipo_radio', 'antena', 'biografia']) => 'estacion',
        $errors->hasAny(['portada', 'portada_estilo', 'portada_opacidad']) => 'portada',
        default => old('seccion', session('seccion')),
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto max-w-6xl">
            <p class="text-[11px] font-medium uppercase tracking-[0.28em] text-slate-500">Cuenta</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-white sm:text-4xl">Mi perfil</h1>
        </div>
    </x-slot>

    <div class="mx-auto box-content max-w-6xl px-4 pt-8 sm:px-6 lg:px-8"
         x-data="seccionesPerfil(@js(array_keys($secciones)), @js($seccionInicial))">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[17rem_minmax(0,1fr)] lg:items-start">

            {{-- Menú lateral (en celular, fila de pestañas deslizable) --}}
            <aside class="min-w-0 lg:sticky lg:top-28">
                <div class="vidrio hidden p-5 lg:block">
                    <div class="flex items-center gap-3">
                        <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-2xl bg-gradient-to-br from-marca-400 to-marca-600 font-display text-sm font-semibold text-blanco ring-1 ring-white/10">
                            @if ($user->foto_url)
                                <img src="{{ $user->foto_url }}" alt="" class="h-full w-full object-cover">
                            @else
                                {{ mb_strtoupper(mb_substr($user->nombres, 0, 1).mb_substr($user->apellidos, 0, 1)) }}
                            @endif
                        </span>
                        <div class="min-w-0">
                            <p class="flex items-center gap-2 font-mono text-lg font-semibold text-white">
                                {{ $user->indicativo }}
                                <x-bandera :codigo="$user->codigoBandera()" :pais="$user->pais" />
                            </p>
                            <p class="truncate text-sm text-slate-400">{{ $user->nombre_completo }}</p>
                        </div>
                    </div>
                    <div class="my-4 h-px bg-white/10"></div>

                    <nav class="space-y-1" aria-label="Secciones del perfil">
                        @foreach ($secciones as $clave => [$titulo, $detalle, $icono])
                            <button type="button" x-on:click="ir(@js($clave))"
                                    :aria-current="seccion === @js($clave) ? 'page' : null"
                                    class="group flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-left transition"
                                    :class="seccion === @js($clave)
                                        ? '{{ $clave === 'cuenta' ? 'bg-rose-500/10 ring-1 ring-rose-400/25' : 'bg-acento-400/10 ring-1 ring-acento-400/25' }}'
                                        : 'hover:bg-white/5'">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl transition"
                                      :class="seccion === @js($clave)
                                        ? '{{ $clave === 'cuenta' ? 'bg-rose-500 text-blanco' : 'bg-gradient-to-b from-marca-400 to-marca-600 text-blanco shadow-boton' }}'
                                        : '{{ $clave === 'cuenta' ? 'bg-rose-500/10 text-rose-300' : 'bg-white/5 text-slate-400 group-hover:text-slate-200' }}'">
                                    <x-icono :nombre="$icono" class="h-[18px] w-[18px]" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium {{ $clave === 'cuenta' ? 'text-rose-300' : 'text-slate-100' }}">{{ $titulo }}</span>
                                    <span class="block truncate text-xs text-slate-500">{{ $detalle }}</span>
                                </span>
                            </button>
                        @endforeach
                    </nav>

                    <div class="my-4 h-px bg-white/10"></div>
                    <a href="{{ route('perfil-publico.show', $user->indicativo) }}" target="_blank"
                       class="flex items-center justify-between rounded-2xl px-3 py-2 text-sm text-slate-300 transition hover:bg-white/5 hover:text-white">
                        Ver mi perfil público
                        <x-icono nombre="enlace-externo" class="h-4 w-4 text-slate-500" />
                    </a>
                </div>

                <nav class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 lg:hidden" aria-label="Secciones del perfil">
                    @foreach ($secciones as $clave => [$titulo, $detalle, $icono])
                        <button type="button" x-on:click="ir(@js($clave))"
                                class="flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-medium ring-1 transition"
                                :class="seccion === @js($clave)
                                    ? 'bg-gradient-to-b from-marca-400 to-marca-600 text-blanco ring-transparent shadow-boton'
                                    : 'bg-white/5 text-slate-300 ring-white/10'">
                            <x-icono :nombre="$icono" class="h-4 w-4" />
                            {{ $titulo }}
                        </button>
                    @endforeach
                </nav>
            </aside>

            {{-- Contenido de la sección elegida --}}
            <div class="min-w-0">
                @include('profile.partials.update-profile-information-form')

                <x-panel x-show="seccion === 'contrasena'" x-cloak class="sm:!p-8">
                    @include('profile.partials.update-password-form')
                </x-panel>

                <x-panel x-show="seccion === 'cuenta'" x-cloak class="sm:!p-8">
                    @include('profile.partials.delete-user-form')
                </x-panel>
            </div>
        </div>
    </div>
</x-app-layout>
