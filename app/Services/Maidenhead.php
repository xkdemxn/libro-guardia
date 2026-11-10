<?php

namespace App\Services;

/**
 * Sistema de localizadores Maidenhead ("grid locator") que usan los radioaficionados.
 *
 * Un locator como FI09 o FI09AA divide el mundo en campos (letras A-R), cuadrados (dígitos 0-9)
 * y subcuadrados (letras A-X). Aquí se convierte al punto central de esa zona.
 */
class Maidenhead
{
    private const RADIO_TIERRA_KM = 6371.0;

    /**
     * Centro del locator en grados, o null si el texto no es un locator válido.
     *
     * @return array{lat: float, lon: float}|null
     */
    public static function aCoordenadas(?string $locator): ?array
    {
        $locator = strtoupper(trim((string) $locator));

        if (preg_match('/^[A-R]{2}[0-9]{2}([A-X]{2})?$/', $locator) !== 1) {
            return null;
        }

        $lon = (ord($locator[0]) - ord('A')) * 20 - 180 + ((int) $locator[2]) * 2;
        $lat = (ord($locator[1]) - ord('A')) * 10 - 90 + ((int) $locator[3]);

        if (strlen($locator) === 6) {
            $lon += (ord($locator[4]) - ord('A')) * (5 / 60) + (2.5 / 60);
            $lat += (ord($locator[5]) - ord('A')) * (2.5 / 60) + (1.25 / 60);
        } else {
            $lon += 1;
            $lat += 0.5;
        }

        return ['lat' => round($lat, 5), 'lon' => round($lon, 5)];
    }

    /**
     * Distancia sobre la superficie de la Tierra (fórmula del semiverseno), en kilómetros.
     */
    public static function distanciaKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return self::RADIO_TIERRA_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
