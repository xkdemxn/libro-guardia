{{-- Tarjeta QSL: vista previa en vivo, personalización (foto y mensaje) y descarga en PNG o SVG. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto max-w-5xl">
            <a href="{{ route('contactos.show', $contacto) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">&lsaquo; Volver al contacto</a>
            <h1 class="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">Tarjeta QSL</h1>
            <p class="mt-1 text-slate-400">Envíasela a <span class="font-mono text-slate-200">{{ $contacto->estacion_llamada }}</span> para confirmar el contacto, como las tarjetas postales de antes.</p>
        </div>
    </x-slot>

    {{-- fotoPerfil maneja la foto (fondos.qsl) y detecta cambios del formulario de personalización --}}
    <div class="mx-auto box-content max-w-5xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8"
         x-data="fotoPerfil(null, @js($errors->any()), {}, @js([
             'url' => $fondo['data'] ?? null,
             'estilo' => old('qsl_foto_estilo', $operador->qsl_foto_estilo),
             'opacidad' => (int) old('qsl_foto_opacidad', $operador->qsl_foto_opacidad),
         ]))"
         x-on:input="revisarCambios()" x-on:change="revisarCambios()">
        <div x-data="{ mensajeQsl: @js(old('qsl_mensaje', $operador->qsl_mensaje) ?? '') }" class="space-y-6">
            <div x-data="tarjetaQsl(@js($nombreArchivo))" class="space-y-4">
                <div x-ref="tarjeta" class="overflow-hidden rounded-[1.6rem] shadow-vidrio-lg ring-1 ring-white/10 [&>svg]:h-auto [&>svg]:w-full">
                    @include('tarjeta-qsl.svg', ['editable' => true])
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <p class="text-sm text-slate-500">
                        <span x-show="! hayCambios">Tus datos salen de <a href="{{ route('profile.edit') }}" class="text-acento-300 hover:text-acento-200">tu perfil</a>: ciudad, grid locator, equipo y antena.</span>
                        <span x-show="hayCambios" x-cloak class="text-amber-300/90">Tienes cambios sin guardar: el PNG ya los incluye; guarda para que también salgan en el SVG.</span>
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('contactos.tarjeta.svg', $contacto) }}" class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-semibold text-slate-100 transition hover:bg-white/10">
                            Descargar SVG
                        </a>
                        <x-primary-button type="button" x-on:click="descargarPng()" x-bind:disabled="generando">
                            <x-icono nombre="importar" class="h-4 w-4" />
                            <span x-text="generando ? 'Generando…' : 'Descargar PNG'">Descargar PNG</span>
                        </x-primary-button>
                    </div>
                </div>
                <p x-show="error" x-cloak role="alert" class="text-right text-sm text-rose-400" x-text="error"></p>
            </div>

            {{-- Personalización: se aplica a todas las tarjetas del operador --}}
            <form method="post" action="{{ route('tarjeta-qsl.personalizar') }}" enctype="multipart/form-data" novalidate>
                @csrf
                @method('patch')

                <x-panel ceja="Personalizar tarjeta" class="sm:!p-8">
                    <p class="-mt-2 text-center text-sm text-slate-400">Los cambios se ven en vivo arriba y se aplican a <strong class="font-semibold text-slate-200">todas</strong> tus tarjetas.</p>

                    <div class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                        <div>
                            <h2 class="font-display text-base font-semibold text-white">Foto</h2>
                            <p class="mt-1 text-sm text-slate-400">De ti, de tu estación o de tu ciudad, como las tarjetas impresas de siempre.</p>
                            @include('profile.partials.selector-fondo', [
                                'clave' => 'qsl',
                                'campo' => 'qsl_foto',
                                'guardada' => $operador->qsl_foto_path !== null,
                                'quitarTexto' => 'Quitar foto',
                                'estilos' => [
                                    'degradado' => ['Foto a la derecha', 'Se ve completa a la derecha y se desvanece hasta la mitad.'],
                                    'completa' => ['Fondo completo', 'Cubre toda la tarjeta, con la transparencia que elijas.'],
                                ],
                            ])
                        </div>

                        <div>
                            <h2 class="font-display text-base font-semibold text-white">Mensaje</h2>
                            <p class="mt-1 text-sm text-slate-400">El texto al pie de la tarjeta.</p>
                            <div class="mt-6">
                                <label for="qsl_mensaje" class="sr-only">Mensaje de la tarjeta</label>
                                <textarea id="qsl_mensaje" name="qsl_mensaje" rows="3" maxlength="{{ \App\Http\Requests\PersonalizarTarjetaQslRequest::MENSAJE_MAXIMO }}"
                                          x-model="mensajeQsl"
                                          placeholder="{{ \App\Models\User::MENSAJE_QSL_PREDETERMINADO }}" class="campo resize-none"></textarea>
                                <div class="mt-1.5 flex items-center justify-between text-xs text-slate-500">
                                    <span>Déjalo vacío para usar el mensaje por defecto.</span>
                                    <span class="tabular-nums" x-text="`${mensajeQsl.length} / {{ \App\Http\Requests\PersonalizarTarjetaQslRequest::MENSAJE_MAXIMO }}`"></span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach (['¡Gracias por el QSO y 73!', 'TNX QSO · 73 desde Ecuador', 'Un placer el contacto. ¡Buen DX!'] as $sugerencia)
                                        <button type="button" class="rounded-full bg-white/5 px-3 py-1 text-xs text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10"
                                                x-on:click="mensajeQsl = @js($sugerencia); $nextTick(() => revisarCambios())">{{ $sugerencia }}</button>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('qsl_mensaje')" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <x-boton-guardar-perfil />
                    </div>
                </x-panel>
            </form>
        </div>
    </div>
</x-app-layout>
