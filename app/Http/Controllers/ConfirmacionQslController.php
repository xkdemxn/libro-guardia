<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmacionQslRequest;
use App\Models\Contacto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Marca el envío y la recepción de QSL de un contacto (US-10).
 */
class ConfirmacionQslController extends Controller
{
    /**
     * Guarda el estado de QSL por medio. Un medio sin nada marcado se elimina.
     *
     * @throws ValidationException
     */
    public function update(ConfirmacionQslRequest $request, string $contacto): RedirectResponse
    {
        $contactoEncontrado = ctype_digit($contacto) ? $request->user()->contactos()->find((int) $contacto) : null;

        if ($contactoEncontrado === null) {
            return redirect()
                ->route('contactos.index')
                ->with('error', 'El contacto no existe o no pertenece a tu libro de guardia.');
        }

        $this->validarFechasPosterioresAlContacto($request, $contactoEncontrado);

        foreach (ConfirmacionQslRequest::tiposManuales() as $tipo) {
            $estado = $request->estadoPara($tipo);

            if (! $estado['enviada'] && ! $estado['recibida']) {
                $contactoEncontrado->confirmacionesQsl()->where('tipo', $tipo)->delete();

                continue;
            }

            $contactoEncontrado->confirmacionesQsl()->updateOrCreate(['tipo' => $tipo], $estado);
        }

        return redirect()
            ->route('contactos.show', $contactoEncontrado)
            ->with('status', 'Estado de QSL actualizado.');
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
