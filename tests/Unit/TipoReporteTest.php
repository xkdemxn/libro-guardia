<?php

namespace Tests\Unit;

use App\Enums\TipoReporte;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class TipoReporteTest extends TestCase
{
    #[TestWith([TipoReporte::Rs, '59'])]
    #[TestWith([TipoReporte::Rs, '31'])]
    #[TestWith([TipoReporte::Rst, '599'])]
    #[TestWith([TipoReporte::Rst, '579'])]
    #[TestWith([TipoReporte::Db, '-12'])]
    #[TestWith([TipoReporte::Db, '+5'])]
    #[TestWith([TipoReporte::Db, '0'])]
    public function test_acepta_reportes_validos_para_su_tipo(TipoReporte $tipo, string $reporte): void
    {
        $this->assertTrue($tipo->esValido($reporte));
    }

    #[TestWith([TipoReporte::Rs, '599'], 'RST en voz')]
    #[TestWith([TipoReporte::Rs, '69'], 'legibilidad mayor a 5')]
    #[TestWith([TipoReporte::Rs, '50'], 'fuerza cero')]
    #[TestWith([TipoReporte::Rst, '59'], 'RS en CW')]
    #[TestWith([TipoReporte::Db, '599'], 'RST en digital')]
    #[TestWith([TipoReporte::Db, '-60'], 'fuera de rango')]
    public function test_rechaza_reportes_que_no_corresponden_al_tipo(TipoReporte $tipo, string $reporte): void
    {
        $this->assertFalse($tipo->esValido($reporte));
    }
}
