{{--
    País, provincia (solo Ecuador) y ciudad como desplegables. Las opciones iniciales salen del servidor
    para que el formulario no cuente como cambiado al abrir; al cambiar de país o provincia las ciudades
    se rellenan en el navegador (componente ubicacionPerfil en resources/js/app.js).
--}}
@php
    use App\Support\Ubicaciones;

    $paisPrefijo = \App\Models\PrefijoPais::paraIndicativo($user->indicativo)?->pais;
    $paisElegido = old('pais', $user->pais);
    $paisEfectivo = $paisElegido ?: $paisPrefijo;
    $esEcuador = Ubicaciones::esEcuador($paisEfectivo);
    $provinciaElegida = $esEcuador ? old('provincia', $user->provincia) : null;
    $ciudadElegida = old('ciudad', $user->ciudad);
    $paises = Ubicaciones::paises();
    // Ecuador sin provincia elegida: los cantones agrupados por provincia.
    $grupos = $esEcuador && ! $provinciaElegida
        ? config('ubicaciones.ecuador')
        : ['' => Ubicaciones::ciudades($paisEfectivo, $provinciaElegida)];
    $ciudadFueraDeLista = $ciudadElegida && ! in_array($ciudadElegida, array_merge(...array_values($grupos)), true);
@endphp

<div class="grid grid-cols-1 gap-5 sm:col-span-2 sm:grid-cols-3"
     x-data="ubicacionPerfil(@js(Ubicaciones::paraFormulario()), @js($paisPrefijo))">
    <div>
        <x-input-label for="pais" value="País" />
        <select id="pais" name="pais" x-ref="pais" x-on:change="cambiarPais()" class="campo">
            <option value="">{{ $paisPrefijo ? "{$paisPrefijo} (automático)" : 'Elige un país' }}</option>
            @if ($paisElegido && ! in_array($paisElegido, $paises, true))
                <option value="{{ $paisElegido }}" selected>{{ $paisElegido }}</option>
            @endif
            @foreach ($paises as $pais)
                <option value="{{ $pais }}" @selected($pais === $paisElegido)>{{ $pais }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('pais')" />
    </div>

    <div x-show="esEcuador" @unless ($esEcuador) style="display: none" @endunless>
        <x-input-label for="provincia" value="Provincia" />
        <select id="provincia" name="provincia" x-ref="provincia" x-on:change="cambiarProvincia()" x-bind:disabled="! esEcuador" class="campo" @disabled(! $esEcuador)>
            <option value="">Elige una provincia</option>
            @foreach (Ubicaciones::provincias() as $provincia)
                <option value="{{ $provincia }}" @selected($provincia === $provinciaElegida)>{{ $provincia }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('provincia')" />
    </div>

    <div>
        <x-input-label for="ciudad" x-text="esEcuador ? 'Ciudad o cantón' : 'Ciudad'" :value="$esEcuador ? 'Ciudad o cantón' : 'Ciudad'" />
        <select id="ciudad" name="ciudad" x-ref="ciudad" class="campo">
            <option value="">Elige una ciudad</option>
            @if ($ciudadFueraDeLista)
                <option value="{{ $ciudadElegida }}" selected>{{ $ciudadElegida }}</option>
            @endif
            @foreach ($grupos as $grupo => $ciudades)
                @if ($grupo !== '')<optgroup label="{{ $grupo }}">@endif
                @foreach ($ciudades as $ciudad)
                    <option value="{{ $ciudad }}" @selected($ciudad === $ciudadElegida)>{{ $ciudad }}</option>
                @endforeach
                @if ($grupo !== '')</optgroup>@endif
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('ciudad')" />
    </div>
</div>
