<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltroContactosRequest;
use App\Models\Banda;
use App\Models\Modo;
use App\Services\ExportadorAdif;
use App\Services\ExportadorCsv;
use App\Services\ReportePdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Throwable;

/**
 * Exporta el libro de guardia en CSV (US-11), ADIF (US-12) o como reporte PDF, con filtros opcionales.
 */
class ExportacionController extends Controller
{
    public function create(): View
    {
        return view('contactos.exportar', [
            'bandas' => Banda::query()->orderBy('frecuencia_min_mhz')->get(),
            'modos' => Modo::query()->orderBy('nombre')->get(),
        ]);
    }

    public function store(FiltroContactosRequest $request, ExportadorCsv $csv, ExportadorAdif $adif, ReportePdf $pdf): Response|RedirectResponse
    {
        $operador = $request->user();

        $contactos = $operador->contactos()
            ->with(['banda', 'modo', 'prefijoPais', 'confirmacionesQsl'])
            ->filtrar($request->filtros())
            ->orderBy('inicio_utc')
            ->get();

        if ($contactos->isEmpty()) {
            return back()->withInput()->with('error', 'No hay contactos para exportar con esos filtros.');
        }

        $formato = $request->validated('formato');

        try {
            $contenido = match ($formato) {
                'adif' => $adif->generar($operador, $contactos),
                'pdf' => $pdf->generar($operador, $contactos, $request->filtros()),
                default => $csv->generar($contactos),
            };
        } catch (Throwable $error) {
            report($error);

            return back()->withInput()->with('error', 'No se pudo generar el archivo. Inténtalo de nuevo.');
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
