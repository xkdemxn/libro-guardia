<?php

namespace Database\Seeders;

use App\Enums\TipoReporte;
use App\Models\Modo;
use Illuminate\Database\Seeder;

/**
 * Modos de emisión y el tipo de reporte que usa cada uno.
 */
class ModoSeeder extends Seeder
{
    /**
     * @var array<string, TipoReporte>
     */
    private const MODOS = [
        'USB' => TipoReporte::Rs,
        'LSB' => TipoReporte::Rs,
        'AM' => TipoReporte::Rs,
        'FM' => TipoReporte::Rs,
        'SSTV' => TipoReporte::Rs,
        'CW' => TipoReporte::Rst,
        'RTTY' => TipoReporte::Rst,
        'PSK31' => TipoReporte::Rst,
        'FT8' => TipoReporte::Db,
        'FT4' => TipoReporte::Db,
        'JT65' => TipoReporte::Db,
    ];

    public function run(): void
    {
        foreach (self::MODOS as $nombre => $tipoReporte) {
            Modo::updateOrCreate(['nombre' => $nombre], ['tipo_reporte' => $tipoReporte]);
        }
    }
}
