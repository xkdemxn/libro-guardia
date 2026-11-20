<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contacto;
use App\Models\PrefijoPais;
use App\Services\ConfirmadorQsl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Solicitudes de confirmación (amplía la innovación 1).
 *
 * Si otra estación anotó un contacto contigo y tú no lo tienes en tu libro, te aparece como solicitud:
 * al aceptarla se copia a tu libro y queda confirmado para los dos; al rechazarla, la otra estación lo ve.
 */
class SolicitudConfirmacionController extends Controller
{
    public function __construct(private ConfirmadorQsl $confirmador) {}

    public function index(Request $request): JsonResponse
    {
        $solicitudes = $request->user()->solicitudesPendientes()
            ->with(['user', 'banda', 'modo'])
            ->orderByDesc('inicio_utc')
            ->get();

        return response()->json([
            'data' => $solicitudes->map(fn (Contacto $solicitud): array => [
                'id' => $solicitud->id,
                'indicativo' => $solicitud->user->indicativo,
                'fecha' => $solicitud->inicio_utc->format('Y-m-d'),
                'hora_inicio' => $solicitud->inicio_utc->format('H:i'),
                'hora_fin' => $solicitud->fin_utc?->format('H:i'),
                'frecuencia_mhz' => $solicitud->frecuencia_mhz,
                'banda' => $solicitud->banda->nombre,
                'modo' => $solicitud->modo->etiqueta,
                // Visto desde mi lado: lo que la otra estación envió es lo que yo recibí.
                'reporte_enviado' => $solicitud->reporte_recibido,
                'reporte_recibido' => $solicitud->reporte_enviado,
            ]),
        ]);
    }

    /**
     * Agrega el contacto a mi libro, visto desde mi lado, y lo confirma para ambos.
     */
    public function aceptar(Request $request, string $contacto): JsonResponse
    {
        $solicitud = $this->buscarSolicitud($request, $contacto);
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
                'reporte_enviado' => $solicitud->reporte_recibido,
                'reporte_recibido' => $solicitud->reporte_enviado,
                'grid_locator' => $otraEstacion->grid_locator,
                'nombre_operador' => $otraEstacion->visibilidad->mostrar_nombre ? $otraEstacion->nombre_completo : null,
                'activacion' => null,
                'observaciones' => 'Agregado desde una solicitud de confirmación de '.$otraEstacion->indicativo.'.',
            ]);

            $this->confirmador->sincronizar($miContacto);
        });

        return response()->json(['mensaje' => __('Contacto con :indicativo agregado a tu libro y confirmado para los dos.', ['indicativo' => $otraEstacion->indicativo])]);
    }

    /**
     * "No fui yo": la solicitud desaparece y la otra estación lo verá en el detalle de su contacto.
     */
    public function rechazar(Request $request, string $contacto): JsonResponse
    {
        $solicitud = $this->buscarSolicitud($request, $contacto);

        $solicitud->rechazado_por_contraparte_at = now();
        $solicitud->save();

        return response()->json(['mensaje' => __('Solicitud de :indicativo rechazada.', ['indicativo' => $solicitud->user->indicativo])]);
    }

    /**
     * Solo se puede responder una solicitud que está pendiente para el operador con sesión.
     */
    private function buscarSolicitud(Request $request, string $id): Contacto
    {
        $solicitud = ctype_digit($id)
            ? $request->user()->solicitudesPendientes()->with('user.visibilidad')->find((int) $id)
            : null;

        abort_if($solicitud === null, 404, __('La solicitud no existe o ya fue respondida.'));

        return $solicitud;
    }
}
