{{-- Barra superior flotante de vidrio. --}}
<nav x-data="{ open: false }" class="sticky top-0 z-40 px-3 pt-3 sm:px-6">
    <div class="vidrio-fuerte mx-auto max-w-7xl rounded-full px-3 py-2 sm:px-4">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <a href="{{ route('contactos.index') }}" class="flex items-center gap-2.5 rounded-full pe-2 text-white">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-gradient-to-b from-acento-400 to-acento-600 shadow-boton">
                        <x-application-logo class="h-5 w-5 text-white" />
                    </span>
                    <span class="hidden font-display text-sm font-semibold tracking-tight md:block">Libro de Guardia</span>
                </a>

                {{-- Control segmentado --}}
                <div class="hidden items-center gap-1 rounded-full bg-black/25 p-1 ring-1 ring-white/5 sm:flex">
                    <x-nav-link :href="route('contactos.index')" :active="request()->routeIs('contactos.index')">
                        Libro
                    </x-nav-link>
                    <x-nav-link :href="route('contactos.create')" :active="request()->routeIs('contactos.create')">
                        Nuevo contacto
                    </x-nav-link>
                    <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                        Perfil
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden items-center gap-3 sm:flex">
                <x-reloj-utc />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-full bg-white/5 py-1 pe-3 ps-1 text-sm text-slate-200 ring-1 ring-white/10 transition hover:bg-white/10">
                            <span class="grid h-7 w-7 place-items-center rounded-full bg-gradient-to-br from-slate-500 to-slate-700 text-[11px] font-semibold text-white">
                                {{ mb_substr(Auth::user()->nombres, 0, 1) }}{{ mb_substr(Auth::user()->apellidos, 0, 1) }}
                            </span>
                            <span class="font-mono font-semibold">{{ Auth::user()->indicativo }}</span>
                            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-3 pb-2 pt-1.5">
                            <p class="text-sm font-medium text-white">{{ Auth::user()->nombre_completo }}</p>
                            <p class="truncate text-xs text-slate-400">{{ Auth::user()->email }}</p>
                        </div>
                        <div class="my-1 h-px bg-white/10"></div>
                        <x-dropdown-link :href="route('profile.edit')">Mi perfil</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="!text-rose-300">
                                Cerrar sesión
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Botón de menú en celular --}}
            <button @click="open = ! open" class="grid h-9 w-9 place-items-center rounded-full text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10 sm:hidden" :aria-expanded="open" aria-label="Abrir menú">
                <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Menú en celular --}}
    <div x-show="open" x-cloak x-transition.origin.top class="vidrio-fuerte mx-auto mt-2 max-w-7xl rounded-3xl p-3 sm:hidden">
        <div class="mb-3 flex items-center justify-between px-2">
            <div>
                <p class="font-mono font-semibold text-white">{{ Auth::user()->indicativo }}</p>
                <p class="text-xs text-slate-400">{{ Auth::user()->nombre_completo }}</p>
            </div>
            <x-reloj-utc />
        </div>
        <div class="space-y-1">
            <x-responsive-nav-link :href="route('contactos.index')" :active="request()->routeIs('contactos.index')">Libro de guardia</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('contactos.create')" :active="request()->routeIs('contactos.create')">Nuevo contacto</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">Mi perfil</x-responsive-nav-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="!text-rose-300">
                    Cerrar sesión
                </x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>
