{{-- Vista previa de la importación ADIF (US-14): qué se importa, qué se omite y qué tiene errores. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto max-w-5xl">
            <a href="{{ route('importacion.create') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">&lsaquo; Elegir otro archivo</a>
            <h1 class="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">Vista previa</h1>
            <p class="mt-1 font-mono text-sm text-slate-400">{{ $nombreArchivo }} · {{ $totalFilas }} {{ $totalFilas === 1 ? 'registro' : 'registros' }}</p>
        </div>
    </x-slot>

    <div class="mx-auto box-content max-w-5xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-3 gap-4">
            @foreach ([
                ['Se importarán', $nuevos, 'bg-emerald-400 shadow-[0_0_8px_rgb(52_211_153)]'],
                ['Duplicados', $duplicados, 'bg-amber-400'],
                ['Con errores', $errores, 'bg-rose-400'],
            ] as [$etiqueta, $valor, $punto])
                <div class="vidrio p-5">
                    <p class="inline-flex items-center gap-2 text-xs text-slate-400"><span class="punto h-2 w-2 {{ $punto }}"></span>{{ $etiqueta }}</p>
                    <p class="mt-2 font-display text-3xl font-semibold tabular-nums text-white">{{ $valor }}</p>
                </div>
            @endforeach
        </div>

        <section class="vidrio overflow-hidden !p-0">
            <div class="px-6 pb-4 pt-6"><x-ceja>Registros del archivo</x-ceja></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-y border-white/5 text-left text-[0.75rem] font-medium uppercase tracking-[0.18em] text-slate-500">
                            <th scope="col" class="py-3 pl-6 pr-3 font-medium">#</th>
                            <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                            <th scope="col" class="px-3 py-3 font-medium">Estación</th>
                            <th scope="col" class="px-3 py-3 font-medium">Fecha · UTC</th>
                            <th scope="col" class="px-3 py-3 font-medium">Banda</th>
                            <th scope="col" class="py-3 pl-3 pr-6 font-medium">Modo / detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($filas as $fila)
                            <tr class="{{ $fila['estado'] === 'error' ? 'bg-rose-500/[0.04]' : '' }}">
                                <td class="py-3 pl-6 pr-3 font-mono text-slate-500">{{ $fila['numero'] }}</td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    @switch($fila['estado'])
                                        @case('nuevo')
                                            <span class="inline-flex items-center gap-2 text-xs font-medium text-emerald-300"><span class="punto h-2 w-2 bg-emerald-400"></span>Nuevo</span>
                                            @break
                                        @case('duplicado')
                                            <span class="inline-flex items-center gap-2 text-xs font-medium text-amber-300"><span class="punto h-2 w-2 bg-amber-400"></span>Duplicado</span>
                                            @break
                                        @default
                                            <span class="inline-flex items-center gap-2 text-xs font-medium text-rose-300"><span class="punto h-2 w-2 bg-rose-400"></span>Error</span>
                                    @endswitch
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 font-mono font-semibold text-white">{{ $fila['indicativo'] ?: '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-3 font-mono text-slate-400">{{ $fila['fecha'] ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    @if ($fila['banda'])
                                        <x-banda-chip :banda="$fila['banda']" />
                                    @else
                                        <span class="text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="py-3 pl-3 pr-6">
                                    @if ($fila['errores'])
                                        <span class="text-rose-300">{{ implode(' ', $fila['errores']) }}</span>
                                    @else
                                        <span class="text-slate-300">{{ $fila['modo'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($totalFilas > count($filas))
                <p class="border-t border-white/5 px-6 py-3 text-xs text-slate-500">Se muestran los primeros {{ count($filas) }} de {{ $totalFilas }} registros. Al importar se procesan todos.</p>
            @endif
        </section>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <form method="POST" action="{{ route('importacion.cancelar') }}">
                @csrf
                <button type="submit" class="rounded-full px-5 py-2.5 text-sm font-medium text-slate-400 transition hover:text-white">Cancelar</button>
            </form>
            <form method="POST" action="{{ route('importacion.confirmar') }}">
                @csrf
                <x-primary-button class="!px-7 !py-3" :disabled="$nuevos === 0">
                    {{ $nuevos === 0 ? 'No hay contactos nuevos' : 'Importar '.$nuevos.' '.($nuevos === 1 ? 'contacto' : 'contactos') }}
                </x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
