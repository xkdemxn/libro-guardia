{{-- Exportar el libro de guardia en CSV (US-11) o ADIF (US-12). --}}
<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto max-w-3xl">
            <a href="{{ route('contactos.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">&lsaquo; Volver al libro</a>
            <h1 class="mt-3 font-display text-3xl font-semibold text-white">Exportar mi libro</h1>
            <p class="mt-1 text-slate-400">Descarga un reporte en PDF, un respaldo de tus contactos o llévalos a otro programa de log.</p>
        </div>
    </x-slot>

    <div class="mx-auto box-content max-w-3xl px-4 pt-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('exportacion.store') }}" class="space-y-6" x-data="{ formato: @js(old('formato', 'adif')) }">
            @csrf

            <x-panel ceja="01 · Formato">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @foreach ([
                        'pdf' => ['Reporte PDF', 'Para imprimir o entregar: resumen por rango, banda y modo, y la tabla de contactos.'],
                        'adif' => ['ADIF (.adi)', 'El estándar de la radioafición. Para LoTW, QRZ, Club Log y otros programas de log.'],
                        'csv' => ['CSV (.csv)', 'Para abrir en Excel, LibreOffice o Google Sheets.'],
                    ] as $valor => [$titulo, $descripcion])
                        <label class="relative cursor-pointer rounded-2xl border border-white/10 bg-white/[0.02] p-5 transition-colors hover:bg-white/[0.05] has-[:checked]:border-acento-400/60 has-[:checked]:bg-acento-400/10 has-[:checked]:shadow-[0_0_0_4px_rgb(82_173_255/0.1)] has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-acento-400">
                            <input type="radio" name="formato" value="{{ $valor }}" x-model="formato" class="sr-only" @checked(old('formato', 'adif') === $valor)>
                            <span class="flex items-center justify-between">
                                <span class="font-display font-semibold text-white">{{ $titulo }}</span>
                                <span class="grid h-5 w-5 place-items-center rounded-full ring-1" :class="formato === '{{ $valor }}' ? 'bg-acento-500 ring-acento-400' : 'ring-white/20'">
                                    <span class="h-2 w-2 rounded-full bg-blanco" x-show="formato === '{{ $valor }}'"></span>
                                </span>
                            </span>
                            <span class="mt-2 block text-sm text-slate-400">{{ $descripcion }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('formato')" />
            </x-panel>

            <x-panel ceja="02 · Qué contactos (opcional)">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="desde" value="Desde" />
                        <x-text-input id="desde" name="desde" type="date" :value="old('desde')" />
                        <x-input-error :messages="$errors->get('desde')" />
                    </div>
                    <div>
                        <x-input-label for="hasta" value="Hasta" />
                        <x-text-input id="hasta" name="hasta" type="date" :value="old('hasta')" />
                        <x-input-error :messages="$errors->get('hasta')" />
                    </div>
                    <div>
                        <x-input-label for="banda_id" value="Banda" />
                        <select id="banda_id" name="banda_id" class="campo">
                            <option value="">Todas</option>
                            @foreach ($bandas as $banda)
                                <option value="{{ $banda->id }}" @selected((string) old('banda_id') === (string) $banda->id)>{{ $banda->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="modo_id" value="Modo" />
                        <select id="modo_id" name="modo_id" class="campo">
                            <option value="">Todos</option>
                            @foreach ($modos as $modo)
                                <option value="{{ $modo->id }}" @selected((string) old('modo_id') === (string) $modo->id)>{{ $modo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="qsl" value="Estado de QSL" />
                        <select id="qsl" name="qsl" class="campo">
                            <option value="">Todos</option>
                            <option value="confirmado" @selected(old('qsl') === 'confirmado')>Solo confirmados</option>
                            <option value="enviada" @selected(old('qsl') === 'enviada')>Solo enviadas</option>
                            <option value="pendiente" @selected(old('qsl') === 'pendiente')>Solo pendientes</option>
                        </select>
                    </div>
                </div>
                <p class="mt-4 text-xs text-slate-500">Si no eliges filtros se exporta el libro completo. Las horas siempre van en UTC.</p>
            </x-panel>

            <div class="flex justify-end">
                <x-primary-button class="!px-7 !py-3">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" /><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" /></svg>
                    Descargar archivo
                </x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
