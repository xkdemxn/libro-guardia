{{--
    Barras horizontales ordenadas para una sola serie (cantidad por categoría).
    Un solo color para todas las barras; el valor va como texto al final, así la lista también sirve de tabla.
--}}
@props(['datos', 'unidad' => 'contactos', 'mono' => false])

@php($maximo = max(1, $datos->max() ?? 1))

<ul {{ $attributes->merge(['class' => 'space-y-1']) }} role="list">
    @foreach ($datos as $categoria => $cantidad)
        <li class="group grid grid-cols-[7.5rem_1fr_2.75rem] items-center gap-3 rounded-lg px-1 py-1.5 transition hover:bg-white/[0.04]"
            title="{{ $categoria }}: {{ $cantidad }} {{ $unidad }}">
            <span class="truncate text-sm text-slate-300 {{ $mono ? 'font-mono' : '' }}">{{ $categoria }}</span>
            <span class="relative h-2.5 rounded-full bg-white/[0.04]" aria-hidden="true">
                <span class="absolute inset-y-0 left-0 rounded-[4px] bg-acento-400 transition group-hover:bg-acento-300"
                      style="width: {{ max(2, round($cantidad / $maximo * 100, 1)) }}%"></span>
            </span>
            <span class="text-right font-mono text-sm tabular-nums text-slate-200">{{ number_format($cantidad) }}</span>
        </li>
    @endforeach
</ul>
