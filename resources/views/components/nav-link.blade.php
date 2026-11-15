@props(['active'])

{{-- Pestaña de un control segmentado: la activa es una píldora de vidrio iluminada. --}}
@php
$classes = ($active ?? false)
            ? 'inline-flex items-center gap-2 whitespace-nowrap rounded-full bg-white/10 px-4 py-1.5 text-sm font-semibold text-white shadow-[inset_0_1px_0_0_rgb(255_255_255/0.12)] ring-1 ring-white/10 transition'
            : 'inline-flex items-center gap-2 whitespace-nowrap rounded-full px-4 py-1.5 text-sm font-medium text-slate-400 transition hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
