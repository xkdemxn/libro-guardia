{{-- Vista previa de la tarjeta QSL con descarga en PNG o SVG. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto max-w-5xl">
            <a href="{{ route('contactos.show', $contacto) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">&lsaquo; Volver al contacto</a>
            <h1 class="mt-3 font-display text-3xl font-semibold text-white">Tarjeta QSL</h1>
            <p class="mt-1 text-slate-400">Envíasela a <span class="font-mono text-slate-200">{{ $contacto->estacion_llamada }}</span> para confirmar el contacto, como las tarjetas postales de antes.</p>
        </div>
    </x-slot>

    <div class="mx-auto box-content max-w-5xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8"
         x-data="tarjetaQsl(@js($nombreArchivo))">
        <div x-ref="tarjeta" class="overflow-hidden rounded-[1.6rem] shadow-vidrio-lg ring-1 ring-white/10 [&>svg]:h-auto [&>svg]:w-full">
            @include('tarjeta-qsl.svg')
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm text-slate-500">
                Tus datos salen de <a href="{{ route('profile.edit') }}" class="text-acento-300 hover:text-acento-200">tu perfil</a>: agrega ciudad, grid locator, equipo y antena para una tarjeta más completa.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('contactos.tarjeta.svg', $contacto) }}" class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-semibold text-slate-100 transition hover:bg-white/10">
                    Descargar SVG
                </a>
                <x-primary-button type="button" x-on:click="descargarPng()" x-bind:disabled="generando">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" /><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" /></svg>
                    <span x-text="generando ? 'Generando…' : 'Descargar PNG'">Descargar PNG</span>
                </x-primary-button>
            </div>
        </div>
        <p x-show="error" x-cloak role="alert" class="text-right text-sm text-rose-400" x-text="error"></p>
    </div>
</x-app-layout>
