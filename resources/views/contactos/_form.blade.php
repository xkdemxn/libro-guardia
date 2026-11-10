{{--
    Formulario de contacto compartido por crear y editar (US-03, US-04, US-05, US-07).
    Variables: $contacto (Contacto), $modos y $bandas (colecciones).
--}}
@php
    $tiposPorModo = $modos->mapWithKeys(fn ($modo) => [$modo->id => [
        'tipo' => $modo->tipo_reporte->value,
        'ejemplo' => $modo->tipo_reporte->ejemplo(),
    ]]);
    $rangosBandas = $bandas->map(fn ($banda) => [
        'nombre' => $banda->nombre,
        'min' => $banda->frecuencia_min_mhz,
        'max' => $banda->frecuencia_max_mhz,
    ])->values();
@endphp

<div
    x-data="formularioContacto(@js($tiposPorModo), @js((string) old('modo_id', $contacto->modo_id)), @js($rangosBandas), @js((string) old('frecuencia_mhz', $contacto->frecuencia_mhz)))"
    class="space-y-6"
>
    @if ($errors->has('duplicado'))
        {{-- Aviso de posible duplicado (US-05) --}}
        <div role="alert" class="vidrio !rounded-2xl border-amber-400/25 !bg-amber-400/[0.07] p-5">
            <div class="flex gap-3">
                <span class="punto mt-1.5 h-2 w-2 bg-amber-400 shadow-[0_0_10px_rgb(251_191_36)]"></span>
                <div>
                    <p class="font-semibold text-amber-200">Posible contacto repetido</p>
                    <p class="mt-1 text-sm text-amber-100/80">{{ $errors->first('duplicado') }}</p>
                    <label class="mt-4 flex items-center gap-2.5 text-sm text-amber-100">
                        <input type="checkbox" name="confirmar_duplicado" value="1" class="h-4 w-4 rounded border-amber-300/40 bg-white/5 text-amber-500 focus:ring-amber-400/30 focus:ring-offset-0">
                        Sí, no es un duplicado: guardarlo de todos modos
                    </label>
                </div>
            </div>
        </div>
    @endif

    <x-panel ceja="01 · Estación y horario">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-6">
            <div class="sm:col-span-3">
                <x-input-label for="estacion_llamada" value="Estación llamada (indicativo)" />
                <x-text-input id="estacion_llamada" name="estacion_llamada" type="text" class="!py-3 font-mono !text-2xl font-semibold uppercase tracking-wider" :value="old('estacion_llamada', $contacto->estacion_llamada)" required autofocus maxlength="20" placeholder="LU4BB" autocomplete="off" />
                <x-input-error :messages="$errors->get('estacion_llamada')" />
            </div>

            <div class="flex items-end sm:col-span-3 sm:justify-end">
                <x-reloj-utc grande class="w-full justify-center sm:w-auto" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="fecha" value="Fecha (UTC)" />
                <x-text-input id="fecha" name="fecha" type="date" x-ref="fecha" :value="old('fecha', $contacto->inicio_utc?->format('Y-m-d'))" required />
                <x-input-error :messages="$errors->get('fecha')" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="hora_inicio" value="Hora de inicio (UTC)" />
                <div class="flex gap-2">
                    <x-text-input id="hora_inicio" name="hora_inicio" type="time" x-ref="horaInicio" :value="old('hora_inicio', $contacto->inicio_utc?->format('H:i'))" required />
                    <button type="button" x-on:click="ponerAhora('inicio')" class="shrink-0 rounded-xl border border-acento-400/30 bg-acento-400/10 px-3.5 text-sm font-semibold text-acento-300 transition hover:bg-acento-400/20 active:scale-95">Ahora</button>
                </div>
                <x-input-error :messages="$errors->get('hora_inicio')" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="hora_fin" value="Hora de fin (UTC)" />
                <div class="flex gap-2">
                    <x-text-input id="hora_fin" name="hora_fin" type="time" x-ref="horaFin" :value="old('hora_fin', $contacto->fin_utc?->format('H:i'))" />
                    <button type="button" x-on:click="ponerAhora('fin')" class="shrink-0 rounded-xl border border-acento-400/30 bg-acento-400/10 px-3.5 text-sm font-semibold text-acento-300 transition hover:bg-acento-400/20 active:scale-95">Ahora</button>
                </div>
                <x-input-error :messages="$errors->get('hora_fin')" />
            </div>
        </div>
    </x-panel>

    <x-panel ceja="02 · Frecuencia y señal">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-6">
            <div class="sm:col-span-2">
                <x-input-label for="frecuencia_mhz" value="Frecuencia (MHz)" />
                <x-text-input id="frecuencia_mhz" name="frecuencia_mhz" type="text" inputmode="decimal" x-model="frecuencia" class="font-mono tabular-nums !text-acento-200" required placeholder="14.225" />
                <p class="mt-1.5 flex h-5 items-center gap-2 text-xs" aria-live="polite">
                    <template x-if="banda">
                        <span class="inline-flex items-center gap-2 text-slate-400">Banda <span class="chip border-sky-400/25 bg-sky-400/10 font-mono text-sky-300" x-text="banda"></span></span>
                    </template>
                    <template x-if="banda === false">
                        <span class="inline-flex items-center gap-2 text-rose-400"><span class="punto bg-rose-400"></span>Fuera de las bandas de radioaficionados</span>
                    </template>
                    <template x-if="banda === null">
                        <span class="text-slate-500">La banda se calcula sola.</span>
                    </template>
                </p>
                <x-input-error :messages="$errors->get('frecuencia_mhz')" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="modo_id" value="Modo" />
                <select id="modo_id" name="modo_id" x-model="modoId" required class="campo">
                    <option value="">Elige un modo</option>
                    @foreach ($modos as $modo)
                        <option value="{{ $modo->id }}" @selected((string) old('modo_id', $contacto->modo_id) === (string) $modo->id)>{{ $modo->nombre }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('modo_id')" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="potencia_w" value="Potencia (W)" />
                <x-text-input id="potencia_w" name="potencia_w" type="number" step="0.1" min="0" :value="old('potencia_w', $contacto->potencia_w)" placeholder="100" />
                <x-input-error :messages="$errors->get('potencia_w')" />
            </div>

            <div class="sm:col-span-3">
                <x-input-label for="reporte_enviado" value="Reporte enviado" />
                <x-text-input id="reporte_enviado" name="reporte_enviado" type="text" class="font-mono tabular-nums" :value="old('reporte_enviado', $contacto->reporte_enviado)" required maxlength="4" x-bind:placeholder="ejemplo" />
                <x-input-error :messages="$errors->get('reporte_enviado')" />
            </div>

            <div class="sm:col-span-3">
                <x-input-label for="reporte_recibido" value="Reporte recibido" />
                <x-text-input id="reporte_recibido" name="reporte_recibido" type="text" class="font-mono tabular-nums" :value="old('reporte_recibido', $contacto->reporte_recibido)" required maxlength="4" x-bind:placeholder="ejemplo" />
                <x-input-error :messages="$errors->get('reporte_recibido')" />
            </div>

            <p class="text-xs text-slate-500 sm:col-span-6" x-show="tipo" x-cloak>
                En este modo el reporte es <span class="font-semibold text-slate-300" x-text="tipo"></span>, por ejemplo <span class="font-mono text-slate-300" x-text="ejemplo"></span>.
            </p>
        </div>
    </x-panel>

    <details class="vidrio group p-5 sm:p-6" @if ($errors->hasAny(['nombre_operador', 'equipo', 'grid_locator', 'activacion', 'observaciones']) || $contacto->observaciones || $contacto->nombre_operador) open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-4 [&::-webkit-details-marker]:hidden">
            <span class="h-px flex-1 bg-gradient-to-r from-transparent to-white/15"></span>
            <span class="text-[11px] font-medium uppercase tracking-[0.28em] text-slate-400">03 · Datos opcionales</span>
            <svg class="h-4 w-4 text-slate-500 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
            <span class="h-px flex-1 bg-gradient-to-l from-transparent to-white/15"></span>
        </summary>
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="nombre_operador" value="Nombre del operador" />
                <x-text-input id="nombre_operador" name="nombre_operador" type="text" :value="old('nombre_operador', $contacto->nombre_operador)" maxlength="100" />
                <x-input-error :messages="$errors->get('nombre_operador')" />
            </div>
            <div>
                <x-input-label for="equipo" value="Equipo de la otra estación" />
                <x-text-input id="equipo" name="equipo" type="text" :value="old('equipo', $contacto->equipo)" maxlength="255" />
                <x-input-error :messages="$errors->get('equipo')" />
            </div>
            <div>
                <x-input-label for="grid_locator" value="Grid locator" />
                <x-text-input id="grid_locator" name="grid_locator" type="text" class="font-mono uppercase" :value="old('grid_locator', $contacto->grid_locator)" maxlength="6" placeholder="FI09" />
                <x-input-error :messages="$errors->get('grid_locator')" />
            </div>
            <div>
                <x-input-label for="activacion" value="Activación (POTA / SOTA)" />
                <x-text-input id="activacion" name="activacion" type="text" class="font-mono uppercase" :value="old('activacion', $contacto->activacion)" maxlength="30" placeholder="HC-0001" />
                <x-input-error :messages="$errors->get('activacion')" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="observaciones" value="Observaciones" />
                <textarea id="observaciones" name="observaciones" rows="3" maxlength="2000" class="campo">{{ old('observaciones', $contacto->observaciones) }}</textarea>
                <x-input-error :messages="$errors->get('observaciones')" />
            </div>
        </div>
    </details>
</div>
