{{-- Perfil público de un operador (US-15). Solo muestra lo que el operador decidió hacer público. --}}
<x-publico-layout>
    <a href="{{ route('perfil-publico.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">&lsaquo; Buscar otro indicativo</a>

    <section class="vidrio mt-4 overflow-hidden !p-0">
        <div class="flex flex-col items-center gap-6 bg-gradient-to-b from-acento-500/10 to-transparent px-6 pb-8 pt-10 text-center sm:flex-row sm:text-left">
            <div class="h-28 w-28 shrink-0 overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-600 to-slate-800 ring-1 ring-white/15">
                @if ($perfil['foto_url'])
                    <img src="{{ $perfil['foto_url'] }}" alt="Foto de {{ $perfil['indicativo'] }}" class="h-full w-full object-cover">
                @else
                    <span class="grid h-full w-full place-items-center">
                        <x-application-logo class="h-12 w-12 text-slate-400" />
                    </span>
                @endif
            </div>
            <div>
                <h1 class="font-mono text-4xl font-semibold tracking-wide text-white">{{ $perfil['indicativo'] }}</h1>
                @if ($perfil['nombre'])
                    <p class="mt-1 text-lg text-slate-200">{{ $perfil['nombre'] }}</p>
                @endif
                <p class="mt-1 text-slate-400">
                    {{ collect([$perfil['ciudad'], $perfil['pais']])->filter()->join(', ') ?: 'País desconocido' }}
                </p>
            </div>
        </div>

        @if ($perfil['equipo_radio'] || $perfil['antena'] || $perfil['biografia'])
            <div class="space-y-6 border-t border-white/5 p-6">
                @if ($perfil['equipo_radio'] || $perfil['antena'])
                    <div>
                        <x-ceja class="mb-4">Estación</x-ceja>
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @if ($perfil['equipo_radio'])
                                <div><dt class="text-xs text-slate-500">Equipo</dt><dd class="mt-1 text-slate-100">{{ $perfil['equipo_radio'] }}</dd></div>
                            @endif
                            @if ($perfil['antena'])
                                <div><dt class="text-xs text-slate-500">Antena</dt><dd class="mt-1 text-slate-100">{{ $perfil['antena'] }}</dd></div>
                            @endif
                        </dl>
                    </div>
                @endif
                @if ($perfil['biografia'])
                    <div>
                        <x-ceja class="mb-4">Sobre mí</x-ceja>
                        <p class="whitespace-pre-line leading-relaxed text-slate-300">{{ $perfil['biografia'] }}</p>
                    </div>
                @endif
            </div>
        @endif
    </section>

    @auth
        @if (auth()->user()->indicativo === $perfil['indicativo'])
            <p class="mt-4 text-center text-sm text-slate-500">
                Así ven tu perfil los demás. <a href="{{ route('profile.edit') }}" class="text-acento-300 hover:text-acento-200">Cambiar qué se muestra</a>
            </p>
        @endif
    @endauth
</x-publico-layout>
