<?php

namespace App\Http\Controllers;

use App\Models\Contacto;
use App\Models\PrefijoPais;
use App\Services\ConfirmadorQsl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Solicitudes de confirmación (amplía la innovación 1).
 *
 * Si otra estación anotó un contacto contigo y tú no lo tienes en tu libro, te aparece como solicitud:
 * al aceptarla se copia a tu libro y queda confirmado para los dos; al rechazarla, la otra estación lo ve.
 */
class SolicitudConfirmacionController extends Controller
{
    public function __construct(private ConfirmadorQsl $confirmador) {}

    public function index(Request $request): View
    {
        return view('solicitudes.index', [
            'solicitudes' => $request->user()->solicitudesPendientes()
                ->with(['user', 'banda', 'modo'])
                ->orderByDesc('inicio_utc')
                ->get(),
        ]);
    }

    /**
     * Agrega el contacto a mi libro, visto desde mi lado, y lo confirma para ambos.
     */
    public function aceptar(Request $request, string $contacto): RedirectResponse
    {
        $solicitud = $this->buscarSolicitud($request, $contacto);

        if ($solicitud === null) {
            return $this->solicitudInexistente();
        }

        $otraEstacion = $solicitud->user;

        DB::transaction(function () use ($request, $solicitud, $otraEstacion): void {
            $miContacto = $request->user()->contactos()->create([
                'estacion_llamada' => $otraEstacion->indicativo,
                'inicio_utc' => $solicitud->inicio_utc,
                'fin_utc' => $solicitud->fin_utc,
                'frecuencia_mhz' => $solicitud->frecuencia_mhz,
                'banda_id' => $solicitud->banda_id,
                'modo_id' => $solicitud->modo_id,
                'prefijo_pais_id' => PrefijoPais::paraIndicativo($otraEstacion->indicativo)?->id,
                // Lo que la otra estación envió es lo que yo recibí, y viceversa.
                'reporte_enviado' => $solicitud->reporte_recibido,
                'reporte_recibido' => $solicitud->reporte_enviado,
                'grid_locator' => $otraEstacion->grid_locator,
                'nombre_operador' => $otraEstacion->visibilidad->mostrar_nombre ? $otraEstacion->nombre_completo : null,
                'activacion' => null,
                'observaciones' => 'Agregado desde una solicitud de confirmación de '.$otraEstacion->indicativo.'.',
            ]);

            $this->confirmador->sincronizar($miContacto);
        });

        return redirect()
            ->route('solicitudes.index')
            ->with('status', "Contacto con {$otraEstacion->indicativo} agregado a tu libro y confirmado para los dos.");
    }

    /**
     * "No fui yo": la solicitud desaparece y la otra estación lo verá en el detalle de su contacto.
     */
    public function rechazar(Request $request, string $contacto): RedirectResponse
    {
        $solicitud = $this->buscarSolicitud($request, $contacto);

        if ($solicitud === null) {
            return $this->solicitudInexistente();
        }

        $solicitud->rechazado_por_contraparte_at = now();
        $solicitud->save();

        return redirect()
            ->route('solicitudes.index')
            ->with('status', "Solicitud de {$solicitud->user->indicativo} rechazada.");
    }

    /**
     * Solo se puede responder una solicitud que está pendiente para el operador con sesión.
     */
    private function buscarSolicitud(Request $request, string $id): ?Contacto
    {
        return ctype_digit($id)
            ? $request->user()->solicitudesPendientes()->with('user.visibilidad')->find((int) $id)
            : null;
    }

    private function solicitudInexistente(): RedirectResponse
    {
        return redirect()
            ->route('solicitudes.index')
            ->with('error', 'La solicitud no existe o ya fue respondida.');
    }
}
