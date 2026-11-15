<?php

namespace Tests\Unit;

use App\Enums\ZonaEcuador;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ZonaEcuadorTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: ?int}>
     */
    public static function indicativos(): array
    {
        return [
            'zona 1 (Pichincha)' => ['HC1QRC', 1],
            'zona 5 en minúsculas' => ['hc5ev', 5],
            'prefijo HD' => ['HD2AB', 2],
            'Galápagos' => ['HC8GR', 8],
            'portátil: manda el prefijo antepuesto' => ['HC2/LU4BB', 2],
            'sufijo portátil' => ['HC6TVB/P', 6],
            'no es de Ecuador' => ['LU4BB', null],
            'dígito 0 no es una zona' => ['HC0AA', null],
        ];
    }

    #[DataProvider('indicativos')]
    public function test_detecta_la_zona_por_el_digito_del_indicativo(string $indicativo, ?int $zona): void
    {
        $this->assertSame($zona, ZonaEcuador::paraIndicativo($indicativo)?->value);
    }

    public function test_describe_la_zona_con_sus_provincias(): void
    {
        $this->assertSame('Zona 5 · Azuay, Cañar, Chimborazo', ZonaEcuador::Zona5->descripcion());
        $this->assertCount(9, ZonaEcuador::cases());
    }
}
