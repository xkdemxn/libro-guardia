<?php

namespace App\Services;

/**
 * Lee archivos ADIF (.adi): campos <NOMBRE:largo>valor, encabezado opcional hasta <EOH>
 * y registros terminados en <EOR>. No valida el contenido, solo separa los registros.
 */
class LectorAdif
{
    /**
     * Devuelve un arreglo por registro, con los nombres de campo en mayúsculas.
     *
     * @return list<array<string, string>>
     */
    public function leer(string $contenido): array
    {
        $inicio = $this->inicioDeRegistros($contenido);
        $registros = [];
        $actual = [];
        $posicion = $inicio;
        $largoTotal = strlen($contenido);

        while ($posicion < $largoTotal) {
            $apertura = strpos($contenido, '<', $posicion);
            if ($apertura === false) {
                break;
            }

            $cierre = strpos($contenido, '>', $apertura);
            if ($cierre === false) {
                break;
            }

            $etiqueta = substr($contenido, $apertura + 1, $cierre - $apertura - 1);
            $posicion = $cierre + 1;

            if (strcasecmp($etiqueta, 'EOR') === 0) {
                if ($actual !== []) {
                    $registros[] = $actual;
                }
                $actual = [];

                continue;
            }

            // <NOMBRE:largo> o <NOMBRE:largo:tipo>
            if (preg_match('/^([A-Za-z0-9_]+):(\d+)(?::[A-Za-z])?$/', $etiqueta, $partes) !== 1) {
                continue;
            }

            $largo = (int) $partes[2];
            $actual[strtoupper($partes[1])] = trim(substr($contenido, $posicion, $largo));
            $posicion += $largo;
        }

        return $registros;
    }

    /**
     * Según la especificación ADIF, si el primer carácter es "<" no hay encabezado;
     * si no, el encabezado termina en <EOH>. Sin <EOH> el archivo no es ADIF válido.
     */
    private function inicioDeRegistros(string $contenido): int
    {
        if (str_starts_with($contenido, '<')) {
            return 0;
        }

        $finEncabezado = stripos($contenido, '<EOH>');

        return $finEncabezado === false ? strlen($contenido) : $finEncabezado + strlen('<EOH>');
    }
}
