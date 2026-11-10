{{-- Paginación con botones de vidrio oscuro. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-slate-500">
            Mostrando <span class="font-medium text-slate-300">{{ $paginator->firstItem() }}</span>
            a <span class="font-medium text-slate-300">{{ $paginator->lastItem() }}</span>
            de <span class="font-medium text-slate-300">{{ $paginator->total() }}</span>
        </p>

        <div class="flex items-center gap-1 rounded-full bg-black/25 p-1 ring-1 ring-white/5">
            @if ($paginator->onFirstPage())
                <span class="grid h-8 w-8 place-items-center rounded-full text-slate-600" aria-disabled="true" aria-label="Anterior">&lsaquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid h-8 w-8 place-items-center rounded-full text-slate-300 transition hover:bg-white/10" aria-label="Anterior">&lsaquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-sm text-slate-600">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="grid h-8 min-w-[2rem] place-items-center rounded-full bg-white/10 px-2.5 font-mono text-sm font-semibold text-white ring-1 ring-white/10">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="grid h-8 min-w-[2rem] place-items-center rounded-full px-2.5 font-mono text-sm text-slate-400 transition hover:bg-white/10 hover:text-white">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid h-8 w-8 place-items-center rounded-full text-slate-300 transition hover:bg-white/10" aria-label="Siguiente">&rsaquo;</a>
            @else
                <span class="grid h-8 w-8 place-items-center rounded-full text-slate-600" aria-disabled="true" aria-label="Siguiente">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif
