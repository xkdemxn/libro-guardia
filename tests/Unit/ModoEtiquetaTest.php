<?php

namespace Tests\Unit;

use App\Models\Modo;
use PHPUnit\Framework\TestCase;

class ModoEtiquetaTest extends TestCase
{
    public function test_usb_y_lsb_se_muestran_como_variantes_de_ssb(): void
    {
        $this->assertSame('SSB · USB', (new Modo(['nombre' => 'USB']))->etiqueta);
        $this->assertSame('SSB · LSB', (new Modo(['nombre' => 'LSB']))->etiqueta);
        $this->assertSame('CW', (new Modo(['nombre' => 'CW']))->etiqueta);
    }
}
