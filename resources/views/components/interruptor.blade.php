{{-- Interruptor estilo iOS. Envía value="1" cuando está activo; si está apagado no se envía nada. --}}
@props(['name', 'checked' => false, 'etiqueta', 'descripcion' => null])

<label class="flex cursor-pointer items-center justify-between gap-4 py-3">
    <span>
        <span class="block text-sm font-medium text-slate-200">{{ $etiqueta }}</span>
        @if ($descripcion)
            <span class="block text-xs text-slate-500">{{ $descripcion }}</span>
        @endif
    </span>
    <span class="relative inline-flex shrink-0">
        <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" @checked($checked) {{ $attributes }}>
        <span class="h-7 w-12 rounded-full bg-white/10 ring-1 ring-white/10 transition peer-checked:bg-emerald-500 peer-checked:ring-emerald-400/50 peer-focus-visible:ring-2 peer-focus-visible:ring-acento-400"></span>
        <span class="absolute left-0.5 top-0.5 h-6 w-6 rounded-full bg-blanco shadow-md transition peer-checked:translate-x-5"></span>
    </span>
</label>
