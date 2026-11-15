<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto max-w-7xl">
            <a href="{{ route('contactos.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">&lsaquo; Volver al libro</a>
            <h1 class="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">Nuevo contacto</h1>
            <p class="mt-1 text-slate-400">Anota el QSO mientras ocurre: el botón «Ahora» pone la hora UTC.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('contactos.store') }}">
            @csrf

            @include('contactos._form')

            <div class="mt-8 flex items-center justify-end gap-3">
                <a href="{{ route('contactos.index') }}" class="rounded-full px-5 py-2.5 text-sm font-medium text-slate-400 transition hover:text-white">Cancelar</a>
                <x-primary-button class="!px-7 !py-3">Guardar contacto</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
