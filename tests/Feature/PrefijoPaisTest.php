<?php

namespace Tests\Feature;

use App\Models\PrefijoPais;
use Database\Seeders\PrefijoPaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PrefijoPaisTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['HC1QRC', 'Ecuador'])]
    #[TestWith(['HC8ABC', 'Islas Galápagos'], 'prefijo más largo gana')]
    #[TestWith(['LU4BB', 'Argentina'])]
    #[TestWith(['KP4XX', 'Puerto Rico'], 'KP4 gana sobre K')]
    #[TestWith(['W1AW', 'Estados Unidos'])]
    #[TestWith(['EA8/HC1QRC', 'Islas Canarias'], 'operando desde otro país')]
    #[TestWith(['HC1QRC/P', 'Ecuador'], 'sufijo portátil')]
    public function test_deduce_el_pais_desde_el_prefijo_del_indicativo(string $indicativo, string $pais): void
    {
        $this->seed(PrefijoPaisSeeder::class);

        $this->assertSame($pais, PrefijoPais::paraIndicativo($indicativo)?->pais);
    }

    public function test_devuelve_null_si_el_prefijo_no_es_conocido(): void
    {
        $this->seed(PrefijoPaisSeeder::class);

        $this->assertNull(PrefijoPais::paraIndicativo('QQ1ABC'));
    }
}
