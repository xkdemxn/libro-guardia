<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="font-sans">
        <div class="fondo-noche" aria-hidden="true"><span></span></div>

        <div class="mx-auto grid min-h-screen max-w-6xl items-center gap-12 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:gap-16">
            {{-- Presentación --}}
            <div class="hidden lg:block">
                <a href="/" class="inline-flex items-center gap-3 text-white">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-b from-acento-400 to-acento-600 shadow-boton">
                        <x-application-logo class="h-6 w-6 text-white" />
                    </span>
                    <span class="font-display text-lg font-semibold">Libro de Guardia</span>
                </a>

                <h1 class="mt-10 font-display text-4xl font-semibold leading-tight text-white xl:text-5xl">
                    Cada contacto,<br>
                    <span class="bg-gradient-to-r from-acento-300 to-sky-200 bg-clip-text text-transparent">en su lugar.</span>
                </h1>
                <p class="mt-5 max-w-md text-lg leading-relaxed text-slate-400">
                    Registra, busca y confirma tus QSO. La banda y el país se calculan solos, y todo queda en hora UTC.
                </p>

                <x-reloj-utc grande class="mt-8" />

                {{-- QSO de ejemplo --}}
                <div class="vidrio mt-10 max-w-md p-5">
                    <x-ceja class="mb-4">QSO de ejemplo</x-ceja>
                    <div class="flex items-center justify-between">
                        <p class="font-display text-2xl font-semibold text-white">LU4BB</p>
                        <p class="font-mono text-xs text-slate-500">01 / 05</p>
                    </div>
                    <p class="mt-1 text-sm text-slate-400">Argentina · 20 m · USB · 59 / 59</p>
                    <div class="mt-4 flex items-center justify-between font-mono text-sm">
                        <span class="text-acento-300">14.225 MHz</span>
                        <span class="text-slate-500">12:15 → 12:20 UTC</span>
                    </div>
                    <div class="mt-5 flex justify-center gap-1.5" aria-hidden="true">
                        <span class="punto bg-white"></span>
                        <span class="punto bg-slate-500"></span>
                        <span class="punto bg-rose-400"></span>
                        <span class="punto bg-amber-400"></span>
                        <span class="punto bg-emerald-400"></span>
                    </div>
                </div>
            </div>

            {{-- Formulario --}}
            <div class="mx-auto w-full max-w-md">
                <a href="/" class="mb-8 flex items-center justify-center gap-3 text-white lg:hidden">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-b from-acento-400 to-acento-600 shadow-boton">
                        <x-application-logo class="h-6 w-6 text-white" />
                    </span>
                    <span class="font-display text-lg font-semibold">Libro de Guardia</span>
                </a>

                <div class="vidrio p-6 sm:p-8">
                    {{ $slot }}
                </div>

                <div class="mt-6 flex justify-center lg:hidden">
                    <x-reloj-utc />
                </div>
            </div>
        </div>
    </body>
</html>
