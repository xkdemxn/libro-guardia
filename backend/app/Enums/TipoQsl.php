<?php

namespace App\Enums;

/**
 * Medio por el que se confirma un contacto.
 *
 * "Interna" es la confirmación automática cuando ambos operadores
 * registraron el mismo contacto en este sistema.
 */
enum TipoQsl: string
{
    case Tarjeta = 'tarjeta';
    case Eqsl = 'eqsl';
    case Lotw = 'lotw';
    case Interna = 'interna';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Tarjeta => 'Tarjeta',
            self::Eqsl => 'eQSL',
            self::Lotw => 'LoTW',
            self::Interna => 'Libro de Guardia',
        };
    }
}
