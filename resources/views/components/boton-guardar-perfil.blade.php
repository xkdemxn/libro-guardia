{{-- Botón «Guardar cambios» del perfil: deshabilitado mientras el formulario no tenga cambios (fotoPerfil.hayCambios). --}}
<div class="flex items-center gap-3">
    <p x-show="! hayCambios" class="flex items-center gap-1.5 text-sm text-slate-500" role="status">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
        No hay cambios por guardar
    </p>
    <x-primary-button class="!px-7 disabled:cursor-not-allowed disabled:!brightness-100 disabled:active:!scale-100" x-bind:disabled="! hayCambios"
                      x-bind:title="hayCambios ? null : 'Modifica algún dato para poder guardar'">Guardar cambios</x-primary-button>
</div>
