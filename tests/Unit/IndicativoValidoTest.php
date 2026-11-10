<?php

namespace Tests\Unit;

use App\Rules\IndicativoValido;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class IndicativoValidoTest extends TestCase
{
    #[TestWith(['hc1 qrc', 'HC1QRC'])]
    #[TestWith(['  lu4bb ', 'LU4BB'])]
    #[TestWith(['ea8/hc1qrc', 'EA8/HC1QRC'])]
    public function test_normaliza_a_mayusculas_sin_espacios(string $entrada, string $esperado): void
    {
        $this->assertSame($esperado, IndicativoValido::normalizar($entrada));
    }

    #[TestWith(['HC1QRC'])]
    #[TestWith(['LU4BB'])]
    #[TestWith(['K1A'])]
    #[TestWith(['3D2AB'])]
    #[TestWith(['HC1QRC/P'])]
    #[TestWith(['EA8/HC1QRC'])]
    public function test_acepta_indicativos_validos(string $indicativo): void
    {
        $this->assertTrue(IndicativoValido::esValido($indicativo));
    }

    #[TestWith(['HOLA'], 'sin número')]
    #[TestWith(['12345'], 'solo números')]
    #[TestWith(['HC1'], 'termina en número')]
    #[TestWith(['HC1-QRC'], 'con guion')]
    #[TestWith([''], 'vacío')]
    public function test_rechaza_indicativos_invalidos(string $indicativo): void
    {
        $this->assertFalse(IndicativoValido::esValido($indicativo));
    }
}
