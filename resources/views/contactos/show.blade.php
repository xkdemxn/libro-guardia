{{-- Detalle del contacto con el panel de confirmaciones QSL (US-10). --}}
<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto max-w-4xl">
            <a href="{{ route('contactos.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">&lsaquo; Volver al libro</a>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="font-mono text-4xl font-semibold tracking-wide text-white">{{ $contacto->estacion_llamada }}</h1>
                    <p class="mt-1 text-slate-400">{{ $contacto->prefijoPais?->pais ?? 'País desconocido' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @if ($esUsuario)
                        <a href="{{ route('perfil-publico.show', $contacto->estacion_llamada) }}" class="rounded-full bg-acento-400/10 px-4 py-2 text-sm font-medium text-acento-300 ring-1 ring-acento-400/25 transition hover:bg-acento-400/20">Ver perfil</a>
                    @endif
                    <x-estado-qsl :estado="$contacto->estadoQsl()" />
                    <a href="{{ route('contactos.tarjeta', $contacto) }}" class="rounded-full bg-gradient-to-b from-marca-400 to-marca-600 px-4 py-2 text-sm font-semibold text-blanco shadow-boton transition hover:brightness-110">Tarjeta QSL</a>
                    <a href="{{ route('contactos.edit', $contacto) }}" class="rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-100 transition hover:bg-white/10">Editar</a>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto box-content max-w-4xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        <x-panel ceja="Datos del contacto">
            @php
                $datos = [
                    ['Fecha', $contacto->inicio_utc->format('Y-m-d'), false],
                    ['Hora UTC', $contacto->inicio_utc->format('H:i').($contacto->fin_utc ? ' → '.$contacto->fin_utc->format('H:i') : ''), true],
                    ['Frecuencia', number_format($contacto->frecuencia_mhz, 3).' MHz', true],
                    ['Modo', $contacto->modo->nombre, false],
                    ['Reporte env / rec', $contacto->reporte_enviado.' / '.$contacto->reporte_recibido, true],
                    ['Potencia', $contacto->potencia_w ? rtrim(rtrim(number_format($contacto->potencia_w, 2, '.', ''), '0'), '.').' W' : '—', false],
                    ['Grid locator', $contacto->grid_locator ?? '—', true],
                    ['Operador', $contacto->nombre_operador ?? '—', false],
                    ['Equipo', $contacto->equipo ?? '—', false],
                    ['Activación', $contacto->activacion ?? '—', true],
                    ['Distancia', ($km = $contacto->distanciaKm()) !== null ? '≈ '.number_format($km).' km' : '—', true],
                ];
            @endphp
            <dl class="grid grid-cols-2 gap-x-6 gap-y-5 sm:grid-cols-4">
                <div>
                    <dt class="text-xs text-slate-500">Banda</dt>
                    <dd class="mt-1"><x-banda-chip :banda="$contacto->banda->nombre" /></dd>
                </div>
                @foreach ($datos as [$etiqueta, $valor, $mono])
                    <div>
                        <dt class="text-xs text-slate-500">{{ $etiqueta }}</dt>
                        <dd class="mt-1 text-slate-100 {{ $mono ? 'font-mono' : '' }}">{{ $valor }}</dd>
                    </div>
                @endforeach
            </dl>
            @if ($contacto->observaciones)
                <div class="mt-6 rounded-2xl bg-black/20 p-4 text-sm leading-relaxed text-slate-300 ring-1 ring-white/5">{{ $contacto->observaciones }}</div>
            @endif
        </x-panel>

        <x-panel ceja="Confirmación QSL">
            <p class="-mt-2 mb-5 text-center text-xs text-slate-500">Marca por separado si enviaste y si recibiste la confirmación. La fecha no puede ser anterior al contacto ({{ $contacto->inicio_utc->format('Y-m-d') }}).</p>

            @if ($contacto->rechazado_por_contraparte_at)
                <div class="mb-5 flex items-center gap-3 rounded-2xl border border-rose-400/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                    <span class="punto h-2 w-2 bg-rose-400"></span>
                    {{ $contacto->estacion_llamada }} indicó el {{ $contacto->rechazado_por_contraparte_at->format('Y-m-d') }} que no reconoce este contacto. Revisa la fecha, la hora y la banda.
                </div>
            @endif

            @if ($confirmaciones->has(\App\Enums\TipoQsl::Interna->value))
                <div class="mb-5 flex items-center gap-3 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                    <span class="punto h-2 w-2 bg-emerald-400 shadow-[0_0_8px_rgb(52_211_153)]"></span>
                    Confirmado automáticamente: {{ $contacto->estacion_llamada }} también registró este contacto en Libro de Guardia.
                </div>
            @endif

            <form method="POST" action="{{ route('contactos.qsl.update', $contacto) }}">
                @csrf
                @method('PUT')

                <div class="divide-y divide-white/5">
                    @foreach (\App\Http\Requests\ConfirmacionQslRequest::tiposManuales() as $tipo)
                        @php
                            $actual = $confirmaciones->get($tipo->value);
                            $clave = 'qsl.'.$tipo->value;
                        @endphp
                        <fieldset class="grid grid-cols-1 gap-4 py-5 first:pt-0 last:pb-0 sm:grid-cols-[8rem_1fr_1fr] sm:items-start"
                                  x-data="{ enviada: @js((bool) old($clave.'.enviada', $actual?->enviada)), recibida: @js((bool) old($clave.'.recibida', $actual?->recibida)) }">
                            <legend class="sr-only">{{ $tipo->etiqueta() }}</legend>
                            <p class="pt-2 font-display text-sm font-semibold text-white" aria-hidden="true">{{ $tipo->etiqueta() }}</p>

                            @foreach (['enviada' => ['Enviada', 'fecha_envio'], 'recibida' => ['Recibida', 'fecha_recepcion']] as $estado => [$etiqueta, $campoFecha])
                                <div>
                                    <label class="flex items-center gap-2.5 text-sm text-slate-300">
                                        <input type="checkbox" name="{{ $clave }}[{{ $estado }}]" value="1" x-model="{{ $estado }}"
                                               class="h-4 w-4 rounded border-white/20 bg-white/5 text-emerald-500 focus:ring-emerald-400/30 focus:ring-offset-0">
                                        {{ $etiqueta }}
                                    </label>
                                    <div x-show="{{ $estado }}" x-cloak class="mt-2">
                                        <label for="{{ $tipo->value }}-{{ $campoFecha }}" class="sr-only">Fecha {{ strtolower($etiqueta) }} ({{ $tipo->etiqueta() }})</label>
                                        <x-text-input id="{{ $tipo->value }}-{{ $campoFecha }}" type="date" name="{{ $clave }}[{{ $campoFecha }}]"
                                                      :value="old($clave.'.'.$campoFecha, $actual?->{$campoFecha}?->format('Y-m-d') ?? now('UTC')->format('Y-m-d'))"
                                                      min="{{ $contacto->inicio_utc->format('Y-m-d') }}" />
                                    </div>
                                    <x-input-error :messages="$errors->get($clave.'.'.$campoFecha)" />
                                </div>
                            @endforeach
                        </fieldset>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-end">
                    <x-primary-button>Guardar QSL</x-primary-button>
                </div>
            </form>
        </x-panel>
    </div>
</x-app-layout>
