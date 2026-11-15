<?php

namespace App\Http\Controllers;

use App\Enums\TipoQsl;
use App\Http\Requests\PersonalizarTarjetaQslRequest;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Tarjeta QSL digital: la versión moderna de la tarjeta postal con la que los radioaficionados
 * se confirman un contacto. Se genera como SVG y se puede descargar en PNG o SVG.
 */
class TarjetaQslController extends Controller
{
    /**
     * Página con la vista previa de la tarjeta y los botones de descarga.
     */
    public function show(Request $request, string $contacto): View|RedirectResponse
    {
        $contactoEncontrado = $this->buscarContactoPropio($request, $contacto);

        if ($contactoEncontrado === null) {
            return $this->contactoInexistente();
        }

        return view('tarjeta-qsl.show', [
            ...$this->datosTarjeta($contactoEncontrado),
            'nombreArchivo' => $this->nombreArchivo($contactoEncontrado),
        ]);
    }

    /**
     * Personaliza todas las tarjetas del operador: foto (estilo y opacidad) y mensaje del pie.
     */
    public function personalizar(PersonalizarTarjetaQslRequest $request): RedirectResponse
    {
        $operador = $request->user();
        $operador->cambiarImagen('qsl_foto_path', 'qsl', $request->file('qsl_foto'), $request->boolean('quitar_qsl_foto'));
        $operador->fill($request->safe()->only(['qsl_foto_estilo', 'qsl_foto_opacidad', 'qsl_mensaje']));
        $operador->save();

        return back()->with('status', 'Tarjeta actualizada. Se aplica a todas tus tarjetas QSL.');
    }

    /**
     * La tarjeta como archivo SVG descargable.
     */
    public function svg(Request $request, string $contacto): Response|RedirectResponse
    {
        $contactoEncontrado = $this->buscarContactoPropio($request, $contacto);

        if ($contactoEncontrado === null) {
            return $this->contactoInexistente();
        }

        $svg = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .view('tarjeta-qsl.svg', $this->datosTarjeta($contactoEncontrado))->render();

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->nombreArchivo($contactoEncontrado).'.svg"',
        ]);
    }

    /**
     * @return array{contacto: Contacto, operador: User, verificado: bool, fondo: array{data: string, estilo: string, opacidad: int}|null}
     */
    private function datosTarjeta(Contacto $contacto): array
    {
        $contacto->load(['user', 'banda', 'modo', 'confirmacionesQsl']);

        return [
            'contacto' => $contacto,
            'operador' => $contacto->user,
            'fondo' => $contacto->user->fondoTarjetaQsl(),
            'verificado' => $contacto->confirmacionesQsl->contains(
                fn ($confirmacion): bool => $confirmacion->tipo === TipoQsl::Interna,
            ),
        ];
    }

    private function nombreArchivo(Contacto $contacto): string
    {
        return sprintf(
            'qsl-%s-%s-%s',
            str_replace('/', '-', $contacto->user->indicativo),
            str_replace('/', '-', $contacto->estacion_llamada),
            $contacto->inicio_utc->format('Ymd-Hi'),
        );
    }

    private function buscarContactoPropio(Request $request, string $id): ?Contacto
    {
        return ctype_digit($id) ? $request->user()->contactos()->find((int) $id) : null;
    }

    private function contactoInexistente(): RedirectResponse
    {
        return redirect()
            ->route('contactos.index')
            ->with('error', 'El contacto no existe o no pertenece a tu libro de guardia.');
    }
}
