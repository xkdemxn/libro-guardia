<?php

namespace Tests\Feature\Api;

use App\Models\Banda;
use App\Models\ConfirmacionQsl;
use App\Models\Contacto;
use App\Models\Modo;
use App\Models\User;
use Database\Seeders\BandaSeeder;
use Database\Seeders\ModoSeeder;
use Database\Seeders\PrefijoPaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Estadísticas (US-16), mapa (innovación 2) y diplomas (innovación 4).
 */
class EstadisticasApiTest extends ApiTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([BandaSeeder::class, ModoSeeder::class, PrefijoPaisSeeder::class]);
    }

    public function test_the_figures_match_only_the_operators_own_log(): void
    {
        $operador = User::factory()->create(['indicativo' => 'HC1QRC', 'grid_locator' => 'FI09']);
        $argentino = $this->contacto($operador, 'LU4BB', '20 m', 'USB', '2026-10-01 10:00:00');
        $this->contacto($operador, 'LU5XYZ', '20 m', 'FT8', '2026-10-02 10:00:00');
        $this->contacto($operador, 'JA1ABC', '40 m', 'FT8', '2026-10-03 10:00:00');
        ConfirmacionQsl::factory()->for($argentino)->recibida()->create();
        Contacto::factory()->for(User::factory())->create();

        $respuesta = $this->actingAs($operador)->getJson(route('api.estadisticas'))->assertOk();

        $respuesta->assertJsonPath('totales', ['contactos' => 3, 'paises' => 2, 'confirmados' => 1, 'bandas' => 2])
            ->assertJsonPath('por_banda', [['nombre' => '20 m', 'cantidad' => 2], ['nombre' => '40 m', 'cantidad' => 1]])
            ->assertJsonPath('por_modo', [['nombre' => 'FT8', 'cantidad' => 2], ['nombre' => 'SSB · USB', 'cantidad' => 1]])
            ->assertJsonPath('dxcc', ['confirmados' => 1, 'meta' => 100])
            ->assertJsonPath('mas_lejano.indicativo', 'JA1ABC');
        $this->assertGreaterThan(13000, $respuesta->json('mas_lejano.km'));
    }

    public function test_it_counts_the_ecuadorian_zones_worked(): void
    {
        $operador = User::factory()->create(['indicativo' => 'HC5EV']);
        $this->contacto($operador, 'HC1QRC', '40 m', 'LSB', '2026-10-01 10:00:00');
        $this->contacto($operador, 'HD1AB', '40 m', 'LSB', '2026-10-02 10:00:00');
        $this->contacto($operador, 'HC8GR', '20 m', 'USB', '2026-10-03 10:00:00');
        $this->contacto($operador, 'LU4BB', '20 m', 'USB', '2026-10-04 10:00:00');

        $zonas = collect($this->actingAs($operador)->getJson(route('api.estadisticas'))->assertOk()->json('zonas'))->keyBy('numero');

        $this->assertSame(2, $zonas->where('contactos', '>', 0)->count());
        $this->assertSame(2, $zonas[1]['contactos']);
        $this->assertSame(1, $zonas[8]['contactos']);
        $this->assertTrue($zonas[5]['propia']);
    }

    public function test_the_date_range_filters_the_figures(): void
    {
        $operador = User::factory()->create();
        $this->contacto($operador, 'LU4BB', '20 m', 'USB', '2026-10-01 10:00:00');
        $this->contacto($operador, 'JA1ABC', '40 m', 'FT8', '2025-03-01 10:00:00');

        $this->actingAs($operador)
            ->getJson(route('api.estadisticas', ['desde' => '2026-01-01', 'hasta' => '2026-12-31']))
            ->assertJsonPath('totales.contactos', 1);
    }

    public function test_a_reversed_date_range_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('api.estadisticas', ['desde' => '2026-12-31', 'hasta' => '2026-01-01']))
            ->assertJsonValidationErrors(['hasta' => 'La fecha de inicio no puede ser mayor que la fecha de fin.']);
    }

    public function test_without_contacts_it_says_so(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('api.estadisticas'))
            ->assertOk()
            ->assertJsonPath('hay_contactos', false)
            ->assertJsonPath('puntos', []);
    }

    private function contacto(User $operador, string $estacion, string $banda, string $modo, string $fecha): Contacto
    {
        return Contacto::factory()->for($operador)->create([
            'estacion_llamada' => $estacion,
            'banda_id' => Banda::where('nombre', $banda)->value('id'),
            'modo_id' => Modo::where('nombre', $modo)->value('id'),
            'inicio_utc' => $fecha,
            'grid_locator' => null,
        ]);
    }
}
