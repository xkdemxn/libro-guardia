<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactoRequest;
use App\Http\Requests\FiltroContactosRequest;
use App\Http\Resources\ContactoResource;
use App\Models\Contacto;
use App\Services\ConfirmadorQsl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Libro de guardia: listar, registrar, ver, corregir y eliminar contactos (US-03 a US-09).
 *
 * Un contacto de otro operador se trata igual que uno inexistente (404): así no se revela que existe.
 */
class ContactoController extends Controller
{
    private const CONTACTOS_POR_PAGINA = 25;

    public function __construct(private ConfirmadorQsl $confirmador) {}

    /**
     * Listado paginado con búsqueda y filtros (US-06, US-09). El resumen es del libro completo.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate(FiltroContactosRequest::reglas(), FiltroContactosRequest::mensajes());

        $contactos = $request->user()->contactos()
            ->with(['banda', 'modo', 'prefijoPais', 'confirmacionesQsl'])
            ->filtrar($filtros)
            ->orderByDesc('inicio_utc')
            ->orderByDesc('id')
            ->paginate(self::CONTACTOS_POR_PAGINA)
            ->withQueryString();

        return ContactoResource::collection($contactos)->additional([
            'resumen' => $this->resumen($request),
        ]);
    }

    /**
     * Registra un contacto (US-03, US-05). Si la otra estación ya lo tenía en su libro, queda confirmado.
     */
    public function store(ContactoRequest $request): JsonResponse
    {
        $contacto = $request->user()->contactos()->create($request->datosContacto());
        $confirmado = $this->confirmador->sincronizar($contacto);

        return $this->respuestaGuardado($contacto, 'guardado', $confirmado, 201);
    }

    /**
     * Detalle con el panel de confirmaciones QSL (US-10).
     */
    public function show(Request $request, string $contacto): ContactoResource
    {
        return (new ContactoResource($this->buscarContactoPropio($request, $contacto)))->detalle();
    }

    /**
     * Corrige un contacto con las mismas validaciones del registro (US-07).
     */
    public function update(ContactoRequest $request, string $contacto): JsonResponse
    {
        $contactoEncontrado = $this->buscarContactoPropio($request, $contacto);

        $contraparteAnterior = $this->confirmador->buscarContraparte($contactoEncontrado);
        // Si la otra estación lo había rechazado, al corregirlo vuelve a aparecerle como solicitud.
        $contactoEncontrado->rechazado_por_contraparte_at = null;
        $contactoEncontrado->update($request->datosContacto());
        $confirmado = $this->confirmador->sincronizar($contactoEncontrado, $contraparteAnterior);

        return $this->respuestaGuardado($contactoEncontrado, 'actualizado', $confirmado);
    }

    /**
     * Elimina un contacto; la confirmación se pide en la interfaz antes de enviar (US-08).
     */
    public function destroy(Request $request, string $contacto): JsonResponse
    {
        $contactoEncontrado = $this->buscarContactoPropio($request, $contacto);

        $this->confirmador->desvincular($contactoEncontrado);
        $contactoEncontrado->delete();

        return response()->json(['mensaje' => __('Contacto con :indicativo eliminado.', ['indicativo' => $contactoEncontrado->estacion_llamada])]);
    }

    private function respuestaGuardado(Contacto $contacto, string $accion, bool $confirmado, int $estado = 200): JsonResponse
    {
        $reemplazos = ['indicativo' => $contacto->estacion_llamada];
        $mensaje = $accion === 'guardado'
            ? __('Contacto con :indicativo guardado.', $reemplazos)
            : __('Contacto con :indicativo actualizado.', $reemplazos);

        if ($confirmado) {
            $mensaje .= ' '.__(':indicativo también lo registró en su libro: quedó confirmado automáticamente.', $reemplazos);
        }

        $contacto->load(['user', 'banda', 'modo', 'prefijoPais', 'confirmacionesQsl']);

        return (new ContactoResource($contacto))->detalle()
            ->additional(['mensaje' => $mensaje])
            ->response()
            ->setStatusCode($estado);
    }

    /**
     * Cifras del libro completo (sin filtros) para las tarjetas de resumen.
     *
     * @return array{contactos: int, paises: int, confirmados: int, bandas: int, solicitudes_pendientes: int}
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
            'solicitudes_pendientes' => $request->user()->solicitudesPendientes()->count(),
        ];
    }

    /**
     * Solo se buscan contactos del operador con sesión: los de otros operadores responden 404.
     */
    private function buscarContactoPropio(Request $request, string $id): Contacto
    {
        $contacto = ctype_digit($id)
            ? $request->user()->contactos()->with(['user', 'banda', 'modo', 'prefijoPais', 'confirmacionesQsl'])->find((int) $id)
            : null;

        abort_if($contacto === null, 404, __('El contacto no existe o no pertenece a tu libro de guardia.'));

        return $contacto;
    }
}
