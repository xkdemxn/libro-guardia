<?php

namespace Database\Seeders;

use App\Models\Banda;
use Illuminate\Database\Seeder;

/**
 * Bandas de radioaficionados según el plan de la IARU Región 2 (Américas).
 */
class BandaSeeder extends Seeder
{
    /**
     * @var list<array{0: string, 1: float, 2: float}>
     */
    private const BANDAS = [
        ['160 m', 1.8, 2.0],
        ['80 m', 3.5, 4.0],
        ['60 m', 5.3305, 5.4065],
        ['40 m', 7.0, 7.3],
        ['30 m', 10.1, 10.15],
        ['20 m', 14.0, 14.35],
        ['17 m', 18.068, 18.168],
        ['15 m', 21.0, 21.45],
        ['12 m', 24.89, 24.99],
        ['10 m', 28.0, 29.7],
        ['6 m', 50.0, 54.0],
        ['2 m', 144.0, 148.0],
        ['70 cm', 420.0, 450.0],
    ];

    public function run(): void
    {
        foreach (self::BANDAS as [$nombre, $minimo, $maximo]) {
            Banda::updateOrCreate(
                ['nombre' => $nombre],
                ['frecuencia_min_mhz' => $minimo, 'frecuencia_max_mhz' => $maximo],
            );
        }
    }
}
