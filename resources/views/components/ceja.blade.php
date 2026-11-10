{{-- Título pequeño en mayúsculas, espaciado y entre líneas finas: «—— SISTEMA WEB ——». --}}
<div {{ $attributes->merge(['class' => 'flex items-center gap-4']) }}>
    <span class="h-px flex-1 bg-gradient-to-r from-transparent to-white/15"></span>
    <span class="text-[11px] font-medium uppercase tracking-[0.28em] text-slate-400">{{ $slot }}</span>
    <span class="h-px flex-1 bg-gradient-to-l from-transparent to-white/15"></span>
</div>
