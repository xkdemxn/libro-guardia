<?php

namespace Tests\Feature;

use App\Models\Banda;
use App\Models\ConfirmacionQsl;
use App\Models\Contacto;
use App\Models\Modo;
use App\Models\User;
use Database\Seeders\BandaSeeder;
use Database\Seeders\ModoSeeder;
use Database\Seeders\PrefijoPaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstadisticasTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_las_cifras_coinciden_con_los_contactos_del_libro(): void
    {
        $this->seed([BandaSeeder::class, ModoSeeder::class, PrefijoPaisSeeder::class]);
        $operador = User::factory()->create(['indicativo' => 'HC1QRC', 'grid_locator' => 'FI09']);
        $argentino = $this->contacto($operador, 'LU4BB', '20 m', 'USB', '2026-10-01 10:00:00');
        $this->contacto($operador, 'LU5XYZ', '20 m', 'FT8', '2026-10-02 10:00:00');
        $this->contacto($operador, 'JA1ABC', '40 m', 'FT8', '2026-10-03 10:00:00');
        ConfirmacionQsl::factory()->for($argentino)->recibida()->create();
        Contacto::factory()->for(User::factory())->create();

        $this->actingAs($operador)
            ->get(route('estadisticas'))
            ->assertOk()
            ->assertViewHas('totales', ['contactos' => 3, 'paises' => 2, 'confirmados' => 1, 'bandas' => 2])
            ->assertViewHas('porBanda', fn ($porBanda): bool => $porBanda->all() === ['20 m' => 2, '40 m' => 1])
            ->assertViewHas('porModo', fn ($porModo): bool => $porModo->all() === ['FT8' => 2, 'SSB · USB' => 1])
            ->assertViewHas('dxcc', ['confirmados' => 1, 'meta' => 100])
            ->assertViewHas('masLejano', fn (array $lejano): bool => $lejano['indicativo'] === 'JA1ABC' && $lejano['km'] > 13000);
    }

    public function test_cuenta_las_zonas_de_ecuador_trabajadas(): void
    {
        $this->seed([BandaSeeder::class, ModoSeeder::class, PrefijoPaisSeeder::class]);
        $operador = User::factory()->create(['indicativo' => 'HC5EV']);
        $this->contacto($operador, 'HC1QRC', '40 m', 'LSB', '2026-10-01 10:00:00');
        $this->contacto($operador, 'HD1AB', '40 m', 'LSB', '2026-10-02 10:00:00');
        $this->contacto($operador, 'HC8GR', '20 m', 'USB', '2026-10-03 10:00:00');
        $this->contacto($operador, 'LU4BB', '20 m', 'USB', '2026-10-04 10:00:00');

        $this->actingAs($operador)
            ->get(route('estadisticas'))
            ->assertOk()
            ->assertSee('/ 9 zonas trabajadas')
            ->assertViewHas('zonas', function ($zonas): bool {
                $porNumero = $zonas->keyBy('numero');

                return $zonas->where('contactos', '>', 0)->count() === 2
                    && $porNumero[1]['contactos'] === 2
                    && $porNumero[8]['contactos'] === 1
                    && $porNumero[5]['propia'] === true;
            });
    }

    public function test_el_rango_de_fechas_filtra_las_cifras(): void
    {
        $this->seed([BandaSeeder::class, ModoSeeder::class, PrefijoPaisSeeder::class]);
        $operador = User::factory()->create();
        $this->contacto($operador, 'LU4BB', '20 m', 'USB', '2026-10-01 10:00:00');
        $this->contacto($operador, 'JA1ABC', '40 m', 'FT8', '2025-03-01 10:00:00');

        $this->actingAs($operador)
            ->get(route('estadisticas', ['desde' => '2026-01-01', 'hasta' => '2026-12-31']))
            ->assertViewHas('totales', fn (array $totales): bool => $totales['contactos'] === 1);
    }

    public function test_sin_contactos_en_el_rango_muestra_un_aviso_y_no_graficos_vacios(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('estadisticas'))
            ->assertOk()
            ->assertSee('No hay contactos en este rango de fechas.')
            ->assertDontSee('mapa-contactos');
    }
}
