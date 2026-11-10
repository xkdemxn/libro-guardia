<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactoRequest;
use App\Models\Banda;
use App\Models\Contacto;
use App\Models\Modo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Libro de guardia: listado, registro, modificación y eliminación de contactos del operador.
 */
class ContactoController extends Controller
{
    private const CONTACTOS_POR_PAGINA = 25;

    /**
     * Listado paginado con búsqueda y filtros (US-06, US-09).
     */
    public function index(Request $request): View
    {
        $validator = Validator::make($request->query(), [
            'indicativo' => ['nullable', 'string', 'max:20'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'banda_id' => ['nullable', 'integer', 'exists:bandas,id'],
            'modo_id' => ['nullable', 'integer', 'exists:modos,id'],
            'qsl' => ['nullable', 'in:confirmado,enviada,pendiente'],
        ], [
            'hasta.after_or_equal' => 'La fecha de inicio no puede ser mayor que la fecha de fin.',
        ]);

        $filtros = $validator->fails() ? [] : $validator->validated();

        $contactos = $request->user()->contactos()
            ->with(['banda', 'modo', 'prefijoPais', 'confirmacionesQsl'])
            ->filtrar($filtros)
            ->orderByDesc('inicio_utc')
            ->orderByDesc('id')
            ->paginate(self::CONTACTOS_POR_PAGINA)
            ->withQueryString();

        $resumen = $this->resumen($request);

        return view('contactos.index', [
            'contactos' => $contactos,
            'filtros' => $filtros,
            'hayFiltros' => array_filter($filtros) !== [],
            'resumen' => $resumen,
            'tieneContactos' => $resumen['contactos'] > 0,
            'bandas' => Banda::query()->orderBy('frecuencia_min_mhz')->get(),
            'modos' => Modo::query()->orderBy('nombre')->get(),
        ])->withErrors($validator);
    }

    /**
     * Formulario para registrar un contacto (US-03, US-04).
     */
    public function create(): View
    {
        return view('contactos.create', [
            'contacto' => new Contacto,
            ...$this->catalogosFormulario(),
        ]);
    }

    /**
     * Guarda un contacto nuevo ya validado (US-03, US-05).
     */
    public function store(ContactoRequest $request): RedirectResponse
    {
        $contacto = $request->user()->contactos()->create($request->datosContacto());

        return redirect()
            ->route('contactos.index')
            ->with('status', "Contacto con {$contacto->estacion_llamada} guardado.");
    }

    /**
     * Formulario para corregir un contacto (US-07).
     */
    public function edit(Request $request, string $contacto): View|RedirectResponse
    {
        $contactoEncontrado = $this->buscarContactoPropio($request, $contacto);

        if ($contactoEncontrado === null) {
            return $this->contactoInexistente();
        }

        return view('contactos.edit', [
            'contacto' => $contactoEncontrado,
            ...$this->catalogosFormulario(),
        ]);
    }

    /**
     * Guarda las correcciones de un contacto con las mismas validaciones del registro (US-07).
     */
    public function update(ContactoRequest $request, string $contacto): RedirectResponse
    {
        $contactoEncontrado = $this->buscarContactoPropio($request, $contacto);

        if ($contactoEncontrado === null) {
            return $this->contactoInexistente();
        }

        $contactoEncontrado->update($request->datosContacto());

        return redirect()
            ->route('contactos.index')
            ->with('status', "Contacto con {$contactoEncontrado->estacion_llamada} actualizado.");
    }

    /**
     * Elimina un contacto; la confirmación se pide en la interfaz antes de enviar (US-08).
     */
    public function destroy(Request $request, string $contacto): RedirectResponse
    {
        $contactoEncontrado = $this->buscarContactoPropio($request, $contacto);

        if ($contactoEncontrado === null) {
            return $this->contactoInexistente();
        }

        $contactoEncontrado->delete();

        return redirect()
            ->route('contactos.index')
            ->with('status', "Contacto con {$contactoEncontrado->estacion_llamada} eliminado.");
    }

    /**
     * Modos y bandas que necesita el formulario de contacto.
     *
     * @return array{modos: Collection<int, Modo>, bandas: Collection<int, Banda>}
     */
    private function catalogosFormulario(): array
    {
        return [
            'modos' => Modo::query()->orderBy('nombre')->get(),
            'bandas' => Banda::query()->orderBy('frecuencia_min_mhz')->get(),
        ];
    }

    /**
     * Cifras del libro completo (sin filtros) para las tarjetas de resumen.
     *
     * @return array{contactos: int, paises: int, confirmados: int, bandas: int}
     */
    private function resumen(Request $request): array
    {
        $contactos = $request->user()->contactos();

        return [
            'contactos' => (clone $contactos)->count(),
            'paises' => (clone $contactos)->whereNotNull('prefijo_pais_id')
                ->join('prefijos_pais', 'prefijos_pais.id', '=', 'contactos.prefijo_pais_id')
                ->distinct()
                ->count('prefijos_pais.pais'),
            'confirmados' => (clone $contactos)->filtrar(['qsl' => 'confirmado'])->count(),
            'bandas' => (clone $contactos)->distinct()->count('banda_id'),
        ];
    }

    /**
     * Solo se buscan contactos del operador con sesión: los de otros operadores se tratan como inexistentes.
     */
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
