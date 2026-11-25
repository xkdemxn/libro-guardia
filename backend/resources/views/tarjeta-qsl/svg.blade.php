{{--
    Tarjeta QSL en SVG (1400 × 900), al estilo de las tarjetas impresas clásicas: foto de borde a borde, el
    indicativo grande arriba, el mensaje abajo a la izquierda, el emblema y la bandera abajo a la derecha y una
    franja amarilla con país, ciudad y zonas. El recuadro de arriba a la derecha confirma el QSO.
    Variables: $contacto, $operador, $verificado, $fondo (foto del operador o null), $remitente (datos de la franja,
    bandera y emblema), $diseno (opcional; si no, el guardado por el operador) y $editable (opcional): para la vista previa de la SPA la foto y las líneas del mensaje llevan
    id / data-linea, así se ven cambiar en vivo. El archivo descargado nunca los lleva.
--}}
@php
    $editable ??= false;
    $miIndicativo = $operador->indicativo;
    $tamanoIndicativo = (int) min(150, 900 / (max(1, mb_strlen($miIndicativo)) * 0.78));
    $recibida = $contacto->confirmacionesQsl->contains('recibida', true);
    $datosQso = [
        ['FECHA', mb_strtoupper(str_replace('.', '', $contacto->inicio_utc->locale('es')->translatedFormat('d M Y')))],
        ['HORA UTC', $contacto->inicio_utc->format('H:i')],
        ['MHz', rtrim(rtrim(number_format($contacto->frecuencia_mhz, 3, '.', ''), '0'), '.')],
        ['BANDA', $contacto->banda->nombre],
        ['MODO', $contacto->modo->etiqueta],
        ['RST', $contacto->reporte_enviado],
    ];
    $lineas = $operador->lineasMensajeQsl();
    $estacion = collect([$operador->equipo_radio, $operador->antena])->filter()->join('  ·  ');
    $franja = $remitente['franja'];
    $tamanoFranja = round(min(22, 1000 / (max(1, mb_strlen($franja)) * 0.52)), 1);
    $tamanoInsignia = (int) min(30, 138 / (max(1, mb_strlen($miIndicativo)) * 0.62));
    $fuenteTitulo = "'Arial Black', 'Archivo Black', Unbounded, Impact, Arial, sans-serif";
    $fuenteTexto = 'Inter, Arial, Helvetica, sans-serif';
    $fuenteMono = "'JetBrains Mono', 'DejaVu Sans Mono', Menlo, monospace";
    $mensajePredeterminado = \App\Models\User::MENSAJE_QSL_PREDETERMINADO;

    // Distribución elegida por el operador (o la que se está previsualizando). Todas dejan el recuadro del QSO
    // completo y legible; solo cambian de lugar los bloques. La franja inferior es igual en todas.
    $diseno ??= $operador->qsl_diseno;
    $t = $tamanoIndicativo;
    $d = match ($diseno) {
        // Recuadro a la izquierda, indicativo a la derecha; mensaje a la derecha y sello a la izquierda.
        'invertido' => [
            'indicativo' => ['x' => 1340, 'y' => 50 + $t, 'anclaje' => 'end', 'sangria' => -6],
            'recuadro' => [60, 50], 'verificado' => [60, 362],
            'mensaje' => ['x' => 1360, 'anclaje' => 'end'], 'sello' => 'izquierda',
            'sombras' => [[0, 300], [560, 280]],
        ],
        // Arriba queda libre para la foto: indicativo y recuadro bajan a la mitad inferior.
        'inferior' => [
            'indicativo' => ['x' => 60, 'y' => 590, 'anclaje' => 'start', 'sangria' => 6],
            'recuadro' => [840, 300], 'verificado' => [1000, 272],
            'mensaje' => ['x' => 40, 'anclaje' => 'start'], 'sello' => 'derecha',
            'sombras' => [[0, 0], [300, 540]],
        ],
        // Indicativo grande al centro y el recuadro debajo.
        'centrado' => [
            'indicativo' => ['x' => 700, 'y' => 40 + $t, 'anclaje' => 'middle', 'sangria' => 0],
            'recuadro' => [450, 300], 'verificado' => [530, 610],
            'mensaje' => ['x' => 40, 'anclaje' => 'start'], 'sello' => 'derecha',
            'sombras' => [[0, 300], [480, 360]],
        ],
        // Clásico: indicativo arriba a la izquierda y recuadro arriba a la derecha.
        default => [
            'indicativo' => ['x' => 60, 'y' => 50 + $t, 'anclaje' => 'start', 'sangria' => 6],
            'recuadro' => [840, 50], 'verificado' => [1000, 362],
            'mensaje' => ['x' => 40, 'anclaje' => 'start'], 'sello' => 'derecha',
            'sombras' => [[0, 300], [560, 280]],
        ],
    };
    $ind = $d['indicativo'];
    // En «inferior» el nombre y el equipo van debajo del indicativo, igual que en los demás, pero más juntos.
    $separacion = $diseno === 'inferior' ? [44, 78] : [50, 90];
    $selloIzquierda = $d['sello'] === 'izquierda';
    $xBandera = $selloIzquierda ? 50 : 1200;
    $xEmblema = $remitente['bandera'] ? ($selloIzquierda ? 218 : 1005) : ($selloIzquierda ? 50 : 1175);
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1400 900" width="1400" height="900" role="img"
     aria-label="Tarjeta QSL de {{ $miIndicativo }} para {{ $contacto->estacion_llamada }}">
    <defs>
        {{-- Fondo por defecto: atardecer andino --}}
        <linearGradient id="cielo" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#0b1730" />
            <stop offset="0.35" stop-color="#2b3f6b" />
            <stop offset="0.58" stop-color="#8a5a6e" />
            <stop offset="0.7" stop-color="#e58a3c" />
            <stop offset="0.78" stop-color="#f7b955" />
        </linearGradient>
        <radialGradient id="sol" cx="0.22" cy="0.74" r="0.32">
            <stop offset="0" stop-color="#ffe7a3" stop-opacity="0.95" />
            <stop offset="0.25" stop-color="#ffb347" stop-opacity="0.55" />
            <stop offset="1" stop-color="#ff8a3d" stop-opacity="0" />
        </radialGradient>
        <linearGradient id="sombraSuperior" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#000000" stop-opacity="0.55" />
            <stop offset="1" stop-color="#000000" stop-opacity="0" />
        </linearGradient>
        <linearGradient id="sombraInferior" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#000000" stop-opacity="0" />
            <stop offset="1" stop-color="#000000" stop-opacity="0.8" />
        </linearGradient>
        <linearGradient id="franja" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#ffc928" />
            <stop offset="1" stop-color="#f5a40b" />
        </linearGradient>
        <filter id="sombraTexto" x="-10%" y="-30%" width="120%" height="160%">
            <feDropShadow dx="0" dy="4" stdDeviation="5" flood-color="#000000" flood-opacity="0.65" />
        </filter>
        <clipPath id="recorteFoto"><rect width="1400" height="840" /></clipPath>
        <clipPath id="recorteInsignia"><path d="M0 0H150V150L75 182 0 150Z" /></clipPath>
        <linearGradient id="desvanecerFoto" x1="1" y1="0" x2="0.3" y2="0">
            <stop offset="0" stop-color="#ffffff" />
            <stop offset="0.4" stop-color="#ffffff" stop-opacity="0.85" />
            <stop offset="1" stop-color="#ffffff" stop-opacity="0" />
        </linearGradient>
        <mask id="mascaraFoto"><rect width="1400" height="840" fill="url(#desvanecerFoto)" /></mask>
    </defs>

    {{-- Fondo por defecto (queda debajo de la foto cuando hay una) --}}
    <rect width="1400" height="900" fill="url(#cielo)" />
    <rect width="1400" height="840" fill="url(#sol)" />
    <path d="M0 640 120 590 230 615 360 548 470 600 590 560 700 610 820 540 960 595 1080 552 1200 600 1310 566 1400 590V840H0Z" fill="#3a2a3f" fill-opacity="0.85" />
    <path d="M0 700 150 650 300 690 420 640 560 690 700 655 860 700 1000 650 1150 695 1290 660 1400 690V840H0Z" fill="#1d1626" />
    <path d="M0 760 200 725 380 755 560 720 760 760 960 728 1180 765 1400 735V840H0Z" fill="#0c0a12" />

    {{-- Foto del operador --}}
    @if ($editable)
        {{-- Siempre presente para la vista previa en vivo: la SPA cambia la foto, el estilo y la opacidad por id. --}}
        <g id="qsl-foto" clip-path="url(#recorteFoto)" @unless ($fondo) style="display: none" @endunless>
            <rect id="qsl-foto-fondo" width="1400" height="840" fill="#05070d" @if (($fondo['estilo'] ?? null) !== 'completa') style="display: none" @endif />
            <image id="qsl-foto-imagen" x="0" y="0" width="1400" height="840" preserveAspectRatio="xMidYMid slice"
                   @if ($fondo) href="{{ $fondo['data'] }}" @if ($fondo['estilo'] === 'degradado') mask="url(#mascaraFoto)" @else opacity="{{ $fondo['opacidad'] / 100 }}" @endif @endif />
        </g>
    @elseif ($fondo)
        <g clip-path="url(#recorteFoto)">
            @if ($fondo['estilo'] === 'completa')
                <rect width="1400" height="840" fill="#05070d" />
            @endif
            <image href="{{ $fondo['data'] }}" x="0" y="0" width="1400" height="840" preserveAspectRatio="xMidYMid slice"
                   @if ($fondo['estilo'] === 'degradado') mask="url(#mascaraFoto)" @else opacity="{{ $fondo['opacidad'] / 100 }}" @endif />
        </g>
    @endif

    {{-- Sombras suaves para que el texto se lea sobre cualquier foto --}}
    @if ($d['sombras'][0][1] > 0)
        <rect y="{{ $d['sombras'][0][0] }}" width="1400" height="{{ $d['sombras'][0][1] }}" fill="url(#sombraSuperior)" />
    @endif
    <rect y="{{ $d['sombras'][1][0] }}" width="1400" height="{{ $d['sombras'][1][1] }}" fill="url(#sombraInferior)" />

    {{-- Mi indicativo, mi nombre y mi estación --}}
    <text x="{{ $ind['x'] }}" y="{{ $ind['y'] }}" text-anchor="{{ $ind['anclaje'] }}" font-family="{{ $fuenteTitulo }}" font-size="{{ $tamanoIndicativo }}" font-weight="900"
          fill="#ffffff" letter-spacing="10" filter="url(#sombraTexto)">{{ $miIndicativo }}</text>
    <text x="{{ $ind['x'] + $ind['sangria'] }}" y="{{ $ind['y'] + $separacion[0] }}" text-anchor="{{ $ind['anclaje'] }}" font-family="{{ $fuenteTexto }}" font-size="30" font-weight="600" fill="#ffffff" fill-opacity="0.92" filter="url(#sombraTexto)">{{ $operador->nombre_completo }}</text>
    @if ($estacion)
        <text x="{{ $ind['x'] + $ind['sangria'] }}" y="{{ $ind['y'] + $separacion[1] }}" text-anchor="{{ $ind['anclaje'] }}" font-family="{{ $fuenteMono }}" font-size="20" fill="#ffffff" fill-opacity="0.75" filter="url(#sombraTexto)">{{ $estacion }}</text>
    @endif

    {{-- Recuadro del QSO --}}
    <g transform="translate({{ $d['recuadro'][0] }} {{ $d['recuadro'][1] }})">
        <rect width="500" height="270" rx="20" fill="#000000" fill-opacity="0.5" stroke="#ffffff" stroke-opacity="0.2" stroke-width="2" />
        <text x="30" y="44" font-family="{{ $fuenteTexto }}" font-size="16" font-weight="600" fill="#cbd5e1" letter-spacing="4">CONFIRMANDO QSO CON</text>
        <text x="30" y="102" font-family="{{ $fuenteMono }}" font-size="{{ mb_strlen($contacto->estacion_llamada) > 10 ? 40 : 52 }}" font-weight="700" fill="#ffc928">{{ $contacto->estacion_llamada }}</text>
        <line x1="30" y1="126" x2="470" y2="126" stroke="#ffffff" stroke-opacity="0.15" stroke-width="2" />
        @foreach ($datosQso as $indice => [$titulo, $valor])
            @php($centro = 500 / 6 * (2 * ($indice % 3) + 1))
            @php($fila = intdiv($indice, 3))
            <text x="{{ $centro }}" y="{{ 160 + $fila * 66 }}" text-anchor="middle" font-family="{{ $fuenteTexto }}" font-size="13" font-weight="600" fill="#94a3b8" letter-spacing="3">{{ $titulo }}</text>
            <text x="{{ $centro }}" y="{{ 192 + $fila * 66 }}" text-anchor="middle" font-family="{{ $fuenteMono }}" font-size="{{ mb_strlen($valor) > 9 ? 20 : 26 }}" font-weight="600" fill="#ffffff">{{ $valor }}</text>
        @endforeach
    </g>
    @if ($verificado)
        <g transform="translate({{ $d['verificado'][0] }} {{ $d['verificado'][1] }})">
            <rect y="-28" width="340" height="44" rx="22" fill="#052e1f" fill-opacity="0.75" stroke="#34d399" stroke-opacity="0.6" stroke-width="2" />
            <circle cx="24" cy="-6" r="6" fill="#34d399" />
            <text x="320" y="0" text-anchor="end" font-family="{{ $fuenteTexto }}" font-size="18" font-weight="600" fill="#a7f3d0">Verificado en Libro de Guardia</text>
        </g>
    @endif

    {{-- Mensaje (hasta 3 líneas) y pie --}}
    @foreach ([0, 1, 2] as $i)
        <text x="{{ $d['mensaje']['x'] }}" y="{{ 788 - (count($lineas) - 1 - $i) * 34 }}" text-anchor="{{ $d['mensaje']['anclaje'] }}" font-family="{{ $fuenteTexto }}" font-size="24" fill="#ffffff" filter="url(#sombraTexto)"
              @if ($editable) data-linea="{{ $i }}" @endif>{{ $lineas[$i] ?? '' }}</text>
    @endforeach
    <text x="{{ $d['mensaje']['x'] }}" y="822" text-anchor="{{ $d['mensaje']['anclaje'] }}" font-family="{{ $fuenteMono }}" font-size="17" fill="#ffffff" fill-opacity="0.7">{{ $recibida ? 'TNX QSL' : 'PSE QSL TNX' }}  ·  QSO #{{ $contacto->id }}</text>

    {{-- Emblema y escudo con la bandera --}}
    <image href="{{ $remitente['emblema'] }}" x="{{ $xEmblema }}" y="640" width="172" height="172" />
    @if ($remitente['bandera'])
        <g transform="translate({{ $xBandera }} 638)">
            <g clip-path="url(#recorteInsignia)">
                <rect width="150" height="182" fill="#ffffff" />
                <image href="{{ $remitente['bandera'] }}" y="46" width="150" height="136" preserveAspectRatio="xMidYMid slice" />
            </g>
            <text x="75" y="33" text-anchor="middle" font-family="{{ $fuenteTitulo }}" font-size="{{ $tamanoInsignia }}" font-weight="900" fill="#c8102e">{{ $miIndicativo }}</text>
            <path d="M0 0H150V150L75 182 0 150Z" fill="none" stroke="#ffffff" stroke-width="3" />
        </g>
    @endif

    {{-- Franja inferior --}}
    <rect y="840" width="1400" height="60" fill="url(#franja)" />
    <text x="30" y="878" font-family="{{ $fuenteTexto }}" font-size="{{ $tamanoFranja }}" font-weight="500" fill="#2a1a00">{{ $franja }}</text>
    <text x="1370" y="877" text-anchor="end" font-family="{{ $fuenteTexto }}" font-size="14" font-weight="700" fill="#2a1a00" fill-opacity="0.7" letter-spacing="3">LIBRO DE GUARDIA</text>
</svg>
