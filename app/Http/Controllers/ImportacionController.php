<?php

namespace App\Http\Controllers;

use App\Services\ImportadorAdif;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

/**
 * Importar contactos desde un archivo ADIF de otro programa (US-14):
 * subir el archivo → ver la vista previa → confirmar → ver el resumen.
 */
class ImportacionController extends Controller
{
    private const CLAVE_SESION = 'importacion_adif';

    private const FILAS_EN_VISTA_PREVIA = 200;

    public function __construct(private ImportadorAdif $importador) {}

    /**
     * Formulario para subir el archivo (y resumen de la última importación, si la hubo).
     */
    public function create(): View
    {
        return view('contactos.importar', ['resultado' => session('resultado')]);
    }

    /**
     * Recibe el archivo y lleva a la vista previa; un archivo que no es ADIF se rechaza con un mensaje.
     */
    public function store(Request $request): RedirectResponse
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
            return back()->withErrors(['archivo' => 'El archivo no tiene un formato ADIF válido o no contiene contactos.']);
        }

        $this->borrarArchivoPendiente($request);

        $ruta = 'importaciones/'.$request->user()->id.'-'.Str::uuid().'.adi';
        Storage::disk('local')->put($ruta, $contenido);
        $request->session()->put(self::CLAVE_SESION, [
            'ruta' => $ruta,
            'nombre' => $archivo->getClientOriginalName(),
        ]);

        return redirect()->route('importacion.vista-previa');
    }

    /**
     * Vista previa: qué se va a importar, qué se omite por duplicado y qué tiene errores.
     */
    public function vistaPrevia(Request $request): View|RedirectResponse
    {
        $pendiente = $this->archivoPendiente($request);

        if ($pendiente === null) {
            return redirect()->route('importacion.create');
        }

        $filas = $this->importador->analizar($request->user(), Storage::disk('local')->get($pendiente['ruta']));
        $conteo = collect($filas)->countBy('estado');

        return view('contactos.importar-vista-previa', [
            'nombreArchivo' => $pendiente['nombre'],
            'filas' => array_slice($filas, 0, self::FILAS_EN_VISTA_PREVIA),
            'totalFilas' => count($filas),
            'nuevos' => $conteo->get(ImportadorAdif::NUEVO, 0),
            'duplicados' => $conteo->get(ImportadorAdif::DUPLICADO, 0),
            'errores' => $conteo->get(ImportadorAdif::ERROR, 0),
        ]);
    }

    /**
     * Importa las filas nuevas y muestra el resumen.
     */
    public function confirmar(Request $request): RedirectResponse
    {
        $pendiente = $this->archivoPendiente($request);

        if ($pendiente === null) {
            return redirect()->route('importacion.create')->with('error', 'No hay ninguna importación pendiente.');
        }

        $filas = $this->importador->analizar($request->user(), Storage::disk('local')->get($pendiente['ruta']));
        $importados = $this->importador->importar($request->user(), $filas);
        $conErrores = array_values(array_filter($filas, fn (array $fila): bool => $fila['estado'] === ImportadorAdif::ERROR));

        $this->borrarArchivoPendiente($request);

        return redirect()->route('importacion.create')->with('resultado', [
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

    public function cancelar(Request $request): RedirectResponse
    {
        $this->borrarArchivoPendiente($request);

        return redirect()->route('importacion.create');
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
