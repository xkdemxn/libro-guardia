{{--
    Perfil del operador (US-13), repartido en tres secciones del menú lateral: datos, estación y privacidad.
    Es un solo formulario: las secciones ocultas también se envían, así ningún dato se pierde al guardar.
--}}
@php($visibilidad = $user->visibilidad)

<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

{{-- novalidate: un campo obligatorio en una sección oculta bloquearía el envío sin mostrar nada; valida el servidor. --}}
<form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" novalidate
      x-data="fotoPerfil(@js($user->foto_url), @js($errors->any()))"
      x-on:input="revisarCambios()" x-on:change="revisarCambios()">
    @csrf
    @method('patch')
    <input type="hidden" name="seccion" :value="seccion">

    {{-- Datos personales --}}
    <x-panel x-show="seccion === 'datos'" x-cloak class="sm:!p-8">
        <header class="mb-8">
            <h2 class="font-display text-lg font-semibold text-white">Datos personales</h2>
            <p class="mt-1 text-sm text-slate-400">Cómo te identifican los demás operadores.</p>
        </header>

        <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
            <div class="relative h-24 w-24 shrink-0 overflow-hidden rounded-3xl bg-gradient-to-br from-marca-400 to-marca-600 ring-1 ring-white/10">
                <template x-if="vistaPrevia && ! quitar">
                    <img :src="vistaPrevia" alt="Foto de perfil" class="h-full w-full object-cover">
                </template>
                <span x-show="! vistaPrevia || quitar" class="grid h-full w-full place-items-center font-display text-2xl font-semibold text-blanco">
                    {{ mb_strtoupper(mb_substr($user->nombres, 0, 1).mb_substr($user->apellidos, 0, 1)) }}
                </span>
            </div>
            @include('profile.partials.editor-foto')

            <div class="flex-1">
                <p class="flex items-center gap-2.5 font-mono text-2xl font-semibold tracking-wide text-white">{{ $user->indicativo }} <x-bandera :codigo="$user->codigoBandera()" :pais="$user->pais" class="!h-4 !w-[1.35rem]" /></p>
                <p class="text-xs text-slate-500">El indicativo no se puede cambiar.</p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-medium text-slate-100 transition hover:bg-white/10">
                        Cambiar foto
                        {{-- Este selector no se envía: la foto recortada va en el campo oculto «foto». --}}
                        <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" x-on:change="elegir($event)">
                    </label>
                    <input type="file" name="foto" x-ref="entrada" class="hidden" tabindex="-1" aria-hidden="true">
                    <button type="button" x-show="vistaPrevia && ! quitar" x-on:click="ajustar()"
                            class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium text-acento-300 transition hover:bg-acento-400/10">
                        Recortar y mover
                    </button>
                    @if ($user->foto_path)
                        <label class="inline-flex items-center gap-2 text-sm text-slate-400">
                            <input type="checkbox" name="quitar_foto" value="1" x-model="quitar" class="h-4 w-4 rounded border-white/20 bg-white/5 text-rose-500 focus:ring-rose-400/30 focus:ring-offset-0">
                            Quitar foto
                        </label>
                    @endif
                    <span class="text-xs text-slate-500">JPG, PNG o WEBP · la puedes recortar antes de guardar</span>
                </div>
                <x-input-error :messages="$errors->get('foto')" />
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="nombres" value="Nombres" />
                <x-text-input id="nombres" name="nombres" type="text" :value="old('nombres', $user->nombres)" required autocomplete="given-name" />
                <x-input-error :messages="$errors->get('nombres')" />
            </div>
            <div>
                <x-input-label for="apellidos" value="Apellidos" />
                <x-text-input id="apellidos" name="apellidos" type="text" :value="old('apellidos', $user->apellidos)" required autocomplete="family-name" />
                <x-input-error :messages="$errors->get('apellidos')" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="email" value="Correo electrónico" />
                <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
                <p class="mt-1.5 text-xs text-slate-500">Nunca se muestra en tu perfil público.</p>
                <x-input-error :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <p class="mt-2 text-sm text-slate-300">
                        Tu correo no está verificado.
                        <button form="send-verification" class="text-acento-300 hover:text-acento-200">Reenviar el enlace de verificación</button>
                    </p>
                @endif
            </div>
            <div>
                <x-input-label for="pais" value="País" />
                <x-text-input id="pais" name="pais" type="text" :value="old('pais', $user->pais)" autocomplete="country-name" placeholder="Ecuador" />
                <p class="mt-1.5 text-xs text-slate-500">Si lo dejas vacío se toma del prefijo de tu indicativo.</p>
                <x-input-error :messages="$errors->get('pais')" />
            </div>
            <div>
                <x-input-label for="ciudad" value="Ciudad" />
                <x-text-input id="ciudad" name="ciudad" type="text" :value="old('ciudad', $user->ciudad)" autocomplete="address-level2" />
                <x-input-error :messages="$errors->get('ciudad')" />
            </div>
        </div>

        <div class="mt-8 flex justify-end">
            <x-boton-guardar-perfil />
        </div>
    </x-panel>

    {{-- Mi estación --}}
    <x-panel x-show="seccion === 'estacion'" x-cloak class="sm:!p-8">
        <header class="mb-8">
            <h2 class="font-display text-lg font-semibold text-white">Mi estación</h2>
            <p class="mt-1 text-sm text-slate-400">Tu ubicación por grid se usa para calcular las distancias del mapa.</p>
        </header>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div>
                <x-input-label for="grid_locator" value="Grid locator" />
                <x-text-input id="grid_locator" name="grid_locator" type="text" class="font-mono uppercase" :value="old('grid_locator', $user->grid_locator)" maxlength="6" placeholder="FI09" />
                <x-input-error :messages="$errors->get('grid_locator')" />
            </div>
            <div>
                <x-input-label for="equipo_radio" value="Equipo de radio" />
                <x-text-input id="equipo_radio" name="equipo_radio" type="text" :value="old('equipo_radio', $user->equipo_radio)" placeholder="Yaesu FT-991A" />
                <x-input-error :messages="$errors->get('equipo_radio')" />
            </div>
            <div>
                <x-input-label for="antena" value="Antena" />
                <x-text-input id="antena" name="antena" type="text" :value="old('antena', $user->antena)" placeholder="Dipolo 40 m" />
                <x-input-error :messages="$errors->get('antena')" />
            </div>
            <div class="sm:col-span-3">
                <x-input-label for="biografia" value="Biografía" />
                <textarea id="biografia" name="biografia" rows="5" maxlength="1000" class="campo" placeholder="Cuéntale a otros operadores sobre ti y tu estación.">{{ old('biografia', $user->biografia) }}</textarea>
                <x-input-error :messages="$errors->get('biografia')" />
            </div>
        </div>

        <div class="mt-8 flex justify-end">
            <x-boton-guardar-perfil />
        </div>
    </x-panel>

    {{-- Privacidad --}}
    <x-panel x-show="seccion === 'privacidad'" x-cloak class="sm:!p-8">
        <header class="mb-6">
            <h2 class="font-display text-lg font-semibold text-white">Privacidad</h2>
            <p class="mt-1 text-sm text-slate-400">Tu indicativo y tu país siempre son visibles. Tu correo y tus contactos nunca lo son.</p>
        </header>

        <div class="divide-y divide-white/5">
            <x-interruptor name="mostrar_nombre" :checked="old('mostrar_nombre', $visibilidad->mostrar_nombre)" etiqueta="Nombre y apellidos" />
            <x-interruptor name="mostrar_pais_ciudad" :checked="old('mostrar_pais_ciudad', $visibilidad->mostrar_pais_ciudad)" etiqueta="Ciudad" />
            <x-interruptor name="mostrar_equipo" :checked="old('mostrar_equipo', $visibilidad->mostrar_equipo)" etiqueta="Equipo y antena" />
            <x-interruptor name="mostrar_biografia_foto" :checked="old('mostrar_biografia_foto', $visibilidad->mostrar_biografia_foto)" etiqueta="Biografía y foto" />
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('perfil-publico.show', $user->indicativo) }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-acento-300 hover:text-acento-200">
                Ver cómo queda mi perfil público
                <x-icono nombre="enlace-externo" class="h-4 w-4" />
            </a>
            <x-boton-guardar-perfil />
        </div>
    </x-panel>
</form>
