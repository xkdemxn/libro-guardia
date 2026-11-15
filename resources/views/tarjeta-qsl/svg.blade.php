{{--
    Tarjeta QSL en SVG (1400 × 900, proporción de tarjeta postal).
    Variables: $contacto, $operador, $verificado, $fondo (foto personalizada del operador o null) y
    $editable (opcional): en la página de la tarjeta la foto y el mensaje se enlazan con Alpine para verlos
    cambiar en vivo. El archivo descargado nunca lleva esos enlaces.
--}}
@php
    $editable ??= false;
    $miIndicativo = $operador->indicativo;
    // El indicativo se achica si es largo (ej. EA8/HC1QRC) para que no invada el bloque de la derecha.
    $tamanoIndicativo = (int) min(170, 760 / (max(1, strlen($miIndicativo)) * 0.66));
    $ubicacion = collect([$operador->ciudad, $operador->pais])->filter()->join(', ');
    $estacion = collect([
        $operador->grid_locator ? 'Grid '.$operador->grid_locator : null,
        $operador->equipo_radio,
        $operador->antena,
    ])->filter()->join('  ·  ');
    $recibida = $contacto->confirmacionesQsl->contains('recibida', true);
    $columnas = [
        ['FECHA', mb_strtoupper(str_replace('.', '', $contacto->inicio_utc->locale('es')->translatedFormat('d M Y')))],
        ['HORA UTC', $contacto->inicio_utc->format('H:i')],
        ['MHz', rtrim(rtrim(number_format($contacto->frecuencia_mhz, 3, '.', ''), '0'), '.')],
        ['BANDA', $contacto->banda->nombre],
        ['MODO', $contacto->modo->nombre],
        ['RST', $contacto->reporte_enviado],
    ];
    $fuenteTitulo = "Unbounded, Inter, 'Arial Black', Arial, sans-serif";
    $fuenteTexto = 'Inter, Arial, Helvetica, sans-serif';
    $fuenteMono = "'JetBrains Mono', 'DejaVu Sans Mono', Menlo, monospace";
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1400 900" width="1400" height="900" role="img"
     aria-label="Tarjeta QSL de {{ $miIndicativo }} para {{ $contacto->estacion_llamada }}">
    <defs>
        <linearGradient id="fondo" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#122038" />
            <stop offset="0.55" stop-color="#0a1426" />
            <stop offset="1" stop-color="#050a14" />
        </linearGradient>
        <radialGradient id="brilloAzul" cx="0.12" cy="0.1" r="0.6">
            <stop offset="0" stop-color="#2a8fff" stop-opacity="0.35" />
            <stop offset="1" stop-color="#2a8fff" stop-opacity="0" />
        </radialGradient>
        <radialGradient id="brilloAmbar" cx="0.95" cy="1" r="0.55">
            <stop offset="0" stop-color="#ffb84a" stop-opacity="0.18" />
            <stop offset="1" stop-color="#ffb84a" stop-opacity="0" />
        </radialGradient>
        <linearGradient id="indicativo" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#ffffff" />
            <stop offset="1" stop-color="#bfe0ff" />
        </linearGradient>
        @if ($fondo || $editable)
            <clipPath id="recorteTarjeta"><rect width="1400" height="900" rx="36" /></clipPath>
            {{-- Degradado: la foto se ve completa a la derecha y se desvanece hasta la mitad --}}
            <linearGradient id="desvanecerFoto" x1="1" y1="0" x2="0.4" y2="0">
                <stop offset="0" stop-color="#ffffff" />
                <stop offset="0.35" stop-color="#ffffff" stop-opacity="0.8" />
                <stop offset="1" stop-color="#ffffff" stop-opacity="0" />
            </linearGradient>
            <mask id="mascaraFoto"><rect width="1400" height="900" fill="url(#desvanecerFoto)" /></mask>
            {{-- Sombra abajo para que los datos y el indicativo destinatario se lean sobre la foto --}}
            <linearGradient id="sombraInferior" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0.35" stop-color="#050a14" stop-opacity="0" />
                <stop offset="1" stop-color="#050a14" stop-opacity="0.85" />
            </linearGradient>
        @endif
    </defs>

    {{-- Fondo --}}
    <rect width="1400" height="900" rx="36" fill="url(#fondo)" />
    <rect width="1400" height="900" rx="36" fill="url(#brilloAzul)" />
    <rect width="1400" height="900" rx="36" fill="url(#brilloAmbar)" />
    @if ($editable)
        {{-- Foto en vivo: fondos.qsl viene del componente fotoPerfil de la página --}}
        <g clip-path="url(#recorteTarjeta)" x-show="fondos.qsl.previa && ! fondos.qsl.quitar" @unless ($fondo) style="display: none" @endunless>
            <image x="0" y="0" width="1400" height="900" preserveAspectRatio="xMidYMid slice"
                   x-bind:href="fondos.qsl.previa"
                   x-bind:mask="fondos.qsl.estilo === 'degradado' ? 'url(#mascaraFoto)' : null"
                   x-bind:opacity="fondos.qsl.estilo === 'completa' ? fondos.qsl.opacidad / 100 : 1" />
            <rect width="1400" height="900" fill="url(#sombraInferior)" />
        </g>
    @elseif ($fondo)
        {{-- Foto personalizada del operador --}}
        <g clip-path="url(#recorteTarjeta)">
            <image href="{{ $fondo['data'] }}" x="0" y="0" width="1400" height="900" preserveAspectRatio="xMidYMid slice"
                   @if ($fondo['estilo'] === 'degradado') mask="url(#mascaraFoto)" @else opacity="{{ $fondo['opacidad'] / 100 }}" @endif />
            <rect width="1400" height="900" fill="url(#sombraInferior)" />
        </g>
    @endif
    <rect x="18" y="18" width="1364" height="864" rx="26" fill="none" stroke="#ffffff" stroke-opacity="0.12" stroke-width="2" />

    {{-- Ondas de radio saliendo de la antena (esquina superior derecha); con foto a la derecha no se dibujan --}}
    @unless (! $editable && $fondo && $fondo['estilo'] === 'degradado')
    <g @if ($editable) x-show="! (fondos.qsl.previa && ! fondos.qsl.quitar && fondos.qsl.estilo === 'degradado')" @endif>
    <g fill="none" stroke="#52adff" stroke-linecap="round">
        @foreach ([70 => 0.55, 125 => 0.4, 180 => 0.28, 235 => 0.18, 290 => 0.1] as $radio => $opacidad)
            <circle cx="1240" cy="150" r="{{ $radio }}" stroke-opacity="{{ $opacidad }}" stroke-width="3" />
        @endforeach
    </g>
    <g transform="translate(1210 112) scale(1.25)" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
        <path d="M24 22 16 44M24 22l8 22M18.5 37h11" />
        <circle cx="24" cy="18" r="3.5" fill="#ffffff" stroke="none" />
        <path d="M16.5 10.5a10.5 10.5 0 0 0 0 15M31.5 10.5a10.5 10.5 0 0 1 0 15" />
    </g>
    </g>
    @endunless

    {{-- Encabezado --}}
    <rect x="80" y="76" width="96" height="44" rx="22" fill="#2a8fff" />
    <text x="128" y="106" text-anchor="middle" font-family="{{ $fuenteTitulo }}" font-size="22" font-weight="700" fill="#ffffff" letter-spacing="3">QSL</text>
    <text x="196" y="106" font-family="{{ $fuenteTexto }}" font-size="18" font-weight="600" fill="#9fb3d3" letter-spacing="6">LIBRO DE GUARDIA</text>

    {{-- Mi estación --}}
    <text x="76" y="{{ 180 + $tamanoIndicativo }}" font-family="{{ $fuenteTitulo }}" font-size="{{ $tamanoIndicativo }}" font-weight="700" fill="url(#indicativo)" letter-spacing="2">{{ $miIndicativo }}</text>
    <text x="82" y="{{ 240 + $tamanoIndicativo }}" font-family="{{ $fuenteTexto }}" font-size="32" font-weight="500" fill="#e8eef8">{{ $operador->nombre_completo }}@if ($ubicacion) <tspan fill="#7189b3">·  {{ $ubicacion }}</tspan>@endif</text>
    @if ($estacion)
        <text x="82" y="{{ 288 + $tamanoIndicativo }}" font-family="{{ $fuenteMono }}" font-size="22" fill="#7189b3">{{ $estacion }}</text>
    @endif

    {{-- Estación destinataria --}}
    <text x="1320" y="490" text-anchor="end" font-family="{{ $fuenteTexto }}" font-size="18" font-weight="600" fill="#9fb3d3" letter-spacing="6">PARA LA ESTACIÓN</text>
    <text x="1320" y="560" text-anchor="end" font-family="{{ $fuenteMono }}" font-size="64" font-weight="700" fill="#ffb84a">{{ $contacto->estacion_llamada }}</text>

    {{-- Datos del contacto --}}
    <rect x="80" y="600" width="1240" height="150" rx="24" fill="#ffffff" fill-opacity="0.05" stroke="#ffffff" stroke-opacity="0.14" stroke-width="2" />
    @foreach ($columnas as $indice => [$titulo, $valor])
        @php($centro = 80 + 1240 / 6 * ($indice + 0.5))
        @if ($indice > 0)
            <line x1="{{ 80 + 1240 / 6 * $indice }}" y1="625" x2="{{ 80 + 1240 / 6 * $indice }}" y2="725" stroke="#ffffff" stroke-opacity="0.1" stroke-width="2" />
        @endif
        <text x="{{ $centro }}" y="655" text-anchor="middle" font-family="{{ $fuenteTexto }}" font-size="16" font-weight="600" fill="#7189b3" letter-spacing="4">{{ $titulo }}</text>
        <text x="{{ $centro }}" y="708" text-anchor="middle" font-family="{{ $fuenteMono }}" font-size="{{ mb_strlen($valor) > 9 ? 28 : 36 }}" font-weight="600" fill="#ffffff">{{ $valor }}</text>
    @endforeach

    {{-- Pie --}}
    <text x="82" y="820" font-family="{{ $fuenteTexto }}" font-size="{{ mb_strlen($operador->mensajeQsl()) > 45 ? 22 : 28 }}" fill="#e2e9f5"
          @if ($editable) x-text="mensajeQsl.trim() || @js(\App\Models\User::MENSAJE_QSL_PREDETERMINADO)" x-bind:font-size="(mensajeQsl.trim() || @js(\App\Models\User::MENSAJE_QSL_PREDETERMINADO)).length > 45 ? 22 : 28" @endif>{{ $operador->mensajeQsl() }}</text>
    <text x="82" y="852" font-family="{{ $fuenteMono }}" font-size="18" fill="#4d6690">{{ $recibida ? 'TNX QSL' : 'PSE QSL TNX' }}  ·  QSO #{{ $contacto->id }}</text>

    @if ($verificado)
        <g transform="translate(1320 812)">
            <rect x="-372" y="-34" width="372" height="52" rx="26" fill="#34d399" fill-opacity="0.12" stroke="#34d399" stroke-opacity="0.45" stroke-width="2" />
            <circle cx="-346" cy="-8" r="7" fill="#34d399" />
            <text x="-24" y="0" text-anchor="end" font-family="{{ $fuenteTexto }}" font-size="20" font-weight="600" fill="#a7f3d0">Verificado en Libro de Guardia</text>
        </g>
    @endif
</svg>
