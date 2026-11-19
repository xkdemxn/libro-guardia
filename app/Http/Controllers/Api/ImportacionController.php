<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImportadorAdif;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

/**
 * Importar contactos desde un archivo ADIF (US-12) en dos pasos: subir y revisar la vista previa,
 * luego confirmar. El archivo queda guardado hasta confirmar o cancelar.
 */
class ImportacionController extends Controller
{
    private const CLAVE_SESION = 'importacion_adif';

    private const FILAS_EN_VISTA_PREVIA = 200;

    public function __construct(private ImportadorAdif $importador) {}

    /**
     * Recibe el archivo y devuelve la vista previa; un archivo que no es ADIF se rechaza.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'archivo' => ['required', File::default()->max(5 * 1024)],
        ], [
            'archivo.required' => 'Elige un archivo ADIF (.adi o .adif).',
            'archivo.max' => 'El archivo no puede pesar más de 5 MB.',
        ]);

        $archivo = $request->file('archivo');
        $extension = strtolower($archivo->getClientOriginalExtension());
        $contenido = (string) file_get_contents($archivo->getRealPath());

        if (! in_array($extension, ['adi', 'adif'], true) || $this->importador->analizar($request->user(), $contenido) === []) {
            throw ValidationException::withMessages(['archivo' => 'El archivo no tiene un formato ADIF válido o no contiene contactos.']);
        }

        $this->borrarArchivoPendiente($request);

        $ruta = 'importaciones/'.$request->user()->id.'-'.Str::uuid().'.adi';
        Storage::disk('local')->put($ruta, $contenido);
        $request->session()->put(self::CLAVE_SESION, [
            'ruta' => $ruta,
            'nombre' => $archivo->getClientOriginalName(),
        ]);

        return $this->vistaPrevia($request);
    }

    /**
     * Vista previa de la importación pendiente: qué se va a importar, qué se omite por duplicado
     * y qué tiene errores. 404 si no hay ninguna.
     */
    public function show(Request $request): JsonResponse
    {
        return $this->vistaPrevia($request);
    }

    /**
     * Importa las filas nuevas y devuelve el resumen.
     */
    public function confirmar(Request $request): JsonResponse
    {
        $pendiente = $this->archivoPendiente($request);

        abort_if($pendiente === null, 404, 'No hay ninguna importación pendiente.');

        $filas = $this->importador->analizar($request->user(), Storage::disk('local')->get($pendiente['ruta']));
        $importados = $this->importador->importar($request->user(), $filas);
        $conErrores = array_values(array_filter($filas, fn (array $fila): bool => $fila['estado'] === ImportadorAdif::ERROR));

        $this->borrarArchivoPendiente($request);

        return response()->json([
            'importados' => $importados,
            'omitidos' => count(array_filter($filas, fn (array $fila): bool => $fila['estado'] === ImportadorAdif::DUPLICADO)),
            'fallidos' => count($conErrores),
            'errores' => array_map(fn (array $fila): array => [
                'numero' => $fila['numero'],
                'indicativo' => $fila['indicativo'],
                'errores' => $fila['errores'],
            ], array_slice($conErrores, 0, 50)),
        ]);
    }

    public function destroy(Request $request): Response
    {
        $this->borrarArchivoPendiente($request);

        return response()->noContent();
    }

    private function vistaPrevia(Request $request): JsonResponse
    {
        $pendiente = $this->archivoPendiente($request);

        abort_if($pendiente === null, 404, 'No hay ninguna importación pendiente.');

        $filas = $this->importador->analizar($request->user(), Storage::disk('local')->get($pendiente['ruta']));
        $conteo = collect($filas)->countBy('estado');

        return response()->json([
            'nombre_archivo' => $pendiente['nombre'],
            'total_filas' => count($filas),
            'nuevos' => $conteo->get(ImportadorAdif::NUEVO, 0),
            'duplicados' => $conteo->get(ImportadorAdif::DUPLICADO, 0),
            'errores' => $conteo->get(ImportadorAdif::ERROR, 0),
            'filas' => array_map(fn (array $fila): array => [
                'numero' => $fila['numero'],
                'estado' => $fila['estado'],
                'indicativo' => $fila['indicativo'],
                'fecha' => $fila['fecha'],
                'banda' => $fila['banda'],
                'modo' => $fila['modo'],
                'errores' => $fila['errores'],
            ], array_slice($filas, 0, self::FILAS_EN_VISTA_PREVIA)),
        ]);
    }

    /**
     * @return array{ruta: string, nombre: string}|null
     */
    private function archivoPendiente(Request $request): ?array
    {
        $pendiente = $request->session()->get(self::CLAVE_SESION);

        return $pendiente !== null && Storage::disk('local')->exists($pendiente['ruta']) ? $pendiente : null;
    }

    private function borrarArchivoPendiente(Request $request): void
    {
        $pendiente = $request->session()->pull(self::CLAVE_SESION);

        if ($pendiente !== null) {
            Storage::disk('local')->delete($pendiente['ruta']);
        }
    }
}
