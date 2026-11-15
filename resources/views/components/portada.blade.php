{{-- Imagen de fondo de la tarjeta del perfil público. $portada = ['url', 'estilo', 'opacidad'] o null. --}}
@props(['portada' => null])

@if ($portada)
    <img src="{{ $portada['url'] }}" alt="" aria-hidden="true" decoding="async"
         {{ $attributes->class(['portada', 'portada-degradado' => $portada['estilo'] === 'degradado']) }}
         @if ($portada['estilo'] === 'completa') style="opacity: {{ min(50, max(10, (int) $portada['opacidad'])) / 100 }}" @endif>
@endif
