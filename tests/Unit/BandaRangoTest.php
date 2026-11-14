<?php

namespace Tests\Unit;

use App\Models\Banda;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class BandaRangoTest extends TestCase
{
    #[TestWith([1.8, 'MF'], '160 m')]
    #[TestWith([7.0, 'HF'], '40 m')]
    #[TestWith([28.0, 'HF'], '10 m')]
    #[TestWith([50.0, 'VHF'], '6 m')]
    #[TestWith([144.0, 'VHF'], '2 m')]
    #[TestWith([222.0, 'VHF'], '1.25 m')]
    #[TestWith([420.0, 'UHF'], '70 cm')]
    #[TestWith([1240.0, 'UHF'], '23 cm')]
    public function test_clasifica_la_frecuencia_en_su_rango_del_espectro(float $mhz, string $rango): void
    {
        $this->assertSame($rango, Banda::rangoPara($mhz));
    }
}
