{{-- Estado de confirmación QSL con un puntito luminoso. --}}
@props(['estado'])

@php
[$punto, $texto, $etiqueta] = match ($estado) {
    'confirmado' => ['bg-emerald-400 shadow-[0_0_8px_rgb(52_211_153)]', 'text-emerald-300', 'Confirmado'],
    'enviada' => ['bg-amber-400 shadow-[0_0_8px_rgb(251_191_36)]', 'text-amber-300', 'Enviada'],
    default => ['bg-slate-500', 'text-slate-400', 'Pendiente'],
};
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-xs font-medium '.$texto]) }}>
    <span class="punto h-2 w-2 {{ $punto }}"></span>{{ $etiqueta }}
</span>
