{{-- Buscador público de indicativos (US-15). Si viene $buscado, es porque ese indicativo no está registrado. --}}
<x-publico-layout>
    <div class="text-center">
        <p class="text-[11px] font-medium uppercase tracking-[0.28em] text-slate-500">Directorio de operadores</p>
        <h1 class="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">Buscar un indicativo</h1>
        <p class="mt-2 text-slate-400">Conoce a la persona con quien hiciste contacto.</p>
    </div>

    <form method="GET" action="{{ route('perfil-publico.index') }}" class="vidrio mx-auto mt-10 flex max-w-xl gap-2 !rounded-full p-2">
        <label for="indicativo" class="sr-only">Indicativo</label>
        <input id="indicativo" name="indicativo" type="search" value="{{ $buscado ?? '' }}" required maxlength="20" autofocus
               placeholder="HC1QRC" class="min-w-0 flex-1 rounded-full border-0 bg-transparent px-5 font-mono text-lg uppercase tracking-wider text-white placeholder:text-slate-600 focus:ring-0">
        <x-primary-button>Buscar</x-primary-button>
    </form>

    @isset($buscado)
        <div class="vidrio mx-auto mt-8 max-w-xl p-6 text-center" role="status">
            <p class="font-mono text-2xl font-semibold text-white">{{ $buscado }}</p>
            <p class="mt-2 text-slate-300">Este indicativo no está registrado.</p>
            @if ($prefijoBuscado)
                <p class="mt-1 inline-flex items-center gap-2 text-sm text-slate-500">
                    <x-bandera :codigo="$prefijoBuscado->codigo_bandera" :pais="$prefijoBuscado->pais" />
                    Por su prefijo, es una estación de {{ $prefijoBuscado->pais }}.
                </p>
            @endif
        </div>
    @endisset
</x-publico-layout>
