<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto max-w-4xl">
            <a href="{{ route('contactos.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">&lsaquo; Volver al libro</a>
            <h1 class="mt-3 font-display text-3xl font-semibold text-white">
                Editar <span class="font-mono text-acento-300">{{ $contacto->estacion_llamada }}</span>
            </h1>
            <p class="mt-1 text-sm text-slate-500">Última modificación: {{ $contacto->updated_at->format('Y-m-d H:i') }} UTC</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 pt-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('contactos.update', $contacto) }}">
            @csrf
            @method('PUT')

            @include('contactos._form')

            <div class="mt-8 flex items-center justify-end gap-3">
                <a href="{{ route('contactos.index') }}" class="rounded-full px-5 py-2.5 text-sm font-medium text-slate-400 transition hover:text-white">Cancelar</a>
                <x-primary-button class="!px-7 !py-3">Guardar cambios</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
