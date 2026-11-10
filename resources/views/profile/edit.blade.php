<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto max-w-3xl">
            <p class="text-[11px] font-medium uppercase tracking-[0.28em] text-slate-500">Cuenta</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-white">Mi perfil</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        <x-panel ceja="Datos del operador" class="sm:!p-8">
            @include('profile.partials.update-profile-information-form')
        </x-panel>

        <x-panel ceja="Seguridad" class="sm:!p-8">
            @include('profile.partials.update-password-form')
        </x-panel>

        <x-panel ceja="Zona de peligro" class="sm:!p-8">
            @include('profile.partials.delete-user-form')
        </x-panel>
    </div>
</x-app-layout>
