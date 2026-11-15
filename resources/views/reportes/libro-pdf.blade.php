{{--
    Reporte del libro de guardia en PDF (dompdf: solo tablas y CSS básico, sin flex ni grid).
    Variables: $operador, $contactos, $generado, $filtrosAplicados, $periodo, $totales, $porRango, $porBanda, $porModo.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Libro de guardia de {{ $operador->indicativo }}</title>
    <style>
        @page { margin: 26mm 14mm 18mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; color: #1f2937; }

        /* Encabezado y pie fijos en todas las páginas */
        .encabezado { position: fixed; top: -19mm; left: 0; right: 0; height: 14mm; border-bottom: 2px solid #1673f0; }
        .encabezado td { vertical-align: bottom; padding-bottom: 2mm; }
        .marca { font-size: 7.5pt; letter-spacing: 2.5px; color: #1673f0; font-weight: bold; }
        .pie { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 7pt; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 2mm; }
        .numero-pagina:after { content: "Página " counter(page); }

        h1 { font-size: 26pt; margin: 0; letter-spacing: 1px; color: #0c172a; font-family: "DejaVu Sans Mono", monospace; }
        h2 { font-size: 8pt; letter-spacing: 2.5px; color: #6b7280; text-transform: uppercase; margin: 0 0 2.5mm 0; font-weight: bold; }
        .tenue { color: #6b7280; }
        .mono { font-family: "DejaVu Sans Mono", monospace; }

        table { width: 100%; border-collapse: collapse; }
        .bloque { margin-bottom: 6mm; }

        .tarjetas td { width: 25%; padding: 0 2mm 0 0; }
        .tarjeta { border: 1px solid #dbe4f0; border-radius: 3mm; padding: 3mm 4mm; background: #f5f8fc; }
        .tarjeta .valor { font-size: 18pt; font-weight: bold; color: #0c172a; }
        .tarjeta .etiqueta { font-size: 7pt; letter-spacing: 1.5px; text-transform: uppercase; color: #6b7280; }

        .resumen > tbody > tr > td { width: 33.3%; vertical-align: top; padding-right: 4mm; }
        .mini td { padding: 1.2mm 0; border-bottom: 1px solid #eef2f7; }
        .mini .barra { height: 2.2mm; background: #52adff; border-radius: 1mm; }
        .mini .cantidad { text-align: right; width: 12mm; font-family: "DejaVu Sans Mono", monospace; }

        .contactos th { background: #0c172a; color: #ffffff; font-size: 7pt; letter-spacing: 1px; text-transform: uppercase; text-align: left; padding: 2mm 2mm; }
        .contactos td { padding: 1.6mm 2mm; border-bottom: 1px solid #eef2f7; }
        .contactos tr:nth-child(even) td { background: #f8fafc; }
        .contactos .derecha { text-align: right; }
        .estado { font-size: 7pt; font-weight: bold; padding: 0.6mm 2mm; border-radius: 2mm; }
        .confirmado { background: #d1fae5; color: #065f46; }
        .enviada { background: #fef3c7; color: #92400e; }
        .pendiente { background: #f1f5f9; color: #475569; }
    </style>
</head>
<body>
    <div class="encabezado">
        <table>
            <tr>
                <td><span class="marca">LIBRO DE GUARDIA · REPORTE DE CONTACTOS</span></td>
                <td style="text-align: right;" class="tenue">Generado el {{ $generado->format('Y-m-d H:i') }} UTC</td>
            </tr>
        </table>
    </div>

    <div class="pie">
        <table>
            <tr>
                <td>{{ $operador->indicativo }} · {{ $operador->nombre_completo }} · Todas las horas en UTC</td>
                <td style="text-align: right;"><span class="numero-pagina"></span></td>
            </tr>
        </table>
    </div>

    {{-- Operador y periodo --}}
    <table class="bloque">
        <tr>
            <td style="vertical-align: top;">
                <h1>{{ $operador->indicativo }}</h1>
                <div style="font-size: 11pt; margin-top: 1mm;">{{ $operador->nombre_completo }}</div>
                <div class="tenue">
                    {{ collect([$operador->ciudad, $operador->pais])->filter()->join(', ') }}
                    @if ($operador->grid_locator) · Grid <span class="mono">{{ $operador->grid_locator }}</span> @endif
                    @if ($operador->equipo_radio) · {{ $operador->equipo_radio }} @endif
                </div>
            </td>
            <td style="vertical-align: top; text-align: right; width: 45%;">
                <h2>Periodo</h2>
                <div class="mono" style="font-size: 10pt;">{{ $periodo['desde']->format('Y-m-d') }} → {{ $periodo['hasta']->format('Y-m-d') }}</div>
                <div class="tenue" style="margin-top: 1.5mm;">
                    {{ $filtrosAplicados === [] ? 'Libro completo, sin filtros.' : implode(' · ', $filtrosAplicados) }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Totales --}}
    <table class="tarjetas bloque">
        <tr>
            @foreach ($totales as $etiqueta => $valor)
                <td>
                    <div class="tarjeta">
                        <div class="etiqueta">{{ $etiqueta }}</div>
                        <div class="valor">{{ number_format($valor) }}</div>
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    {{-- Distribución --}}
    <table class="resumen bloque">
        <tr>
            @foreach (['Por rango' => $porRango, 'Por banda' => $porBanda, 'Por modo' => $porModo] as $titulo => $datos)
                @php($maximo = max(1, $datos->max() ?? 1))
                <td>
                    <h2>{{ $titulo }}</h2>
                    <table class="mini">
                        @foreach ($datos as $categoria => $cantidad)
                            <tr>
                                <td style="width: 22mm;" class="mono">{{ $categoria }}</td>
                                <td><div class="barra" style="width: {{ max(2, round($cantidad / $maximo * 100)) }}%;"></div></td>
                                <td class="cantidad">{{ $cantidad }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            @endforeach
        </tr>
    </table>

    {{-- Contactos --}}
    <h2>Contactos ({{ $contactos->count() }})</h2>
    <table class="contactos">
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Hora UTC</th>
                <th>Estación</th>
                <th>País</th>
                <th class="derecha">MHz</th>
                <th>Banda</th>
                <th>Modo</th>
                <th>Env / Rec</th>
                <th>QSL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($contactos as $contacto)
                @php($estado = $contacto->estadoQsl())
                <tr>
                    <td class="tenue">{{ $loop->iteration }}</td>
                    <td class="mono">{{ $contacto->inicio_utc->format('Y-m-d') }}</td>
                    <td class="mono">{{ $contacto->inicio_utc->format('H:i') }}{{ $contacto->fin_utc ? '–'.$contacto->fin_utc->format('H:i') : '' }}</td>
                    <td class="mono" style="font-weight: bold;">{{ $contacto->estacion_llamada }}</td>
                    <td>{{ $contacto->prefijoPais?->pais ?? '—' }}</td>
                    <td class="mono derecha">{{ number_format($contacto->frecuencia_mhz, 3) }}</td>
                    <td class="mono">{{ $contacto->banda->nombre }} <span class="tenue">{{ $contacto->banda->rango === 'MF' ? 'HF' : $contacto->banda->rango }}</span></td>
                    <td class="mono">{{ $contacto->modo->etiqueta }}</td>
                    <td class="mono">{{ $contacto->reporte_enviado }} / {{ $contacto->reporte_recibido }}</td>
                    <td><span class="estado {{ $estado }}">{{ ucfirst($estado) }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
