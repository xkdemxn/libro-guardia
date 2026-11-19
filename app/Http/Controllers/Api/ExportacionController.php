<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FiltroContactosRequest;
use App\Services\ExportadorAdif;
use App\Services\ExportadorCsv;
use App\Services\ReportePdf;
use Illuminate\Http\Response;
use Throwable;

/**
 * Exporta el libro de guardia en CSV (US-11), ADIF (US-12) o como reporte PDF, con filtros opcionales.
 */
class ExportacionController extends Controller
{
    public function store(FiltroContactosRequest $request, ExportadorCsv $csv, ExportadorAdif $adif, ReportePdf $pdf): Response
    {
        $operador = $request->user();

        $contactos = $operador->contactos()
            ->with(['banda', 'modo', 'prefijoPais', 'confirmacionesQsl'])
            ->filtrar($request->filtros())
            ->orderBy('inicio_utc')
            ->get();

        abort_if($contactos->isEmpty(), 422, 'No hay contactos para exportar con esos filtros.');

        $formato = $request->validated('formato');

        try {
            $contenido = match ($formato) {
                'adif' => $adif->generar($operador, $contactos),
                'pdf' => $pdf->generar($operador, $contactos, $request->filtros()),
                default => $csv->generar($contactos),
            };
        } catch (Throwable $error) {
            report($error);

            abort(500, 'No se pudo generar el archivo. Inténtalo de nuevo.');
        }

        $nombreArchivo = sprintf(
            'libro-%s-%s.%s',
            str_replace('/', '-', $operador->indicativo),
            now('UTC')->format('Ymd-Hi'),
            ['adif' => 'adi', 'pdf' => 'pdf'][$formato] ?? 'csv',
        );

        return response($contenido, 200, [
            'Content-Type' => match ($formato) {
                'adif' => 'text/plain; charset=US-ASCII',
                'pdf' => 'application/pdf',
                default => 'text/csv; charset=UTF-8',
            },
            'Content-Disposition' => 'attachment; filename="'.$nombreArchivo.'"',
        ]);
    }
}
