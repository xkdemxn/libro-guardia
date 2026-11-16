<?php

namespace App\Http\Controllers;

use App\Enums\TipoQsl;
use App\Http\Requests\PersonalizarTarjetaQslRequest;
use App\Models\Contacto;
use App\Models\PrefijoPais;
use App\Models\User;
use App\Support\Ubicaciones;
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
     * @return array{contacto: Contacto, operador: User, verificado: bool, fondo: array{data: string, estilo: string, opacidad: int}|null, remitente: array{franja: string, bandera: string|null, emblema: string}}
     */
    private function datosTarjeta(Contacto $contacto): array
    {
        $contacto->load(['user', 'banda', 'modo', 'confirmacionesQsl']);

        return [
            'contacto' => $contacto,
            'operador' => $contacto->user,
            'fondo' => $contacto->user->fondoTarjetaQsl(),
            'remitente' => $this->remitente($contacto->user),
            'verificado' => $contacto->confirmacionesQsl->contains(
                fn ($confirmacion): bool => $confirmacion->tipo === TipoQsl::Interna,
            ),
        ];
    }

    /**
     * Datos del operador para la franja inferior («Ecuador, Sudamérica · Guaranda - Bolívar · ITU 12 - CQ 10 ·
     * Grid FI08lj»), su bandera y el emblema, como imágenes incrustadas para que el archivo funcione solo.
     *
     * @return array{franja: string, bandera: string|null, emblema: string}
     */
    private function remitente(User $operador): array
    {
        $prefijo = PrefijoPais::paraIndicativo($operador->indicativo);
        $pais = $operador->pais ?: $prefijo?->pais;
        $continente = Ubicaciones::continente(
            PrefijoPais::query()->where('pais', $pais)->value('continente') ?? $prefijo?->continente,
        );
        $grid = $operador->grid_locator ? substr($operador->grid_locator, 0, 4).strtolower(substr($operador->grid_locator, 4)) : null;
        $zonas = Ubicaciones::esEcuador($pais) ? config('ubicaciones.zonas_ecuador') : null;

        $franja = collect([
            collect([$pais, $continente])->filter()->join(', '),
            collect([$operador->ciudad, $operador->provincia])->filter()->join(' - '),
            $zonas ? "ITU {$zonas['itu']} - CQ {$zonas['cq']}" : null,
            $operador->zonaEcuador()?->nombre(),
            $grid ? "Grid {$grid}" : null,
        ])->filter()->join('   ·   ');

        $codigoBandera = $operador->codigoBandera($prefijo);
        $archivoBandera = $codigoBandera ? public_path("banderas/{$codigoBandera}.svg") : null;

        return [
            'franja' => $franja,
            'bandera' => $archivoBandera && is_file($archivoBandera)
                ? 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($archivoBandera))
                : null,
            'emblema' => 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/emblema-256.png'))),
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
