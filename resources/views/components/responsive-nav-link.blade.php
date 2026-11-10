@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex w-full items-center gap-3 rounded-xl bg-white/10 px-4 py-2.5 text-start text-base font-semibold text-white transition'
            : 'flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-start text-base font-medium text-slate-400 transition hover:bg-white/5 hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
