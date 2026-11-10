<?php

namespace Tests\Unit;

use App\Services\Maidenhead;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class MaidenheadTest extends TestCase
{
    public function test_convierte_un_locator_de_4_caracteres_al_centro_del_cuadrado(): void
    {
        // FI09 cubre Quito: centro en -0.5, -79.
        $this->assertSame(['lat' => -0.5, 'lon' => -79.0], Maidenhead::aCoordenadas('FI09'));
    }

    public function test_convierte_un_locator_de_6_caracteres_con_mas_precision(): void
    {
        // FN31pr es la estación W1AW (Newington, EE. UU.): 41.714, -72.727.
        $coordenadas = Maidenhead::aCoordenadas('fn31pr');

        $this->assertEqualsWithDelta(41.714, $coordenadas['lat'], 0.05);
        $this->assertEqualsWithDelta(-72.727, $coordenadas['lon'], 0.05);
    }

    #[TestWith(['ZZ99'], 'letras fuera de A-R')]
    #[TestWith(['FI9'], 'incompleto')]
    #[TestWith([''], 'vacío')]
    #[TestWith([null], 'nulo')]
    public function test_devuelve_null_para_locators_invalidos(?string $locator): void
    {
        $this->assertNull(Maidenhead::aCoordenadas($locator));
    }

    public function test_un_grado_sobre_el_ecuador_mide_unos_111_km(): void
    {
        $this->assertEqualsWithDelta(111.19, Maidenhead::distanciaKm(0, 0, 0, 1), 0.01);
    }
}
