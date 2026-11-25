<?php

namespace App\Services;

use App\Models\Contacto;
use Illuminate\Support\Collection;

/**
 * Genera un CSV del libro de guardia que abre correctamente en Excel y LibreOffice (US-11).
 */
class ExportadorCsv
{
    /**
     * Marca de orden de bytes: hace que Excel lea bien las tildes (UTF-8).
     */
    private const BOM = "\xEF\xBB\xBF";

    private const COLUMNAS = [
        'Fecha (UTC)', 'Hora inicio (UTC)', 'Hora fin (UTC)', 'Indicativo', 'País', 'Frecuencia (MHz)',
        'Banda', 'Modo', 'Reporte enviado', 'Reporte recibido', 'Potencia (W)', 'Grid locator',
        'Nombre', 'Equipo', 'Activación', 'Estado QSL', 'Observaciones',
    ];

    /**
     * @param  Collection<int, Contacto>  $contactos  con banda, modo, prefijoPais y confirmacionesQsl cargados
     */
    public function generar(Collection $contactos): string
    {
        $archivo = fopen('php://temp', 'r+');
        fwrite($archivo, self::BOM);
        fputcsv($archivo, self::COLUMNAS, escape: '');

        foreach ($contactos as $contacto) {
            fputcsv($archivo, [
                $contacto->inicio_utc->format('Y-m-d'),
                $contacto->inicio_utc->format('H:i'),
                $contacto->fin_utc?->format('H:i'),
                $contacto->estacion_llamada,
                $contacto->prefijoPais?->pais,
                number_format($contacto->frecuencia_mhz, 3, '.', ''),
                $contacto->banda->nombre,
                $contacto->modo->nombre,
                $contacto->reporte_enviado,
                $contacto->reporte_recibido,
                $contacto->potencia_w,
                $contacto->grid_locator,
                $contacto->nombre_operador,
                $contacto->equipo,
                $contacto->activacion,
                ucfirst($contacto->estadoQsl()),
                $contacto->observaciones,
            ], escape: '');
        }

        rewind($archivo);
        $contenido = stream_get_contents($archivo);
        fclose($archivo);

        return $contenido;
    }
}
