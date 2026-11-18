{{-- Solicitudes de confirmación: contactos que otras estaciones anotaron contigo y que no están en tu libro. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto max-w-3xl">
            <p class="text-[0.75rem] font-medium uppercase tracking-[0.28em] text-slate-500">Confirmación QSL</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-white sm:text-4xl">Solicitudes de confirmación</h1>
            <p class="mt-1 text-slate-400">Otras estaciones registraron estos contactos contigo. Si los reconoces, agrégalos a tu libro y quedan confirmados para los dos.</p>
        </div>
    </x-slot>

    <div class="mx-auto box-content max-w-3xl space-y-4 px-4 pt-8 sm:px-6 lg:px-8">
        @forelse ($solicitudes as $solicitud)
            <article class="vidrio p-5 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-400">
                            <a href="{{ route('perfil-publico.show', $solicitud->user->indicativo) }}" class="font-mono text-lg font-semibold text-white hover:text-acento-300">{{ $solicitud->user->indicativo }}</a>
                            dice que habló contigo
                        </p>
                        <p class="mt-1 font-mono text-sm text-slate-300">
                            {{ $solicitud->inicio_utc->format('Y-m-d') }} · {{ $solicitud->inicio_utc->format('H:i') }}{{ $solicitud->fin_utc ? ' → '.$solicitud->fin_utc->format('H:i') : '' }} UTC
                        </p>
                    </div>
                    <span class="font-mono text-xs text-slate-600">{{ sprintf('%02d', $loop->iteration) }} / {{ sprintf('%02d', $loop->count) }}</span>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                    <x-banda-chip :banda="$solicitud->banda->nombre" />
                    <span class="chip border-white/10 bg-white/5 text-slate-300">{{ $solicitud->modo->etiqueta }}</span>
                    <span class="font-mono text-acento-300">{{ number_format($solicitud->frecuencia_mhz, 3) }} MHz</span>
                    {{-- Visto desde tu lado: lo que ellos enviaron es lo que tú recibiste. --}}
                    <span class="text-slate-400">· Tú enviaste <span class="font-mono text-slate-200">{{ $solicitud->reporte_recibido }}</span>, recibiste <span class="font-mono text-slate-200">{{ $solicitud->reporte_enviado }}</span></span>
                </div>

                <div class="mt-5 flex flex-wrap justify-end gap-3 border-t border-white/5 pt-5">
                    <form method="POST" action="{{ route('solicitudes.rechazar', $solicitud) }}">
                        @csrf
                        <x-secondary-button type="submit">No fui yo</x-secondary-button>
                    </form>
                    <form method="POST" action="{{ route('solicitudes.aceptar', $solicitud) }}">
                        @csrf
                        <x-primary-button>Sí, confirmar y agregar a mi libro</x-primary-button>
                    </form>
                </div>
            </article>
        @empty
            <div class="vidrio px-6 py-14 text-center" role="status">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-400/10 text-emerald-300 ring-1 ring-emerald-400/25">
                    <svg class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                </span>
                <h2 class="mt-5 font-display text-lg font-semibold text-white">No tienes solicitudes pendientes.</h2>
                <p class="mt-1 text-sm text-slate-400">Cuando otra estación registre un contacto contigo que no esté en tu libro, aparecerá aquí.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
