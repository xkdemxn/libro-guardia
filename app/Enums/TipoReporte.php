<?php

namespace App\Enums;

/**
 * Tipo de reporte de señal según el modo de emisión.
 *
 * RS para voz (59), RST para telegrafía (599) y dB para modos digitales (-12).
 */
enum TipoReporte: string
{
    case Rs = 'RS';
    case Rst = 'RST';
    case Db = 'dB';

    /**
     * Expresión regular que debe cumplir un reporte de este tipo.
     */
    public function patron(): string
    {
        return match ($this) {
            self::Rs => '/^[1-5][1-9]$/',
            self::Rst => '/^[1-5][1-9][1-9]$/',
            self::Db => '/^[+-]?([0-9]|[1-4][0-9]|50)$/',
        };
    }

    public function ejemplo(): string
    {
        return match ($this) {
            self::Rs => '59',
            self::Rst => '599',
            self::Db => '-12',
        };
    }

    public function esValido(string $reporte): bool
    {
        return preg_match($this->patron(), trim($reporte)) === 1;
    }
}
