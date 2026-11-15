<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-[0.28em] text-slate-500">
                    Libro de guardia · <span class="font-mono text-acento-300">{{ Auth::user()->indicativo }}</span>
                </p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-white sm:text-4xl">Mis contactos</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('importacion.create') }}" class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-semibold text-slate-100 transition hover:bg-white/10">
                Importar
            </a>
            @if ($tieneContactos)
                <a href="{{ route('exportacion.create') }}" class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-semibold text-slate-100 transition hover:bg-white/10">
                    Exportar
                </a>
            @endif
            <a href="{{ route('contactos.create') }}" class="inline-flex items-center gap-2 rounded-full bg-gradient-to-b from-marca-400 to-marca-600 px-5 py-2.5 text-sm font-semibold text-blanco shadow-boton transition hover:brightness-110 active:scale-[0.97]">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                Nuevo contacto
            </a>
            </div>
        </div>
    </x-slot>

    <div x-data="{ eliminar: { url: '', texto: '' } }" class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        @if ($solicitudesPendientes)
            <a href="{{ route('solicitudes.index') }}" class="vidrio flex items-center gap-4 !rounded-2xl border-rose-400/20 px-5 py-4 transition hover:bg-white/[0.06]">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-rose-500/15 font-display font-semibold text-rose-200 ring-1 ring-rose-400/25">{{ $solicitudesPendientes }}</span>
                <span class="flex-1 text-sm text-slate-200">
                    {{ $solicitudesPendientes === 1
                        ? 'Otra estación registró un contacto contigo que no está en tu libro.'
                        : 'Otras estaciones registraron '.$solicitudesPendientes.' contactos contigo que no están en tu libro.' }}
                    <span class="text-slate-400">Revísalo y confírmalo con un clic.</span>
                </span>
                <span class="text-sm font-medium text-acento-300">Ver &rsaquo;</span>
            </a>
        @endif

        @if ($tieneContactos)
            {{-- Resumen --}}
            @php
                $tarjetas = [
                    ['Contactos', $resumen['contactos'], 'QSO registrados'],
                    ['Países', $resumen['paises'], 'distintos contactados'],
                    ['Confirmados', $resumen['confirmados'], 'con QSL recibida'],
                    ['Bandas', $resumen['bandas'], 'en las que operaste'],
                ];
            @endphp
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($tarjetas as $indice => [$titulo, $valor, $detalle])
                    <div class="vidrio p-5">
                        <x-ceja class="mb-4">{{ $titulo }}</x-ceja>
                        <div class="flex items-end justify-between gap-2">
                            <p class="font-display text-3xl font-semibold tabular-nums text-white sm:text-4xl">{{ number_format($valor) }}</p>
                            <p class="pb-1 font-mono text-[11px] text-slate-600">{{ sprintf('%02d', $indice + 1) }} / 04</p>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ $detalle }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Búsqueda y filtros (US-09) --}}
            <x-panel ceja="Buscar y filtrar">
                <form method="GET" action="{{ route('contactos.index') }}">
                    <div class="grid grid-cols-2 items-end gap-4 md:grid-cols-6">
                        <div class="col-span-2">
                            <x-input-label for="indicativo" value="Indicativo" />
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.08 3.08a.75.75 0 1 1-1.06 1.06l-3.08-3.08A7 7 0 0 1 2 9Z" clip-rule="evenodd" /></svg>
                                <x-text-input id="indicativo" name="indicativo" type="search" class="!pl-10 font-mono uppercase" :value="request('indicativo')" placeholder="HC1, LU4BB…" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="desde" value="Desde" />
                            <x-text-input id="desde" name="desde" type="date" :value="request('desde')" />
                        </div>
                        <div>
                            <x-input-label for="hasta" value="Hasta" />
                            <x-text-input id="hasta" name="hasta" type="date" :value="request('hasta')" />
                        </div>
                        <div>
                            <x-input-label for="banda_id" value="Banda" />
                            <select id="banda_id" name="banda_id" class="campo">
                                <option value="">Todas</option>
                                @foreach ($bandas as $banda)
                                    <option value="{{ $banda->id }}" @selected((string) request('banda_id') === (string) $banda->id)>{{ $banda->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="modo_id" value="Modo" />
                            <select id="modo_id" name="modo_id" class="campo">
                                <option value="">Todos</option>
                                @foreach ($modos as $modo)
                                    <option value="{{ $modo->id }}" @selected((string) request('modo_id') === (string) $modo->id)>{{ $modo->etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        {{-- Estado QSL como control segmentado --}}
                        <div class="grid w-full grid-cols-4 gap-1 rounded-full bg-black/25 p-1 ring-1 ring-white/5 sm:inline-flex sm:w-auto" role="radiogroup" aria-label="Estado de QSL">
                            @foreach (['' => 'Todas', 'confirmado' => 'Confirmado', 'enviada' => 'Enviada', 'pendiente' => 'Pendiente'] as $valor => $etiqueta)
                                <label class="cursor-pointer">
                                    <input type="radio" name="qsl" value="{{ $valor }}" class="peer sr-only" @checked((string) request('qsl') === $valor)>
                                    <span class="flex justify-center rounded-full px-1 py-1.5 text-[13px] text-slate-400 sm:px-3.5 sm:text-sm transition hover:text-white peer-checked:bg-white/10 peer-checked:font-semibold peer-checked:text-white peer-checked:ring-1 peer-checked:ring-white/10 peer-focus-visible:ring-2 peer-focus-visible:ring-acento-400">{{ $etiqueta }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            @if (request()->query())
                                <a href="{{ route('contactos.index') }}" class="text-sm text-slate-400 transition hover:text-white">Limpiar</a>
                            @endif
                            <x-primary-button>Buscar</x-primary-button>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div role="alert" class="mt-4 space-y-1">
                            @foreach ($errors->all() as $mensaje)
                                <p class="flex items-center gap-2 text-sm text-rose-400"><span class="punto bg-rose-400"></span>{{ $mensaje }}</p>
                            @endforeach
                        </div>
                    @endif
                </form>
            </x-panel>
        @endif

        @if (! $tieneContactos)
            {{-- Libro vacío (US-06) --}}
            <div class="vidrio mx-auto max-w-xl px-6 py-14 text-center">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-gradient-to-b from-acento-400/30 to-acento-600/10 ring-1 ring-acento-400/30">
                    <x-application-logo class="h-8 w-8 text-acento-300" />
                </span>
                <h2 class="mt-6 font-display text-xl font-semibold text-white">Aún no tienes contactos registrados.</h2>
                <p class="mt-2 text-slate-400">Cuando hagas tu primer QSO, anótalo aquí. La banda y el país se calculan solos.</p>
                <a href="{{ route('contactos.create') }}" class="mt-8 inline-flex items-center gap-2 rounded-full bg-gradient-to-b from-marca-400 to-marca-600 px-6 py-3 text-sm font-semibold text-blanco shadow-boton transition hover:brightness-110">
                    Registrar mi primer contacto
                </a>
            </div>
        @else
            <section class="vidrio overflow-hidden !p-0">
                <div class="flex items-center justify-between gap-4 px-6 pb-4 pt-6">
                    <x-ceja class="flex-1">
                        {{ $hayFiltros ? 'Resultados' : 'Registro' }} · {{ number_format($contactos->total()) }} {{ $contactos->total() === 1 ? 'contacto' : 'contactos' }}
                    </x-ceja>
                </div>

                @if ($contactos->isEmpty())
                    <div class="px-6 pb-14 pt-8 text-center">
                        <p class="font-display text-lg text-white">No se encontraron contactos.</p>
                        <p class="mt-1 text-sm text-slate-400">Prueba con otro indicativo o quita algunos filtros.</p>
                    </div>
                @else
                    {{-- Tabla (pantallas medianas y grandes) --}}
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-y border-white/5 text-left text-[11px] font-medium uppercase tracking-[0.18em] text-slate-500">
                                    <th scope="col" class="py-3 pl-6 pr-3 font-medium">Fecha · UTC</th>
                                    <th scope="col" class="px-3 py-3 font-medium">Estación</th>
                                    <th scope="col" class="px-3 py-3 text-right font-medium">MHz</th>
                                    <th scope="col" class="px-3 py-3 font-medium">Banda</th>
                                    <th scope="col" class="px-3 py-3 font-medium">Modo</th>
                                    <th scope="col" class="px-3 py-3 font-medium">Env / Rec</th>
                                    <th scope="col" class="px-3 py-3 font-medium">QSL</th>
                                    <th scope="col" class="py-3 pl-3 pr-6"><span class="sr-only">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach ($contactos as $contacto)
                                    <tr class="group transition hover:bg-white/[0.03]">
                                        <td class="whitespace-nowrap py-3.5 pl-6 pr-3">
                                            <p class="tabular-nums text-slate-300">{{ $contacto->inicio_utc->format('Y-m-d') }}</p>
                                            <p class="font-mono text-xs text-slate-500">{{ $contacto->inicio_utc->format('H:i') }}{{ $contacto->fin_utc ? ' → '.$contacto->fin_utc->format('H:i') : '' }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5">
                                            <a href="{{ route('contactos.show', $contacto) }}" class="font-mono text-[15px] font-semibold tracking-wide text-white transition hover:text-acento-300">{{ $contacto->estacion_llamada }}</a>
                                            <p class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500"><x-bandera :codigo="$contacto->prefijoPais?->codigo_bandera" :pais="$contacto->prefijoPais?->pais" class="!h-3 !w-4" />{{ $contacto->prefijoPais?->pais ?? 'País desconocido' }}@if ($zona = $contacto->zonaEcuador())<span class="text-amber-300/80" title="{{ $zona->descripcion() }}">· {{ $zona->nombre() }}</span>@endif</p>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-right font-mono tabular-nums text-acento-300">{{ number_format($contacto->frecuencia_mhz, 3) }}</td>
                                        <td class="whitespace-nowrap px-3 py-3.5"><x-banda-chip :banda="$contacto->banda->nombre" /></td>
                                        <td class="whitespace-nowrap px-3 py-3.5 font-medium text-slate-300">{{ $contacto->modo->etiqueta }}</td>
                                        <td class="whitespace-nowrap px-3 py-3.5 font-mono tabular-nums text-slate-400">{{ $contacto->reporte_enviado }} / {{ $contacto->reporte_recibido }}</td>
                                        <td class="whitespace-nowrap px-3 py-3.5"><x-estado-qsl :estado="$contacto->estadoQsl()" /></td>
                                        <td class="whitespace-nowrap py-3.5 pl-3 pr-6 text-right">
                                            <div class="inline-flex items-center gap-1 opacity-60 transition group-hover:opacity-100 group-focus-within:opacity-100">
                                                <a href="{{ route('contactos.edit', $contacto) }}" class="grid h-8 w-8 place-items-center rounded-full text-slate-300 transition hover:bg-white/10 hover:text-white" title="Editar" aria-label="Editar contacto con {{ $contacto->estacion_llamada }}">
                                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" /><path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" /></svg>
                                                </a>
                                                <button type="button" class="grid h-8 w-8 place-items-center rounded-full text-slate-300 transition hover:bg-rose-500/15 hover:text-rose-300" title="Eliminar" aria-label="Eliminar contacto con {{ $contacto->estacion_llamada }}"
                                                        x-on:click="eliminar = { url: @js(route('contactos.destroy', $contacto)), texto: @js($contacto->estacion_llamada.' del '.$contacto->inicio_utc->format('Y-m-d H:i').' UTC') }; $dispatch('open-modal', 'eliminar-contacto')">
                                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Tarjetas (celular) --}}
                    <ul class="space-y-3 px-4 pb-4 md:hidden">
                        @foreach ($contactos as $contacto)
                            <li class="rounded-2xl border border-white/5 bg-white/[0.03] p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <a href="{{ route('contactos.show', $contacto) }}" class="font-mono text-lg font-semibold text-white">{{ $contacto->estacion_llamada }}</a>
                                        <p class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500"><x-bandera :codigo="$contacto->prefijoPais?->codigo_bandera" :pais="$contacto->prefijoPais?->pais" class="!h-3 !w-4" />{{ $contacto->prefijoPais?->pais ?? 'País desconocido' }}@if ($zona = $contacto->zonaEcuador())<span class="text-amber-300/80" title="{{ $zona->descripcion() }}">· {{ $zona->nombre() }}</span>@endif</p>
                                    </div>
                                    <x-estado-qsl :estado="$contacto->estadoQsl()" />
                                </div>
                                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                                    <x-banda-chip :banda="$contacto->banda->nombre" />
                                    <span class="chip border-white/10 bg-white/5 text-slate-300">{{ $contacto->modo->etiqueta }}</span>
                                    <span class="font-mono text-acento-300">{{ number_format($contacto->frecuencia_mhz, 3) }}</span>
                                    <span class="font-mono text-slate-400">{{ $contacto->reporte_enviado }}/{{ $contacto->reporte_recibido }}</span>
                                </div>
                                <div class="mt-3 flex items-center justify-between border-t border-white/5 pt-3">
                                    <p class="font-mono text-xs text-slate-500">{{ $contacto->inicio_utc->format('Y-m-d H:i') }} UTC</p>
                                    <div class="flex gap-4 text-sm">
                                        <a href="{{ route('contactos.edit', $contacto) }}" class="text-acento-300">Editar</a>
                                        <button type="button" class="text-rose-300"
                                                x-on:click="eliminar = { url: @js(route('contactos.destroy', $contacto)), texto: @js($contacto->estacion_llamada.' del '.$contacto->inicio_utc->format('Y-m-d H:i').' UTC') }; $dispatch('open-modal', 'eliminar-contacto')">Eliminar</button>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="border-t border-white/5 px-6 py-4">
                        {{ $contactos->onEachSide(1)->links('pagination.vidrio') }}
                    </div>
                @endif
            </section>
        @endif

        {{-- Confirmación de eliminación (US-08) --}}
        <x-modal name="eliminar-contacto" maxWidth="md" focusable>
            <form method="POST" x-bind:action="eliminar.url" class="p-6 sm:p-8">
                @csrf
                @method('DELETE')
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-rose-500/15 text-rose-300 ring-1 ring-rose-400/25">
                    <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
                </span>
                <h2 class="mt-5 font-display text-lg font-semibold text-white">¿Eliminar este contacto?</h2>
                <p class="mt-2 text-sm text-slate-400">
                    Se eliminará el contacto con <span class="font-mono text-slate-200" x-text="eliminar.texto"></span>. Esta acción no se puede deshacer.
                </p>
                <div class="mt-8 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                    <x-danger-button>Eliminar</x-danger-button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
