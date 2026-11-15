{{-- Reloj UTC en vivo con aspecto de display de radio. --}}
@props(['grande' => false])

<div x-data="relojUtc" {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-2 rounded-full border border-dial-400/20 bg-black/30 font-mono text-dial-400 '.($grande ? 'px-5 py-2' : 'px-3 py-1')]) }}>
    <span class="{{ $grande ? 'text-xs' : 'text-[10px]' }} font-sans font-semibold uppercase tracking-[0.2em] text-dial-300/70">UTC</span>
    <span class="{{ $grande ? 'text-3xl' : 'text-sm' }} font-semibold tabular-nums tracking-wider [text-shadow:0_0_12px_rgb(255_184_74/0.45)]" x-text="hora" role="timer" aria-label="Hora UTC actual">--:--:--</span>
    @if ($grande)
        <span class="whitespace-nowrap text-xs tabular-nums text-slate-500" x-text="fecha"></span>
    @endif
</div>
