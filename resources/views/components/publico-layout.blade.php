{{-- Layout de las páginas públicas (perfil por indicativo): funciona con o sin sesión. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="font-sans">
        <div class="fondo-noche" aria-hidden="true"><span></span></div>

        <div class="min-h-screen pb-16">
            @auth
                @include('layouts.navigation')
            @else
                <nav class="sticky top-0 z-40 px-3 pt-3 sm:px-6">
                    <div class="vidrio-fuerte mx-auto flex max-w-7xl items-center justify-between rounded-full px-3 py-2 sm:px-4">
                        <a href="{{ route('perfil-publico.index') }}" class="flex items-center gap-2.5 text-white">
                            <span class="grid h-9 w-9 place-items-center rounded-full bg-gradient-to-b from-acento-400 to-acento-600 shadow-boton">
                                <x-application-logo class="h-5 w-5 text-white" />
                            </span>
                            <span class="font-display text-sm font-semibold">Libro de Guardia</span>
                        </a>
                        <div class="flex items-center gap-2">
                            <x-reloj-utc class="hidden sm:inline-flex" />
                            <a href="{{ route('login') }}" class="rounded-full bg-white/10 px-4 py-1.5 text-sm font-semibold text-white ring-1 ring-white/10 transition hover:bg-white/15">Iniciar sesión</a>
                        </div>
                    </div>
                </nav>
            @endauth

            <main class="mx-auto box-content max-w-3xl px-4 pt-10 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
