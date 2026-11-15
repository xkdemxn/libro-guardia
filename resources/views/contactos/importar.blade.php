{{-- Importar contactos desde ADIF (US-14): subir el archivo y ver el resumen de la última importación. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto max-w-3xl">
            <a href="{{ route('contactos.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">&lsaquo; Volver al libro</a>
            <h1 class="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">Importar contactos</h1>
            <p class="mt-1 text-slate-400">Trae tus contactos antiguos desde otro programa de log con un archivo ADIF.</p>
        </div>
    </x-slot>

    <div class="mx-auto box-content max-w-3xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        @if ($resultado)
            <x-panel ceja="Resumen de la importación">
                <div class="grid grid-cols-3 gap-4 text-center">
                    @foreach ([
                        ['Importados', $resultado['importados'], 'bg-emerald-400'],
                        ['Omitidos (duplicados)', $resultado['omitidos'], 'bg-amber-400'],
                        ['Con errores', $resultado['fallidos'], 'bg-rose-400'],
                    ] as [$etiqueta, $valor, $punto])
                        <div class="rounded-2xl bg-black/20 p-4 ring-1 ring-white/5">
                            <p class="font-display text-3xl font-semibold tabular-nums text-white">{{ $valor }}</p>
                            <p class="mt-1 inline-flex items-center gap-2 text-xs text-slate-400"><span class="punto {{ $punto }}"></span>{{ $etiqueta }}</p>
                        </div>
                    @endforeach
                </div>

                @if ($resultado['errores'] !== [])
                    <div class="mt-6">
                        <p class="mb-3 text-sm font-medium text-slate-300">Registros que no se importaron</p>
                        <ul class="space-y-2 text-sm">
                            @foreach ($resultado['errores'] as $fila)
                                <li class="rounded-xl bg-rose-500/[0.06] px-4 py-2.5 ring-1 ring-rose-400/15">
                                    <span class="font-mono text-slate-400">#{{ $fila['numero'] }}</span>
                                    <span class="font-mono font-semibold text-white">{{ $fila['indicativo'] ?: '—' }}</span>
                                    <span class="text-rose-300">· {{ implode(' ', $fila['errores']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-6 flex justify-end">
                    <a href="{{ route('contactos.index') }}" class="text-sm font-medium text-acento-300 hover:text-acento-200">Ver mi libro &rsaquo;</a>
                </div>
            </x-panel>
        @endif

        <x-panel ceja="Subir archivo ADIF">
            <form method="POST" action="{{ route('importacion.store') }}" enctype="multipart/form-data"
                  x-data="{ nombre: '' }" class="space-y-5">
                @csrf
                <label class="flex cursor-pointer flex-col items-center gap-3 rounded-3xl border-2 border-dashed border-white/15 bg-white/[0.02] px-6 py-12 text-center transition hover:border-acento-400/50 hover:bg-acento-400/[0.04]">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-acento-400/10 text-acento-300 ring-1 ring-acento-400/25">
                        <svg class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.25 13.25a.75.75 0 0 0 1.5 0V4.636l2.955 3.129a.75.75 0 0 0 1.09-1.03l-4.25-4.5a.75.75 0 0 0-1.09 0l-4.25 4.5a.75.75 0 1 0 1.09 1.03L9.25 4.636v8.614Z" /><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" /></svg>
                    </span>
                    <span class="font-medium text-white" x-text="nombre || 'Elige tu archivo .adi o .adif'"></span>
                    <span class="text-xs text-slate-500">Máximo 5 MB. Antes de importar verás una vista previa.</span>
                    <input type="file" name="archivo" accept=".adi,.adif" required class="sr-only" x-on:change="nombre = $event.target.files[0]?.name ?? ''">
                </label>
                <x-input-error :messages="$errors->get('archivo')" />

                <ul class="space-y-1.5 text-xs text-slate-500">
                    <li class="flex gap-2"><span class="punto mt-1.5 bg-slate-500"></span>Se aplican las mismas reglas que al registrar un contacto: frecuencia dentro de una banda y reporte según el modo.</li>
                    <li class="flex gap-2"><span class="punto mt-1.5 bg-slate-500"></span>Los contactos que ya están en tu libro se omiten como duplicados.</li>
                    <li class="flex gap-2"><span class="punto mt-1.5 bg-slate-500"></span>Las horas se conservan en UTC, tal como vienen en el archivo.</li>
                </ul>

                <div class="flex justify-end">
                    <x-primary-button>Ver vista previa</x-primary-button>
                </div>
            </form>
        </x-panel>
    </div>
</x-app-layout>
