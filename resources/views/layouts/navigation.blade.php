{{-- Barra superior flotante de vidrio. --}}
<nav x-data="{ open: false, desplazado: window.scrollY > 4 }" x-on:scroll.window.passive="desplazado = window.scrollY > 4" class="sticky top-0 z-40 px-3 pt-3 sm:px-6">
    {{-- Franja de desenfoque a todo el ancho: lo que pasa por debajo de la barra se ve borroso. --}}
    <div class="franja-desenfoque" x-bind:class="desplazado && 'visible'" aria-hidden="true"></div>
    <div class="vidrio-fuerte mx-auto max-w-7xl rounded-full px-3 py-2 sm:px-4">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <a href="{{ route('contactos.index') }}" class="flex items-center gap-2.5 rounded-full pe-2 text-white">
                    <x-emblema class="h-10 w-10" />
                    <span class="leading-tight whitespace-nowrap lg:hidden xl:block">
                        <span class="block font-display text-sm font-semibold tracking-tight">Libro de Guardia</span>
                        <span class="block text-[10px] font-medium uppercase tracking-[0.18em] text-amber-300/90">DX Ecuador</span>
                    </span>
                </a>

                {{-- Control segmentado --}}
                <div class="hidden items-center gap-1 rounded-full bg-black/25 p-1 ring-1 ring-white/5 lg:flex">
                    <x-nav-link :href="route('contactos.index')" :active="request()->routeIs('contactos.index')">
                        Libro
                    </x-nav-link>
                    <x-nav-link :href="route('contactos.create')" :active="request()->routeIs('contactos.create')">
                        Nuevo contacto
                    </x-nav-link>
                    <x-nav-link :href="route('estadisticas')" :active="request()->routeIs('estadisticas')">
                        Estadísticas
                    </x-nav-link>
                    <x-nav-link :href="route('perfil-publico.index')" :active="request()->routeIs('perfil-publico.*')">
                        Directorio
                    </x-nav-link>
                    <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                        Perfil
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden shrink-0 items-center gap-3 lg:flex">
                <x-reloj-utc />

                <x-selector-tema />

                {{-- Solicitudes de confirmación pendientes --}}
                <a href="{{ route('solicitudes.index') }}" class="relative grid h-9 w-9 place-items-center rounded-full text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10 hover:text-white {{ request()->routeIs('solicitudes.*') ? 'bg-white/10 text-white' : '' }}"
                   title="Solicitudes de confirmación" aria-label="Solicitudes de confirmación{{ $solicitudesPendientes ? ': '.$solicitudesPendientes.' pendientes' : '' }}">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 2a6 6 0 0 0-6 6c0 1.887-.454 3.665-1.257 5.234a.75.75 0 0 0 .515 1.076 32.91 32.91 0 0 0 3.256.508 3.5 3.5 0 0 0 6.972 0 32.903 32.903 0 0 0 3.256-.508.75.75 0 0 0 .515-1.076A11.448 11.448 0 0 1 16 8a6 6 0 0 0-6-6ZM8.05 14.943a33.54 33.54 0 0 0 3.9 0 2 2 0 0 1-3.9 0Z" clip-rule="evenodd" /></svg>
                    @if ($solicitudesPendientes)
                        <span class="absolute -right-1 -top-1 grid h-5 min-w-[1.25rem] place-items-center rounded-full bg-rose-500 px-1 text-[11px] font-bold text-blanco ring-2 ring-noche-900">{{ $solicitudesPendientes }}</span>
                    @endif
                </a>

                <x-dropdown align="right" width="w-72">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 whitespace-nowrap rounded-full bg-white/5 py-1 pe-3 ps-1 text-sm text-slate-200 ring-1 ring-white/10 transition hover:bg-white/10">
                            <span class="grid h-7 w-7 place-items-center overflow-hidden rounded-full bg-gradient-to-br from-marca-400 to-marca-600 text-[11px] font-semibold text-blanco">
                                @if (Auth::user()->foto_url)
                                    <img src="{{ Auth::user()->foto_url }}" alt="" class="h-full w-full object-cover">
                                @else
                                    {{ mb_strtoupper(mb_substr(Auth::user()->nombres, 0, 1).mb_substr(Auth::user()->apellidos, 0, 1)) }}
                                @endif
                            </span>
                            <span class="font-mono font-semibold">{{ Auth::user()->indicativo }}</span>
                            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="flex items-center gap-3 rounded-xl bg-white/[0.04] p-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-xl bg-gradient-to-br from-marca-400 to-marca-600 text-sm font-semibold text-blanco">
                                @if (Auth::user()->foto_url)
                                    <img src="{{ Auth::user()->foto_url }}" alt="" class="h-full w-full object-cover">
                                @else
                                    {{ mb_strtoupper(mb_substr(Auth::user()->nombres, 0, 1).mb_substr(Auth::user()->apellidos, 0, 1)) }}
                                @endif
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-white">{{ Auth::user()->nombre_completo }}</p>
                                <p class="truncate text-xs text-slate-400">{{ Auth::user()->email }}</p>
                            </div>
                        </div>

                        <p class="px-3 pb-1 pt-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Cuenta</p>
                        <x-dropdown-link :href="route('profile.edit').'#datos'">
                            <x-icono nombre="usuario" class="h-4 w-4 text-slate-400" /> Mi perfil
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('profile.edit').'#contrasena'">
                            <x-icono nombre="candado" class="h-4 w-4 text-slate-400" /> Cambiar contraseña
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('perfil-publico.show', Auth::user()->indicativo)">
                            <x-icono nombre="enlace-externo" class="h-4 w-4 text-slate-400" /> Ver mi perfil público
                        </x-dropdown-link>

                        <div class="mx-2 my-1.5 h-px bg-white/10"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="!text-rose-300 hover:!bg-rose-500/10">
                                <x-icono nombre="salir" class="h-4 w-4" /> Cerrar sesión
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Botón de menú en celular --}}
            <button @click="open = ! open" class="relative grid h-9 w-9 place-items-center rounded-full text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10 lg:hidden" :aria-expanded="open" aria-label="Abrir menú">
                <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                @if ($solicitudesPendientes)
                    <span class="absolute right-0 top-0 h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-noche-900"></span>
                @endif
            </button>
        </div>
    </div>

    {{-- Menú en celular: compacto, con accesos en cuadrícula en lugar de una lista larga --}}
    @php
        $accesos = [
            ['contactos.index', 'contactos.index', 'Libro', 'libro'],
            ['solicitudes.index', 'solicitudes.*', 'Solicitudes', 'campana'],
            ['estadisticas', 'estadisticas', 'Estadísticas', 'grafico'],
            ['perfil-publico.index', 'perfil-publico.*', 'Directorio', 'lupa'],
            ['importacion.create', 'importacion.*', 'Importar ADIF', 'importar'],
            ['exportacion.create', 'exportacion.*', 'Exportar', 'exportar'],
        ];
    @endphp
    <div x-show="open" x-cloak x-transition.origin.top class="vidrio-fuerte mx-auto mt-2 max-w-7xl rounded-3xl p-3 lg:hidden">
        {{-- Operador, hora y tema --}}
        <div class="flex items-center justify-between gap-3 px-1">
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-xl bg-gradient-to-br from-marca-400 to-marca-600 text-xs font-semibold text-blanco">
                    @if (Auth::user()->foto_url)
                        <img src="{{ Auth::user()->foto_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        {{ mb_strtoupper(mb_substr(Auth::user()->nombres, 0, 1).mb_substr(Auth::user()->apellidos, 0, 1)) }}
                    @endif
                </span>
                <div class="min-w-0 leading-tight">
                    <p class="font-mono text-sm font-semibold text-white">{{ Auth::user()->indicativo }}</p>
                    <p class="truncate text-xs text-slate-400">{{ Auth::user()->nombre_completo }}</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-1.5">
                <x-reloj-utc />
                <x-selector-tema />
            </div>
        </div>

        {{-- Acción principal --}}
        <a href="{{ route('contactos.create') }}" class="mt-3 flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-b from-marca-400 to-marca-600 py-3 text-sm font-semibold text-blanco shadow-boton">
            <x-icono nombre="mas" class="h-4 w-4" /> Nuevo contacto
        </a>

        {{-- Accesos --}}
        <nav class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3" aria-label="Secciones">
            @foreach ($accesos as [$ruta, $patron, $titulo, $icono])
                @php($activo = request()->routeIs($patron))
                <a href="{{ route($ruta) }}" @if ($activo) aria-current="page" @endif
                   @class([
                       'relative flex items-center gap-2.5 rounded-2xl px-3 py-2.5 text-sm font-medium ring-1 transition',
                       'bg-acento-400/15 text-white ring-acento-400/30' => $activo,
                       'bg-white/[0.03] text-slate-300 ring-white/5 active:bg-white/10' => ! $activo,
                   ])>
                    <x-icono :nombre="$icono" @class(['h-[18px] w-[18px]', 'text-acento-300' => $activo, 'text-slate-500' => ! $activo]) />
                    <span class="truncate">{{ $titulo }}</span>
                    @if ($ruta === 'solicitudes.index' && $solicitudesPendientes)
                        <span class="ms-auto grid h-5 min-w-[1.25rem] place-items-center rounded-full bg-rose-500 px-1 text-[11px] font-bold text-blanco">{{ $solicitudesPendientes }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        {{-- Cuenta --}}
        <div class="mt-3 grid grid-cols-2 gap-2 border-t border-white/10 pt-3">
            <a href="{{ route('profile.edit') }}" @class([
                'flex items-center justify-center gap-2 rounded-2xl py-2.5 text-sm font-medium transition',
                'bg-white/10 text-white' => request()->routeIs('profile.*'),
                'text-slate-300 active:bg-white/10' => ! request()->routeIs('profile.*'),
            ])>
                <x-icono nombre="usuario" class="h-4 w-4" /> Mi perfil
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl py-2.5 text-sm font-medium text-rose-300 transition active:bg-rose-500/10">
                    <x-icono nombre="salir" class="h-4 w-4" /> Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</nav>
