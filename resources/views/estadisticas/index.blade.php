{{-- Estadísticas (US-16), mapa de contactos (innovación 2) y diplomas (innovación 4). --}}
<x-app-layout>
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    @endpush

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-[0.28em] text-slate-500">
                    Actividad · <span class="font-mono text-acento-300">{{ Auth::user()->indicativo }}</span>
                </p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-white sm:text-4xl">Estadísticas</h1>
            </div>
            <form method="GET" action="{{ route('estadisticas') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <x-input-label for="desde" value="Desde" />
                    <x-text-input id="desde" name="desde" type="date" :value="request('desde')" class="!py-2" />
                </div>
                <div>
                    <x-input-label for="hasta" value="Hasta" />
                    <x-text-input id="hasta" name="hasta" type="date" :value="request('hasta')" class="!py-2" />
                </div>
                <x-primary-button class="!py-2.5">Aplicar</x-primary-button>
                @if (request()->query())
                    <a href="{{ route('estadisticas') }}" class="pb-2.5 text-sm text-slate-400 hover:text-white">Todo</a>
                @endif
            </form>
        </div>
        @if ($errors->any())
            <p role="alert" class="mt-3 flex items-center gap-2 text-sm text-rose-400"><span class="punto bg-rose-400"></span>{{ $errors->first() }}</p>
        @endif
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        @if (! $hayContactos)
            <div class="vidrio mx-auto max-w-xl px-6 py-14 text-center" role="status">
                <h2 class="font-display text-xl font-semibold text-white">No hay contactos en este rango de fechas.</h2>
                <p class="mt-2 text-slate-400">Prueba con otro rango o registra tu primer contacto.</p>
            </div>
        @else
            {{-- Resumen --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ([
                    ['Contactos', $totales['contactos']],
                    ['Países', $totales['paises']],
                    ['Confirmados', $totales['confirmados']],
                    ['Bandas', $totales['bandas']],
                ] as $indice => [$titulo, $valor])
                    <div class="vidrio p-5">
                        <x-ceja class="mb-4">{{ $titulo }}</x-ceja>
                        <div class="flex items-end justify-between">
                            <p class="font-display text-3xl font-semibold tabular-nums text-white sm:text-4xl">{{ number_format($valor) }}</p>
                            <p class="pb-1 font-mono text-[11px] text-slate-600">{{ sprintf('%02d', $indice + 1) }} / 04</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Mapa (innovación 2) --}}
            <section class="vidrio overflow-hidden !p-0">
                <div class="flex flex-wrap items-center justify-between gap-4 px-6 pb-4 pt-6">
                    <x-ceja class="flex-1">Mapa de contactos</x-ceja>
                </div>
                <div id="mapa-contactos" class="h-[26rem] w-full border-y border-white/5 bg-noche-950" role="img"
                     aria-label="Mapa mundial con {{ count($puntos) }} ubicaciones contactadas"></div>
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 px-6 py-4 text-xs text-slate-400">
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-dial-400 ring-2 ring-noche-950"></span>Mi estación</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-acento-400 ring-2 ring-noche-950"></span>Estaciones contactadas (más grande = más contactos)</span>
                    @if ($masLejano)
                        <span class="ms-auto">Contacto más lejano: <span class="font-mono font-semibold text-white">{{ $masLejano['indicativo'] }}</span> · {{ $masLejano['lugar'] }} · <span class="font-mono text-acento-300">{{ number_format($masLejano['km']) }} km</span></span>
                    @endif
                </div>
                @unless ($origen)
                    <p class="border-t border-white/5 px-6 py-3 text-xs text-slate-500">Agrega tu grid locator en <a href="{{ route('profile.edit') }}" class="text-acento-300">tu perfil</a> para calcular distancias exactas.</p>
                @endunless
            </section>

            {{-- Diplomas (innovación 4) --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-panel ceja="Diploma DXCC">
                    @php($porcentaje = min(100, round($dxcc['confirmados'] / $dxcc['meta'] * 100)))
                    <div class="flex items-end justify-between">
                        <p><span class="font-display text-4xl font-semibold tabular-nums text-white">{{ $dxcc['confirmados'] }}</span> <span class="text-slate-500">/ {{ $dxcc['meta'] }}</span></p>
                        <p class="pb-1 font-mono text-sm text-acento-300">{{ $porcentaje }} %</p>
                    </div>
                    <div class="mt-4 h-3 rounded-full bg-acento-400/15" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $dxcc['meta'] }}" aria-valuenow="{{ $dxcc['confirmados'] }}" aria-label="Países confirmados para el DXCC">
                        <div class="h-3 rounded-full bg-gradient-to-r from-acento-500 to-acento-300" style="width: {{ max(1, $porcentaje) }}%"></div>
                    </div>
                    <p class="mt-3 text-sm text-slate-400">Países con QSL confirmada. El diploma DXCC de la ARRL se otorga al confirmar 100.</p>
                </x-panel>

                <x-panel ceja="Diploma WAC · todos los continentes">
                    <ul class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ($continentes as $continente)
                            <li class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 ring-1 {{ $continente['trabajado'] ? 'bg-emerald-400/10 text-emerald-200 ring-emerald-400/20' : 'bg-white/[0.02] text-slate-500 ring-white/5' }}">
                                <span class="punto h-2 w-2 {{ $continente['trabajado'] ? 'bg-emerald-400 shadow-[0_0_8px_rgb(52_211_153)]' : 'bg-slate-600' }}"></span>
                                <span class="text-sm">{{ $continente['nombre'] }}</span>
                                <span class="sr-only">{{ $continente['trabajado'] ? 'trabajado' : 'pendiente' }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-sm text-slate-400">{{ $continentes->where('trabajado', true)->count() }} de 6 continentes contactados.</p>
                </x-panel>
            </div>

            {{-- Distribución (US-16) --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <x-panel ceja="Por banda">
                    <x-barras :datos="$porBanda" mono />
                </x-panel>
                <x-panel ceja="Por modo">
                    <x-barras :datos="$porModo" />
                </x-panel>
                <x-panel ceja="Top países">
                    <x-barras :datos="$porPais" />
                </x-panel>
            </div>
        @endif
    </div>

    @if ($hayContactos)
        @push('scripts')
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
            <script>
                (() => {
                    const origen = @js($origen);
                    const puntos = @js($puntos);
                    const mapa = L.map('mapa-contactos', { worldCopyJump: true, scrollWheelZoom: false, attributionControl: true })
                        .setView(origen ? [origen.lat, origen.lon] : [10, -40], 2);

                    // Mapa base oscuro de Esri: gratuito y sin clave de API.
                    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
                        attribution: 'Mapa base &copy; Esri, HERE, Garmin, &copy; OpenStreetMap',
                        maxZoom: 10,
                    }).addTo(mapa);

                    const limites = [];
                    const maximo = Math.max(1, ...puntos.map((p) => p.cantidad));

                    puntos.forEach((punto) => {
                        if (origen) {
                            L.polyline([[origen.lat, origen.lon], [punto.lat, punto.lon]], {
                                color: '#52adff', weight: 1, opacity: 0.35, interactive: false,
                            }).addTo(mapa);
                        }

                        L.circleMarker([punto.lat, punto.lon], {
                            radius: 5 + 9 * Math.sqrt(punto.cantidad / maximo),
                            color: '#070e1c', weight: 2, fillColor: '#52adff', fillOpacity: 0.9,
                        })
                            .bindTooltip(
                                `<strong>${punto.lugar}</strong><br>${punto.cantidad} ${punto.cantidad === 1 ? 'contacto' : 'contactos'}<br><span style="font-family:monospace">${punto.indicativos.join(', ')}</span>`,
                                { direction: 'top', offset: [0, -6] },
                            )
                            .addTo(mapa);

                        limites.push([punto.lat, punto.lon]);
                    });

                    if (origen) {
                        L.circleMarker([origen.lat, origen.lon], {
                            radius: 7, color: '#070e1c', weight: 2, fillColor: '#ffb84a', fillOpacity: 1,
                        }).bindTooltip('Mi estación', { direction: 'top', offset: [0, -6] }).addTo(mapa);
                        limites.push([origen.lat, origen.lon]);
                    }

                    if (limites.length > 1) {
                        mapa.fitBounds(limites, { padding: [30, 30], maxZoom: 4 });
                    }
                })();
            </script>
        @endpush
    @endif
</x-app-layout>
