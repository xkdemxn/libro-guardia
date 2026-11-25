<?php

namespace App\Support;

/**
 * Listas de países, provincias de Ecuador y ciudades para los desplegables del perfil (config/ubicaciones.php).
 */
class Ubicaciones
{
    public const ECUADOR = 'Ecuador';

    /**
     * @var array<string, string>
     */
    private const CONTINENTES = [
        'AF' => 'África',
        'AN' => 'Antártida',
        'AS' => 'Asia',
        'EU' => 'Europa',
        'NA' => 'Norteamérica',
        'OC' => 'Oceanía',
        'SA' => 'Sudamérica',
    ];

    /**
     * @return list<string>
     */
    public static function paises(): array
    {
        $paises = array_keys(config('ubicaciones.paises'));
        sort($paises, SORT_LOCALE_STRING);

        return $paises;
    }

    /**
     * @return list<string>
     */
    public static function provincias(): array
    {
        return array_keys(config('ubicaciones.ecuador'));
    }

    /**
     * Ciudades de un país; en Ecuador, los cantones de la provincia (o de todo el país si no hay provincia).
     *
     * @return list<string>
     */
    public static function ciudades(?string $pais, ?string $provincia = null): array
    {
        if (self::esEcuador($pais)) {
            $cantones = config('ubicaciones.ecuador');

            return $provincia !== null ? ($cantones[$provincia] ?? []) : array_merge(...array_values($cantones));
        }

        return config("ubicaciones.paises.{$pais}", []);
    }

    /**
     * Provincia ecuatoriana de un cantón; si el nombre se repite (Bolívar, Olmedo) devuelve la primera.
     */
    public static function provinciaDe(string $ciudad): ?string
    {
        foreach (config('ubicaciones.ecuador') as $provincia => $cantones) {
            if (in_array($ciudad, $cantones, true)) {
                return $provincia;
            }
        }

        return null;
    }

    public static function esEcuador(?string $pais): bool
    {
        return in_array($pais, [self::ECUADOR, 'Islas Galápagos'], true);
    }

    public static function continente(?string $codigo): ?string
    {
        return self::CONTINENTES[$codigo] ?? null;
    }

    /**
     * Todo lo que necesitan los desplegables en el navegador.
     *
     * @return array{paises: list<string>, ecuador: array<string, list<string>>, ciudades: array<string, list<string>>}
     */
    public static function paraFormulario(): array
    {
        return [
            'paises' => self::paises(),
            'ecuador' => config('ubicaciones.ecuador'),
            'ciudades' => config('ubicaciones.paises'),
        ];
    }
}
