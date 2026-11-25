<?php

namespace App\Services;

use App\Models\Banda;
use App\Models\Contacto;
use App\Models\Modo;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

/**
 * Reporte del libro de guardia en PDF: datos del operador, periodo y filtros, resumen
 * (totales, por rango HF/VHF/UHF, por banda y por modo) y la tabla de contactos.
 */
class ReportePdf
{
    /**
     * @param  Collection<int, Contacto>  $contactos  con banda, modo, prefijoPais y confirmacionesQsl cargados
     * @param  array<string, mixed>  $filtros  los mismos del listado (desde, hasta, banda_id, modo_id, qsl, indicativo)
     */
    public function generar(User $operador, Collection $contactos, array $filtros): string
    {
        $confirmados = $contactos->filter(fn (Contacto $contacto): bool => $contacto->estadoQsl() === 'confirmado');

        return Pdf::loadView('reportes.libro-pdf', [
            'operador' => $operador,
            'contactos' => $contactos,
            'generado' => now('UTC'),
            'filtrosAplicados' => $this->describirFiltros($filtros),
            'periodo' => [
                'desde' => $contactos->min('inicio_utc'),
                'hasta' => $contactos->max('inicio_utc'),
            ],
            'totales' => [
                'Contactos' => $contactos->count(),
                'Países' => $contactos->pluck('prefijoPais.pais')->filter()->unique()->count(),
                'Confirmados' => $confirmados->count(),
                'Bandas' => $contactos->pluck('banda_id')->unique()->count(),
            ],
            'porRango' => $contactos->countBy(fn (Contacto $c): string => $c->banda->rango === 'MF' ? 'HF' : $c->banda->rango)->sortKeys(),
            'porBanda' => $contactos->countBy(fn (Contacto $c): string => $c->banda->nombre)->sortDesc(),
            'porModo' => $contactos->countBy(fn (Contacto $c): string => $c->modo->nombre)->sortDesc(),
        ])
            ->setPaper('a4', 'landscape')
            // Incrusta solo los caracteres usados de la fuente: el archivo pesa mucho menos.
            ->setOption('isFontSubsettingEnabled', true)
            ->output();
    }

    /**
     * Texto legible de los filtros usados, para el encabezado del reporte.
     *
     * @param  array<string, mixed>  $filtros
     * @return list<string>
     */
    private function describirFiltros(array $filtros): array
    {
        $descripcion = [];

        if (! empty($filtros['desde']) || ! empty($filtros['hasta'])) {
            $descripcion[] = 'Fechas: '.($filtros['desde'] ?? 'inicio').' a '.($filtros['hasta'] ?? 'hoy');
        }
        if (! empty($filtros['banda_id'])) {
            $descripcion[] = 'Banda: '.Banda::find($filtros['banda_id'])?->nombre;
        }
        if (! empty($filtros['modo_id'])) {
            $descripcion[] = 'Modo: '.Modo::find($filtros['modo_id'])?->nombre;
        }
        if (! empty($filtros['qsl'])) {
            $descripcion[] = 'QSL: '.$filtros['qsl'];
        }
        if (! empty($filtros['indicativo'])) {
            $descripcion[] = 'Indicativo: '.mb_strtoupper($filtros['indicativo']);
        }

        return $descripcion;
    }
}
