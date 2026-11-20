<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipoQsl;
use App\Http\Controllers\Controller;
use App\Models\Contacto;
use App\Rules\IndicativoValido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * «Consulta una QSL»: quien recibió una tarjeta comprueba, sin cuenta, que el contacto está registrado.
 *
 * Hacen falta el número de QSO impreso en la tarjeta y el indicativo de la estación contactada. Los dos
 * juntos: solo con el número se podrían recorrer los libros ajenos. Si no coinciden, la respuesta es
 * la misma que si el QSO no existiera.
 */
class VerificacionQslController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'qso' => ['required', 'integer', 'min:1'],
            'indicativo' => ['required', 'string', 'max:20'],
        ], [
            'qso.required' => 'Escribe el número de QSO que aparece al pie de la tarjeta.',
            'qso.integer' => 'El número de QSO son solo dígitos (el que va después de «QSO #»).',
            'indicativo.required' => 'Escribe tu indicativo.',
        ]);

        $contacto = Contacto::query()
            ->with(['user', 'banda', 'modo', 'confirmacionesQsl'])
            ->whereKey($datos['qso'])
            ->where('estacion_llamada', IndicativoValido::normalizar($datos['indicativo']))
            ->first();

        abort_if($contacto === null, 404, 'No encontramos un QSO con ese número para ese indicativo. Revisa los dos datos en la tarjeta.');

        return response()->json([
            'data' => [
                'qso' => $contacto->id,
                'de' => $contacto->user->indicativo,
                'para' => $contacto->estacion_llamada,
                'fecha' => $contacto->inicio_utc->format('Y-m-d'),
                'hora_utc' => $contacto->inicio_utc->format('H:i'),
                'frecuencia_mhz' => $contacto->frecuencia_mhz,
                'banda' => $contacto->banda->nombre,
                'modo' => $contacto->modo->etiqueta,
                'reporte' => $contacto->reporte_enviado,
                'estado_qsl' => $contacto->estadoQsl(),
                'verificado' => $contacto->confirmacionesQsl->contains('tipo', TipoQsl::Interna),
                'svg' => app(TarjetaQslController::class)->svgPublico($contacto),
            ],
        ]);
    }
}
