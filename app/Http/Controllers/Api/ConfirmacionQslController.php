<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmacionQslRequest;
use App\Http\Resources\ContactoResource;
use App\Models\Contacto;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ConfirmacionQslController extends Controller
{
    /**
     * Guarda el estado de QSL por medio (US-10). Un medio sin nada marcado se elimina.
     *
     * @throws ValidationException
     */
    public function update(ConfirmacionQslRequest $request, string $contacto): JsonResponse
    {
        $contactoEncontrado = ctype_digit($contacto) ? $request->user()->contactos()->find((int) $contacto) : null;

        abort_if($contactoEncontrado === null, 404, 'El contacto no existe o no pertenece a tu libro de guardia.');

        $this->validarFechasPosterioresAlContacto($request, $contactoEncontrado);

        foreach (ConfirmacionQslRequest::tiposManuales() as $tipo) {
            $estado = $request->estadoPara($tipo);

            if (! $estado['enviada'] && ! $estado['recibida']) {
                $contactoEncontrado->confirmacionesQsl()->where('tipo', $tipo)->delete();

                continue;
            }

            $contactoEncontrado->confirmacionesQsl()->updateOrCreate(['tipo' => $tipo], $estado);
        }

        $contactoEncontrado->load(['user', 'banda', 'modo', 'prefijoPais', 'confirmacionesQsl']);

        return (new ContactoResource($contactoEncontrado))->detalle()
            ->additional(['mensaje' => 'Estado de QSL actualizado.'])
            ->response();
    }

    /**
     * La fecha de una QSL no puede ser anterior a la fecha del contacto.
     *
     * @throws ValidationException
     */
    private function validarFechasPosterioresAlContacto(ConfirmacionQslRequest $request, Contacto $contacto): void
    {
        $fechaContacto = $contacto->inicio_utc->toDateString();
        $errores = [];

        foreach (ConfirmacionQslRequest::tiposManuales() as $tipo) {
            foreach (['fecha_envio', 'fecha_recepcion'] as $campo) {
                $fecha = $request->estadoPara($tipo)[$campo];

                if ($fecha !== null && $fecha < $fechaContacto) {
                    $errores["qsl.{$tipo->value}.{$campo}"] = 'La fecha de QSL no puede ser anterior a la del contacto ('.$fechaContacto.').';
                }
            }
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }
}
