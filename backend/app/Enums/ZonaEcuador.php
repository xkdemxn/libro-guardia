<?php

namespace App\Enums;

use App\Rules\IndicativoValido;

/**
 * Zonas de radioaficionados de Ecuador (mapa de DX Ecuador, preparado por HC6TVB).
 * El número de la zona es el dígito del indicativo ecuatoriano: HC1QRC → zona 1, HD8XX → zona 8.
 */
enum ZonaEcuador: int
{
    case Zona1 = 1;
    case Zona2 = 2;
    case Zona3 = 3;
    case Zona4 = 4;
    case Zona5 = 5;
    case Zona6 = 6;
    case Zona7 = 7;
    case Zona8 = 8;
    case Zona9 = 9;

    /**
     * @return list<string>
     */
    public function provincias(): array
    {
        return match ($this) {
            self::Zona1 => ['Pichincha', 'Imbabura', 'Carchi', 'Santo Domingo'],
            self::Zona2 => ['Guayas', 'Los Ríos', 'Santa Elena'],
            self::Zona3 => ['El Oro', 'Loja'],
            self::Zona4 => ['Esmeraldas', 'Manabí'],
            self::Zona5 => ['Azuay', 'Cañar', 'Chimborazo'],
            self::Zona6 => ['Cotopaxi', 'Tungurahua', 'Bolívar'],
            self::Zona7 => ['Sucumbíos', 'Napo', 'Orellana', 'Pastaza', 'Morona Santiago', 'Zamora Chinchipe'],
            self::Zona8 => ['Galápagos'],
            self::Zona9 => ['Antártida (estación Pedro Vicente Maldonado)'],
        };
    }

    public function nombre(): string
    {
        return 'Zona '.$this->value;
    }

    /**
     * «Zona 5 · Azuay, Cañar, Chimborazo».
     */
    public function descripcion(): string
    {
        return $this->nombre().' · '.implode(', ', $this->provincias());
    }

    /**
     * Zona de un indicativo ecuatoriano (HC/HD + dígito), o null si no es de Ecuador.
     * En operaciones portátiles manda el prefijo antepuesto: HC2/LU4BB opera desde la zona 2.
     */
    public static function paraIndicativo(?string $indicativo): ?self
    {
        $partes = explode('/', IndicativoValido::normalizar((string) $indicativo));
        usort($partes, fn (string $a, string $b): int => strlen($a) <=> strlen($b));

        foreach ($partes as $parte) {
            if (preg_match('/^H[CD]([1-9])/', $parte, $coincidencia) === 1) {
                return self::from((int) $coincidencia[1]);
            }
        }

        return null;
    }
}
