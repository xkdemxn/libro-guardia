{{--
    Formulario de contacto compartido por crear y editar (US-03, US-04, US-05, US-07).
    Variables: $contacto (Contacto), $modos y $bandas (colecciones).
--}}
@php
    $listaModos = $modos->map(fn ($modo) => [
        'id' => $modo->id,
        'nombre' => $modo->nombre,
        'tipo' => $modo->tipo_reporte->value,
        'ejemplo' => $modo->tipo_reporte->ejemplo(),
    ])->values();
    $listaBandas = $bandas->map(fn ($banda) => [
        'nombre' => $banda->nombre,
        'min' => $banda->frecuencia_min_mhz,
        'max' => $banda->frecuencia_max_mhz,
        'rango' => $banda->rango,
    ])->values();
    $reportesIniciales = [
        'enviado' => (string) old('reporte_enviado', $contacto->reporte_enviado),
        'recibido' => (string) old('reporte_recibido', $contacto->reporte_recibido),
    ];
    // Botones de modo: SSB agrupa USB y LSB (sus dos bandas laterales).
    $botonesModo = collect(['SSB'])->merge($modos->pluck('nombre')->reject(fn ($nombre) => in_array($nombre, ['USB', 'LSB'])));
@endphp

<div
    x-data="formularioContacto(@js($listaModos), @js((string) old('modo_id', $contacto->modo_id)), @js($listaBandas), @js((string) old('frecuencia_mhz', $contacto->frecuencia_mhz)), @js(route('contactos.ficha')), @js((string) old('estacion_llamada', $contacto->estacion_llamada)), @js($reportesIniciales))"
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
                <x-text-input id="estacion_llamada" name="estacion_llamada" type="text" class="!py-3 font-mono !text-2xl font-semibold uppercase tracking-wider" required autofocus maxlength="20" placeholder="LU4BB" autocomplete="off"
                              x-model="indicativo" x-on:input.debounce.350ms="buscarFicha()" aria-describedby="ficha-indicativo" />
                <x-input-error :messages="$errors->get('estacion_llamada')" />
            </div>

            <div class="flex items-end sm:col-span-3 sm:justify-end">
                <x-reloj-utc grande class="w-full justify-center sm:w-auto" />
            </div>

            {{-- Ficha rápida del indicativo (innovación 3) --}}
            <div id="ficha-indicativo" class="sm:col-span-6" aria-live="polite">
                <template x-if="ficha">
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-3 rounded-2xl bg-black/20 px-4 py-3 ring-1 ring-white/5">
                        <template x-if="ficha.perfil && ficha.perfil.foto_url">
                            <img :src="ficha.perfil.foto_url" alt="" class="h-10 w-10 rounded-xl object-cover ring-1 ring-white/10">
                        </template>
                        <div class="min-w-0">
                            <p class="text-sm text-white">
                                <span class="font-mono font-semibold" x-text="ficha.indicativo"></span>
                                <template x-if="ficha.perfil && ficha.perfil.nombre"><span class="text-slate-300"> · <span x-text="ficha.perfil.nombre"></span></span></template>
                            </p>
                            <p class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-400">
                                <template x-if="ficha.bandera"><img :src="ficha.bandera" :alt="`Bandera de ${ficha.pais}`" class="h-3 w-4 rounded-[2px] object-cover ring-1 ring-white/15"></template>
                                <span x-text="[ficha.perfil?.ciudad, ficha.pais].filter(Boolean).join(', ') || 'País desconocido'"></span>
                            </p>
                        </div>
                        <div class="text-xs">
                            <template x-if="ficha.contactos_previos > 0">
                                <p class="inline-flex items-center gap-2 text-amber-200">
                                    <span class="punto h-2 w-2 bg-amber-400"></span>
                                    <span x-text="`Ya hablaste ${ficha.contactos_previos} ${ficha.contactos_previos === 1 ? 'vez' : 'veces'} con esta estación · última ${ficha.ultimo.hace} en ${ficha.ultimo.banda} ${ficha.ultimo.modo}`"></span>
                                </p>
                            </template>
                            <template x-if="ficha.contactos_previos === 0">
                                <p class="inline-flex items-center gap-2 text-emerald-200"><span class="punto h-2 w-2 bg-emerald-400"></span>Primer contacto con esta estación</p>
                            </template>
                        </div>
                        <template x-if="ficha.registrado">
                            <a :href="ficha.perfil.url" target="_blank" class="ms-auto inline-flex items-center gap-1.5 rounded-full bg-acento-400/10 px-3 py-1 text-xs font-medium text-acento-300 ring-1 ring-acento-400/25 hover:bg-acento-400/20">
                                Usa Libro de Guardia · ver perfil
                            </a>
                        </template>
                    </div>
                </template>
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

    <x-panel ceja="02 · Banda y frecuencia">
        {{-- Rango del espectro --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-1 rounded-full bg-black/25 p-1 ring-1 ring-white/5" role="tablist" aria-label="Rango de frecuencias">
                @foreach (['HF' => 'HF · onda corta', 'VHF' => 'VHF', 'UHF' => 'UHF'] as $rango => $etiqueta)
                    <button type="button" role="tab" x-on:click="rangoVisible = '{{ $rango }}'" x-bind:aria-selected="rangoVisible === '{{ $rango }}'"
                            class="rounded-full px-4 py-1.5 text-sm transition"
                            x-bind:class="rangoVisible === '{{ $rango }}' ? 'bg-white/10 font-semibold text-white ring-1 ring-white/10' : 'text-slate-400 hover:text-white'">{{ $etiqueta }}</button>
                @endforeach
            </div>
            <p class="text-xs text-slate-500">HF: 1.8–30 MHz · VHF: 30–300 MHz · UHF: 300–3000 MHz</p>
        </div>

        {{-- Bandas del rango elegido --}}
        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-5" role="radiogroup" aria-label="Banda">
            <template x-for="opcion in bandasDelRango(rangoVisible)" :key="opcion.nombre">
                <button type="button" role="radio" x-on:click="elegirBanda(opcion)" x-bind:aria-checked="banda === opcion.nombre"
                        class="rounded-2xl border px-3 py-2.5 text-left transition"
                        x-bind:class="banda === opcion.nombre ? 'border-acento-400/60 bg-acento-400/10 shadow-[0_0_0_3px_rgb(82_173_255/0.12)]' : 'border-white/10 bg-white/[0.02] hover:bg-white/[0.05]'">
                    <span class="block font-mono text-sm font-semibold text-white" x-text="opcion.nombre"></span>
                    <span class="block font-mono text-[11px] text-slate-500" x-text="`${opcion.min.toFixed(3)}–${opcion.max.toFixed(3)}`"></span>
                </button>
            </template>
        </div>

        {{-- Frecuencia: escrita o con el deslizador dentro de la banda --}}
        <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-6">
            <div class="sm:col-span-2">
                <x-input-label for="frecuencia_mhz" value="Frecuencia (MHz)" />
                <x-text-input id="frecuencia_mhz" name="frecuencia_mhz" type="text" inputmode="decimal" x-model="frecuencia" class="font-mono tabular-nums !text-acento-200" required placeholder="14.225" />
                <p class="mt-1.5 flex h-5 items-center gap-2 text-xs" aria-live="polite">
                    <template x-if="banda">
                        <span class="inline-flex items-center gap-2 text-slate-400">Banda <span class="chip border-sky-400/25 bg-sky-400/10 font-mono text-sky-300" x-text="banda"></span><span x-text="bandaActual?.rango"></span></span>
                    </template>
                    <template x-if="banda === false">
                        <span class="inline-flex items-center gap-2 text-rose-400"><span class="punto bg-rose-400"></span>Fuera de las bandas de radioaficionados</span>
                    </template>
                    <template x-if="banda === null">
                        <span class="text-slate-500">Elige una banda o escribe la frecuencia.</span>
                    </template>
                </p>
                <x-input-error :messages="$errors->get('frecuencia_mhz')" />
            </div>

            <div class="sm:col-span-4">
                <template x-if="bandaActual">
                    <div>
                        <div class="mb-1.5 flex items-baseline justify-between text-[13px]">
                            <label for="deslizador-frecuencia" class="font-medium text-slate-400">Sintonía dentro de <span class="font-mono" x-text="bandaActual.nombre"></span></label>
                            <span class="font-mono text-acento-300" x-text="`${mhz.toFixed(3)} MHz`"></span>
                        </div>
                        <input id="deslizador-frecuencia" type="range" class="w-full accent-[#2a8fff]"
                               x-bind:min="bandaActual.min" x-bind:max="bandaActual.max" x-bind:step="pasoDeslizador(bandaActual)"
                               x-bind:value="mhz" x-on:input="frecuencia = Number($event.target.value).toFixed(3)">
                        <div class="mt-1 flex justify-between font-mono text-[11px] text-slate-500">
                            <span x-text="bandaActual.min.toFixed(3)"></span>
                            <span x-text="bandaActual.max.toFixed(3)"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </x-panel>

    <x-panel ceja="03 · Modo y señal">
        {{-- Modo --}}
        <input type="hidden" name="modo_id" x-bind:value="modoId">
        <x-input-label value="Modo" />
        <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Modo">
            @foreach ($botonesModo as $nombreModo)
                @if ($nombreModo === 'SSB')
                    {{-- SSB es un solo modo; USB/LSB es su variante y aparece dentro del mismo botón. --}}
                    <div class="flex items-center rounded-full border transition"
                         x-bind:class="esSsb ? 'border-acento-400/60 bg-acento-400/15' : 'border-white/10 bg-white/[0.02] hover:bg-white/[0.06]'">
                        <button type="button" role="radio" x-on:click="elegirModo('SSB')" x-bind:aria-checked="esSsb"
                                class="rounded-full px-4 py-2 font-mono text-sm font-semibold transition"
                                x-bind:class="esSsb ? 'text-white' : 'text-slate-300'">SSB</button>
                        <div x-show="esSsb" x-cloak class="flex items-center gap-0.5 pe-1" role="radiogroup" aria-label="Banda lateral de SSB">
                            @foreach (['USB' => 'Banda lateral superior', 'LSB' => 'Banda lateral inferior'] as $lateral => $descripcion)
                                <button type="button" role="radio" x-on:click="elegirLateral('{{ $lateral }}')" x-bind:aria-checked="modoActual?.nombre === '{{ $lateral }}'"
                                        title="{{ $descripcion }}" aria-label="{{ $lateral }}: {{ mb_strtolower($descripcion) }}"
                                        class="rounded-full px-3 py-1 font-mono text-xs font-semibold transition"
                                        x-bind:class="modoActual?.nombre === '{{ $lateral }}' ? 'bg-gradient-to-b from-marca-400 to-marca-600 text-blanco shadow-boton' : 'text-slate-400 hover:text-white'">{{ $lateral }}</button>
                            @endforeach
                        </div>
                    </div>
                    @continue
                @endif
                <button type="button" role="radio" x-on:click="elegirModo(@js($nombreModo))" x-bind:aria-checked="botonActivo(@js($nombreModo))"
                        class="rounded-full border px-4 py-2 font-mono text-sm font-semibold transition"
                        x-bind:class="botonActivo(@js($nombreModo)) ? 'border-acento-400/60 bg-acento-400/15 text-white' : 'border-white/10 bg-white/[0.02] text-slate-300 hover:bg-white/[0.06]'">{{ $nombreModo }}</button>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('modo_id')" />

        {{-- SSB: qué significa la variante elegida y sugerencia para las bandas bajas --}}
        <p x-show="esSsb" x-cloak class="mt-2.5 text-xs text-slate-500">
            <span x-text="modoActual?.nombre === 'LSB' ? 'LSB: banda lateral inferior.' : 'USB: banda lateral superior (la más usada; queda por defecto si no eliges otra).'"></span>
            <span x-show="sugiereLsb" x-cloak class="text-amber-300/90">
                En 160, 80 y 40 m lo habitual es LSB.
                <button type="button" class="font-medium text-acento-300 hover:text-acento-200" x-on:click="elegirLateral('LSB')">Usar LSB</button>
            </span>
        </p>

        {{-- Reportes --}}
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2" x-show="tipo" x-cloak>
            @foreach (['enviado' => 'Reporte enviado', 'recibido' => 'Reporte recibido'] as $lado => $etiqueta)
                <div>
                    <x-input-label :value="$etiqueta" />
                    <input type="hidden" name="reporte_{{ $lado }}" x-bind:value="reporte.{{ $lado }}">

                    {{-- Voz y CW: botón rápido 59 / 599, u «Otro» para armarlo con R, S y T --}}
                    <template x-if="tipo === 'RS' || tipo === 'RST'">
                        <div>
                            <div class="flex gap-2">
                                <button type="button" x-on:click="usarRapido('{{ $lado }}')"
                                        class="flex-1 rounded-xl border px-4 py-2.5 font-mono text-lg font-semibold transition"
                                        x-bind:class="! manual.{{ $lado }} ? 'border-emerald-400/50 bg-emerald-400/10 text-emerald-200' : 'border-white/10 bg-white/[0.02] text-slate-400 hover:bg-white/[0.06]'"
                                        x-text="reporteRapido"></button>
                                <button type="button" x-on:click="abrirManual('{{ $lado }}')"
                                        class="rounded-xl border px-4 py-2.5 text-sm font-semibold transition"
                                        x-bind:class="manual.{{ $lado }} ? 'border-amber-400/50 bg-amber-400/10 text-amber-200' : 'border-white/10 bg-white/[0.02] text-slate-300 hover:bg-white/[0.06]'">Otro</button>
                            </div>
                            <div x-show="manual.{{ $lado }}" x-cloak class="mt-2 grid gap-2" x-bind:class="tipo === 'RST' ? 'grid-cols-3' : 'grid-cols-2'">
                                <label class="text-[11px] text-slate-500">R · legibilidad
                                    <select class="campo mt-1 !py-2 font-mono" x-model.number="rst.{{ $lado }}.r" x-on:change="componer('{{ $lado }}')">
                                        @foreach ([5 => 'perfecta', 4 => 'casi sin dificultad', 3 => 'con dificultad', 2 => 'apenas', 1 => 'ilegible'] as $valor => $texto)
                                            <option value="{{ $valor }}">{{ $valor }} · {{ $texto }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="text-[11px] text-slate-500">S · fuerza
                                    <select class="campo mt-1 !py-2 font-mono" x-model.number="rst.{{ $lado }}.s" x-on:change="componer('{{ $lado }}')">
                                        @for ($valor = 9; $valor >= 1; $valor--)
                                            <option value="{{ $valor }}">{{ $valor }}{{ $valor === 9 ? ' · muy fuerte' : ($valor === 1 ? ' · apenas perceptible' : '') }}</option>
                                        @endfor
                                    </select>
                                </label>
                                <template x-if="tipo === 'RST'">
                                    <label class="text-[11px] text-slate-500">T · tono
                                        <select class="campo mt-1 !py-2 font-mono" x-model.number="rst.{{ $lado }}.t" x-on:change="componer('{{ $lado }}')">
                                            @for ($valor = 9; $valor >= 1; $valor--)
                                                <option value="{{ $valor }}">{{ $valor }}{{ $valor === 9 ? ' · puro' : ($valor === 1 ? ' · muy áspero' : '') }}</option>
                                            @endfor
                                        </select>
                                    </label>
                                </template>
                            </div>
                            <p x-show="manual.{{ $lado }}" x-cloak class="mt-1.5 text-xs text-slate-400">Reporte: <span class="font-mono font-semibold text-slate-200" x-text="reporte.{{ $lado }}"></span></p>
                        </div>
                    </template>

                    {{-- Modos digitales: relación señal/ruido en dB --}}
                    <template x-if="tipo === 'dB'">
                        <div>
                            <input type="text" inputmode="numeric" class="campo font-mono" x-model="reporte.{{ $lado }}" maxlength="4" placeholder="-12" aria-label="{{ $etiqueta }} en dB">
                            <p class="mt-1.5 text-xs text-slate-500">En dB, de -50 a +50 (ej. -12).</p>
                        </div>
                    </template>
                    <x-input-error :messages="$errors->get('reporte_'.$lado)" />
                </div>
            @endforeach
        </div>

        <div class="mt-6 sm:w-1/3">
            <x-input-label for="potencia_w" value="Potencia (W)" />
            <x-text-input id="potencia_w" name="potencia_w" type="number" step="0.1" min="0" :value="old('potencia_w', $contacto->potencia_w)" placeholder="100" />
            <x-input-error :messages="$errors->get('potencia_w')" />
        </div>
    </x-panel>

    <details class="vidrio group p-5 sm:p-6" @if ($errors->hasAny(['nombre_operador', 'equipo', 'grid_locator', 'activacion', 'observaciones']) || $contacto->observaciones || $contacto->nombre_operador) open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-4 [&::-webkit-details-marker]:hidden">
            <span class="h-px flex-1 bg-gradient-to-r from-transparent to-white/15"></span>
            <span class="text-[11px] font-medium uppercase tracking-[0.28em] text-slate-400">04 · Datos opcionales</span>
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
