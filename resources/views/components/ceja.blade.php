{{-- Título pequeño en mayúsculas, espaciado y entre líneas finas: «—— SISTEMA WEB ——». --}}
<div {{ $attributes->merge(['class' => 'flex items-center gap-2 sm:gap-4']) }}>
    <span class="h-px flex-1 bg-gradient-to-r from-transparent to-white/15"></span>
    <span class="min-w-0 text-center text-[0.6875rem] font-medium uppercase tracking-[0.16em] text-slate-400 sm:text-[0.75rem] sm:tracking-[0.28em]">{{ $slot }}</span>
    <span class="h-px flex-1 bg-gradient-to-l from-transparent to-white/15"></span>
</div>
