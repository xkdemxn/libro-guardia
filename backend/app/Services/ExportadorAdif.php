<?php

namespace App\Services;

use App\Enums\TipoQsl;
use App\Models\ConfirmacionQsl;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Genera un archivo ADIF 3.1.4 (formato .adi), el estándar para intercambiar libros de guardia (US-12).
 *
 * Cada campo se escribe como <NOMBRE:largo>valor, el encabezado termina en <EOH> y cada contacto en <EOR>.
 * Las horas se exportan en UTC.
 */
class ExportadorAdif
{
    public const VERSION_ADIF = '3.1.4';

    /**
     * Modos que en ADIF se expresan como MODE + SUBMODE.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const MODOS_CON_SUBMODO = [
        'USB' => ['SSB', 'USB'],
        'LSB' => ['SSB', 'LSB'],
        'FT4' => ['MFSK', 'FT4'],
        'PSK31' => ['PSK', 'PSK31'],
    ];

    /**
     * @param  Collection<int, Contacto>  $contactos  con banda, modo, prefijoPais y confirmacionesQsl cargados
     */
    public function generar(User $operador, Collection $contactos): string
    {
        $adif = 'Libro de guardia de '.$operador->indicativo.' exportado el '.now('UTC')->format('Y-m-d H:i').' UTC'."\n"
            .$this->campo('ADIF_VER', self::VERSION_ADIF)
            .$this->campo('PROGRAMID', 'LibroDeGuardia')
            .$this->campo('CREATED_TIMESTAMP', now('UTC')->format('Ymd His'))
            ."<EOH>\n\n";

        foreach ($contactos as $contacto) {
            $adif .= $this->registro($operador, $contacto)."<EOR>\n\n";
        }

        return $adif;
    }

    private function registro(User $operador, Contacto $contacto): string
    {
        [$modo, $submodo] = self::MODOS_CON_SUBMODO[$contacto->modo->nombre] ?? [$contacto->modo->nombre, null];

        $campos = [
            'STATION_CALLSIGN' => $operador->indicativo,
            'CALL' => $contacto->estacion_llamada,
            'QSO_DATE' => $contacto->inicio_utc->format('Ymd'),
            'TIME_ON' => $contacto->inicio_utc->format('His'),
            'QSO_DATE_OFF' => $contacto->fin_utc?->format('Ymd'),
            'TIME_OFF' => $contacto->fin_utc?->format('His'),
            'FREQ' => $this->numero($contacto->frecuencia_mhz, 4),
            'BAND' => str_replace(' ', '', $contacto->banda->nombre),
            'MODE' => $modo,
            'SUBMODE' => $submodo,
            'RST_SENT' => $contacto->reporte_enviado,
            'RST_RCVD' => $contacto->reporte_recibido,
            'TX_PWR' => $contacto->potencia_w !== null ? $this->numero($contacto->potencia_w, 2) : null,
            'GRIDSQUARE' => $contacto->grid_locator,
            'NAME' => $contacto->nombre_operador,
            'COUNTRY' => $contacto->prefijoPais?->pais,
            'RIG' => $contacto->equipo,
            ...$this->camposActivacion($contacto->activacion),
            'NOTES' => $contacto->observaciones,
            ...$this->camposQsl($contacto),
        ];

        $registro = '';
        foreach ($campos as $nombre => $valor) {
            $registro .= $this->campo($nombre, $valor);
        }

        return $registro;
    }

    /**
     * Número con punto decimal y sin ceros sobrantes: 14.2250 → 14.225, 100.00 → 100.
     */
    private function numero(float $valor, int $decimales): string
    {
        $texto = number_format($valor, $decimales, '.', '');

        return str_contains($texto, '.') ? rtrim(rtrim($texto, '0'), '.') : $texto;
    }

    /**
     * Las referencias SOTA llevan barra (HC/PI-001); las de POTA son del tipo HC-0001.
     *
     * @return array<string, ?string>
     */
    private function camposActivacion(?string $activacion): array
    {
        if ($activacion === null || $activacion === '') {
            return [];
        }

        return str_contains($activacion, '/')
            ? ['SOTA_REF' => $activacion]
            : ['POTA_REF' => $activacion];
    }

    /**
     * @return array<string, ?string>
     */
    private function camposQsl(Contacto $contacto): array
    {
        $prefijos = [
            TipoQsl::Tarjeta->value => ['QSL_SENT', 'QSL_RCVD', 'QSLSDATE', 'QSLRDATE'],
            TipoQsl::Eqsl->value => ['EQSL_QSL_SENT', 'EQSL_QSL_RCVD', 'EQSL_QSLSDATE', 'EQSL_QSLRDATE'],
            TipoQsl::Lotw->value => ['LOTW_QSL_SENT', 'LOTW_QSL_RCVD', 'LOTW_QSLSDATE', 'LOTW_QSLRDATE'],
        ];

        $campos = [];
        foreach ($prefijos as $tipo => [$enviada, $recibida, $fechaEnvio, $fechaRecepcion]) {
            /** @var ConfirmacionQsl|null $confirmacion */
            $confirmacion = $contacto->confirmacionesQsl->first(fn (ConfirmacionQsl $c): bool => $c->tipo->value === $tipo);

            if ($confirmacion === null) {
                continue;
            }

            $campos[$enviada] = $confirmacion->enviada ? 'Y' : 'N';
            $campos[$recibida] = $confirmacion->recibida ? 'Y' : 'N';
            $campos[$fechaEnvio] = $confirmacion->fecha_envio?->format('Ymd');
            $campos[$fechaRecepcion] = $confirmacion->fecha_recepcion?->format('Ymd');
        }

        return $campos;
    }

    /**
     * Escribe un campo ADIF. Los valores se pasan a ASCII porque el largo se cuenta en caracteres
     * y muchos programas de log leen bytes; los campos vacíos se omiten.
     */
    private function campo(string $nombre, ?string $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        $valor = Str::ascii($valor);

        return '<'.$nombre.':'.strlen($valor).'>'.$valor.' ';
    }
}
