{{--
    Controles de una imagen de fondo (portada del perfil o foto de la tarjeta QSL): subir/quitar, estilo y opacidad.
    Variables: $clave (fondos.{clave} en fotoPerfil), $campo (nombre del campo), $guardada, $quitarTexto, $estilos
    y, opcionales, $opacidadMaxima (50 por defecto) y $textoOpacidad.
--}}
@php
    $estado = "fondos.{$clave}";
    $opacidadMaxima ??= 50;
    $textoOpacidad ??= 'Máximo 50 %, para que tu indicativo y tus datos se sigan leyendo bien.';
@endphp

{{-- Imagen --}}
<div class="mt-6 flex flex-wrap items-center gap-3">
    <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-medium text-slate-100 transition hover:bg-white/10">
        <x-icono nombre="imagen" class="h-4 w-4" />
        <span x-text="{{ $estado }}.previa ? 'Cambiar imagen' : 'Subir imagen'">Subir imagen</span>
        <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" x-on:change="elegirFondo($event, '{{ $clave }}')">
    </label>
    <input type="file" name="{{ $campo }}" x-ref="entrada_{{ $clave }}" class="hidden" tabindex="-1" aria-hidden="true">
    @if ($guardada)
        <label class="inline-flex items-center gap-2 text-sm text-slate-400">
            <input type="checkbox" name="quitar_{{ $campo }}" value="1" x-model="{{ $estado }}.quitar" class="h-4 w-4 rounded border-white/20 bg-white/5 text-rose-500 focus:ring-rose-400/30 focus:ring-offset-0">
            {{ $quitarTexto }}
        </label>
    @endif
    <span class="text-xs text-slate-500">JPG, PNG o WEBP · se ajusta sola al tamaño de la tarjeta</span>
</div>
<x-input-error :messages="$errors->get($campo)" />

{{-- Estilo --}}
<fieldset class="mt-7" x-bind:disabled="! {{ $estado }}.previa || {{ $estado }}.quitar" x-bind:class="(! {{ $estado }}.previa || {{ $estado }}.quitar) && 'opacity-50'">
    <legend class="mb-3 text-sm font-medium text-slate-300">Estilo</legend>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        @foreach ($estilos as $estilo => [$titulo, $detalle])
            <label class="flex cursor-pointer items-start gap-3 rounded-2xl p-4 ring-1 transition"
                   x-bind:class="{{ $estado }}.estilo === '{{ $estilo }}' ? 'bg-acento-400/10 ring-acento-400/40' : 'bg-white/[0.02] ring-white/10 hover:bg-white/[0.05]'">
                <input type="radio" name="{{ $campo }}_estilo" value="{{ $estilo }}" x-model="{{ $estado }}.estilo" class="mt-0.5 h-4 w-4 border-white/20 bg-white/5 text-marca-500 focus:ring-acento-400/30 focus:ring-offset-0">
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-slate-100">{{ $titulo }}</span>
                    <span class="mt-0.5 block text-xs text-slate-500">{{ $detalle }}</span>
                </span>
                {{-- Miniatura del estilo --}}
                <span class="relative h-10 w-16 shrink-0 overflow-hidden rounded-lg bg-noche-800 ring-1 ring-white/10" aria-hidden="true">
                    <span class="absolute inset-0 bg-gradient-to-br from-amber-400 via-rose-500 to-marca-600 {{ $estilo === 'degradado' ? 'portada-degradado' : 'opacity-40' }}"></span>
                </span>
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get($campo.'_estilo')" />

    {{-- Opacidad (solo con la imagen en toda la tarjeta) --}}
    <div x-show="{{ $estado }}.estilo === 'completa'" x-cloak class="mt-5">
        <div class="flex items-center justify-between">
            <label for="{{ $campo }}_opacidad" class="text-sm font-medium text-slate-300">Opacidad</label>
            <span class="font-mono text-sm text-acento-300" x-text="`${ {{ $estado }}.opacidad } %`"></span>
        </div>
        <input id="{{ $campo }}_opacidad" type="range" name="{{ $campo }}_opacidad" min="10" max="{{ $opacidadMaxima }}" step="5"
               x-model.number="{{ $estado }}.opacidad" class="mt-2 h-1.5 w-full cursor-pointer accent-marca-500">
        <p class="mt-1 text-xs text-slate-500">{{ $textoOpacidad }}</p>
        <x-input-error :messages="$errors->get($campo.'_opacidad')" />
    </div>
</fieldset>
