<?php

namespace App\Services;

use App\Enums\TipoQsl;
use App\Models\Banda;
use App\Models\Contacto;
use App\Models\Modo;
use App\Models\PrefijoPais;
use App\Models\User;
use App\Rules\IndicativoValido;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Importa contactos desde un archivo ADIF (US-14), con las mismas reglas que el formulario:
 * indicativo válido, frecuencia dentro de una banda, reporte según el modo y fin no anterior al inicio.
 * Las horas del archivo ya vienen en UTC y se conservan así.
 */
class ImportadorAdif
{
    public const NUEVO = 'nuevo';

    public const DUPLICADO = 'duplicado';

    public const ERROR = 'error';

    /**
     * @var array<string, array{0: TipoQsl, 1: string, 2: string, 3: string, 4: string}>
     */
    private const CAMPOS_QSL = [
        'tarjeta' => [TipoQsl::Tarjeta, 'QSL_SENT', 'QSL_RCVD', 'QSLSDATE', 'QSLRDATE'],
        'eqsl' => [TipoQsl::Eqsl, 'EQSL_QSL_SENT', 'EQSL_QSL_RCVD', 'EQSL_QSLSDATE', 'EQSL_QSLRDATE'],
        'lotw' => [TipoQsl::Lotw, 'LOTW_QSL_SENT', 'LOTW_QSL_RCVD', 'LOTW_QSLSDATE', 'LOTW_QSLRDATE'],
    ];

    /** @var Collection<int, Banda>|null */
    private ?Collection $bandas = null;

    /** @var Collection<string, Modo>|null */
    private ?Collection $modos = null;

    public function __construct(
        private LectorAdif $lector,
        private ConfirmadorQsl $confirmador,
    ) {}

    /**
     * Analiza el archivo sin guardar nada: cada fila dice si es nueva, duplicada o tiene errores.
     *
     * @return list<array{numero: int, estado: string, indicativo: string, fecha: ?string, banda: ?string, modo: ?string, errores: list<string>, datos: array<string, mixed>, qsl: list<array<string, mixed>>}>
     */
    public function analizar(User $operador, string $contenido): array
    {
        $filas = [];
        $vistosEnArchivo = [];

        foreach ($this->lector->leer($contenido) as $indice => $registro) {
            $fila = $this->analizarRegistro($indice + 1, $registro);

            if ($fila['estado'] === self::NUEVO) {
                $clave = $this->claveDuplicado($fila['datos']);
                $yaEnLibro = $operador->contactos()
                    ->posiblesDuplicados(
                        $fila['datos']['estacion_llamada'],
                        $fila['datos']['banda_id'],
                        $fila['datos']['modo_id'],
                        $fila['datos']['inicio_utc'],
                    )
                    ->exists();

                if ($yaEnLibro || isset($vistosEnArchivo[$clave])) {
                    $fila['estado'] = self::DUPLICADO;
                }

                $vistosEnArchivo[$clave] = true;
            }

            $filas[] = $fila;
        }

        return $filas;
    }

    /**
     * Guarda las filas nuevas (las duplicadas y con errores se omiten) y confirma automáticamente las QSL internas.
     *
     * @param  list<array<string, mixed>>  $filas  resultado de analizar()
     * @return int cantidad de contactos importados
     */
    public function importar(User $operador, array $filas): int
    {
        $nuevas = array_filter($filas, fn (array $fila): bool => $fila['estado'] === self::NUEVO);

        $creados = DB::transaction(function () use ($operador, $nuevas): array {
            $creados = [];

            foreach ($nuevas as $fila) {
                $contacto = $operador->contactos()->create($fila['datos']);

                foreach ($fila['qsl'] as $qsl) {
                    $contacto->confirmacionesQsl()->create($qsl);
                }

                $creados[] = $contacto;
            }

            return $creados;
        });

        foreach ($creados as $contacto) {
            $this->confirmador->sincronizar($contacto);
        }

        return count($creados);
    }

    /**
     * @param  array<string, string>  $registro
     * @return array{numero: int, estado: string, indicativo: string, fecha: ?string, banda: ?string, modo: ?string, errores: list<string>, datos: array<string, mixed>, qsl: list<array<string, mixed>>}
     */
    private function analizarRegistro(int $numero, array $registro): array
    {
        $errores = [];
        $indicativo = IndicativoValido::normalizar($registro['CALL'] ?? '');

        if (! IndicativoValido::esValido($indicativo)) {
            $errores[] = $indicativo === '' ? __('Falta el indicativo (CALL).') : __('Indicativo inválido: :indicativo.', ['indicativo' => $indicativo]);
        }

        $inicio = $this->fechaHora($registro['QSO_DATE'] ?? null, $registro['TIME_ON'] ?? null);
        if ($inicio === null) {
            $errores[] = __('Fecha u hora de inicio inválida (QSO_DATE / TIME_ON).');
        }

        $fin = isset($registro['TIME_OFF'])
            ? $this->fechaHora($registro['QSO_DATE_OFF'] ?? $registro['QSO_DATE'] ?? null, $registro['TIME_OFF'])
            : null;
        if ($inicio !== null && $fin !== null && $fin->lessThan($inicio)) {
            $errores[] = __('La hora de fin es menor que la de inicio.');
        }

        $frecuencia = isset($registro['FREQ']) && is_numeric($registro['FREQ']) ? (float) $registro['FREQ'] : null;
        $banda = $frecuencia !== null ? $this->bandaPara($frecuencia) : null;
        if ($frecuencia === null) {
            $errores[] = __('Falta la frecuencia (FREQ).');
        } elseif ($banda === null) {
            $errores[] = __('La frecuencia :frecuencia MHz está fuera de las bandas de radioaficionados.', ['frecuencia' => $frecuencia]);
        }

        $modo = $this->modoPara($registro, $frecuencia);
        if ($modo === null) {
            $errores[] = __('Modo no reconocido: :modo.', ['modo' => trim(($registro['MODE'] ?? '').' '.($registro['SUBMODE'] ?? ''))]);
        }

        foreach (['RST_SENT' => 'enviado', 'RST_RCVD' => 'recibido'] as $campo => $nombre) {
            $reporte = $registro[$campo] ?? '';
            if ($modo !== null && ! $modo->tipo_reporte->esValido($reporte)) {
                $errores[] = $reporte === ''
                    ? __('Falta el reporte :nombre (:campo).', ['nombre' => __($nombre), 'campo' => $campo])
                    : __('El reporte :nombre «:reporte» no corresponde al modo :modo.', ['nombre' => __($nombre), 'reporte' => $reporte, 'modo' => $modo->nombre]);
            }
        }

        $datos = $errores !== [] ? [] : [
            'estacion_llamada' => $indicativo,
            'inicio_utc' => $inicio,
            'fin_utc' => $fin,
            'frecuencia_mhz' => $frecuencia,
            'banda_id' => $banda->id,
            'modo_id' => $modo->id,
            'prefijo_pais_id' => PrefijoPais::paraIndicativo($indicativo)?->id,
            'reporte_enviado' => $registro['RST_SENT'],
            'reporte_recibido' => $registro['RST_RCVD'],
            'potencia_w' => isset($registro['TX_PWR']) && is_numeric($registro['TX_PWR']) ? (float) $registro['TX_PWR'] : null,
            'grid_locator' => $this->gridLocator($registro['GRIDSQUARE'] ?? null),
            'nombre_operador' => isset($registro['NAME']) ? mb_substr($registro['NAME'], 0, 100) : null,
            'equipo' => isset($registro['RIG']) ? mb_substr($registro['RIG'], 0, 255) : null,
            'activacion' => $registro['POTA_REF'] ?? $registro['SOTA_REF'] ?? null,
            'observaciones' => $registro['NOTES'] ?? $registro['COMMENT'] ?? null,
        ];

        return [
            'numero' => $numero,
            'estado' => $errores === [] ? self::NUEVO : self::ERROR,
            'indicativo' => $indicativo,
            'fecha' => $inicio?->format('Y-m-d H:i'),
            'banda' => $banda?->nombre,
            'modo' => $modo?->nombre,
            'errores' => $errores,
            'datos' => $datos,
            'qsl' => $errores === [] ? $this->confirmacionesQsl($registro) : [],
        ];
    }

    /**
     * ADIF usa fecha YYYYMMDD y hora HHMM o HHMMSS, siempre en UTC.
     */
    private function fechaHora(?string $fecha, ?string $hora): ?CarbonImmutable
    {
        if ($fecha === null || $hora === null || preg_match('/^\d{8}$/', $fecha) !== 1 || preg_match('/^\d{4}(\d{2})?$/', $hora) !== 1) {
            return null;
        }

        $hora = str_pad($hora, 6, '0');
        $resultado = CarbonImmutable::createFromFormat('YmdHis', $fecha.$hora, 'UTC');

        return $resultado === false || $resultado->format('YmdHis') !== $fecha.$hora ? null : $resultado;
    }

    private function bandaPara(float $frecuencia): ?Banda
    {
        $this->bandas ??= Banda::all();

        return $this->bandas->first(
            fn (Banda $banda): bool => $frecuencia >= $banda->frecuencia_min_mhz && $frecuencia <= $banda->frecuencia_max_mhz,
        );
    }

    /**
     * Usa SUBMODE si lo reconocemos (USB, LSB, FT4...); si no, MODE. Un "SSB" sin submodo sigue la
     * convención de la radioafición: LSB por debajo de 10 MHz y USB por encima.
     *
     * @param  array<string, string>  $registro
     */
    private function modoPara(array $registro, ?float $frecuencia): ?Modo
    {
        $this->modos ??= Modo::all()->keyBy(fn (Modo $modo): string => strtoupper($modo->nombre));

        $submodo = strtoupper($registro['SUBMODE'] ?? '');
        $modo = strtoupper($registro['MODE'] ?? '');

        if ($submodo !== '' && $this->modos->has($submodo)) {
            return $this->modos->get($submodo);
        }

        if ($modo === 'SSB') {
            return $this->modos->get($frecuencia !== null && $frecuencia < 10 ? 'LSB' : 'USB');
        }

        return $this->modos->get($modo);
    }

    private function gridLocator(?string $grid): ?string
    {
        $grid = strtoupper(substr(trim((string) $grid), 0, 6));

        return preg_match('/^[A-R]{2}[0-9]{2}([A-X]{2})?$/', $grid) === 1 ? $grid : null;
    }

    /**
     * Estados de QSL del archivo (Y = sí) para tarjeta, eQSL y LoTW.
     *
     * @param  array<string, string>  $registro
     * @return list<array<string, mixed>>
     */
    private function confirmacionesQsl(array $registro): array
    {
        $confirmaciones = [];

        foreach (self::CAMPOS_QSL as [$tipo, $enviada, $recibida, $fechaEnvio, $fechaRecepcion]) {
            $fueEnviada = strtoupper($registro[$enviada] ?? '') === 'Y';
            $fueRecibida = strtoupper($registro[$recibida] ?? '') === 'Y';

            if (! $fueEnviada && ! $fueRecibida) {
                continue;
            }

            $confirmaciones[] = [
                'tipo' => $tipo,
                'enviada' => $fueEnviada,
                'fecha_envio' => $fueEnviada ? $this->fecha($registro[$fechaEnvio] ?? null) : null,
                'recibida' => $fueRecibida,
                'fecha_recepcion' => $fueRecibida ? $this->fecha($registro[$fechaRecepcion] ?? null) : null,
            ];
        }

        return $confirmaciones;
    }

    private function fecha(?string $fecha): ?string
    {
        return $fecha !== null && preg_match('/^(\d{4})(\d{2})(\d{2})$/', $fecha, $partes) === 1
            ? "{$partes[1]}-{$partes[2]}-{$partes[3]}"
            : null;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function claveDuplicado(array $datos): string
    {
        return $datos['estacion_llamada'].'|'.$datos['banda_id'].'|'.$datos['modo_id'].'|'
            .intdiv($datos['inicio_utc']->getTimestamp(), Contacto::MINUTOS_DUPLICADO * 60);
    }
}
