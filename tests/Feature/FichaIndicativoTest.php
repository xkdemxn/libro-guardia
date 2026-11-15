<?php

namespace Tests\Feature;

use App\Models\Contacto;
use App\Models\User;
use Database\Seeders\BandaSeeder;
use Database\Seeders\ModoSeeder;
use Database\Seeders\PrefijoPaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Innovación 3: ficha rápida al escribir un indicativo.
 */
class FichaIndicativoTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_pais_y_contactos_previos_solo_del_propio_libro(): void
    {
        $this->seed([BandaSeeder::class, ModoSeeder::class, PrefijoPaisSeeder::class]);
        $operador = User::factory()->create();
        Contacto::factory()->count(2)->for($operador)->create(['estacion_llamada' => 'LU4BB']);
        Contacto::factory()->for(User::factory())->create(['estacion_llamada' => 'LU4BB']);

        $this->actingAs($operador)
            ->getJson(route('contactos.ficha', ['indicativo' => 'lu4 bb']))
            ->assertOk()
            ->assertJson([
                'valido' => true,
                'indicativo' => 'LU4BB',
                'pais' => 'Argentina',
                'bandera' => asset('banderas/ar.svg'),
                'contactos_previos' => 2,
                'registrado' => false,
            ]);
    }

    public function test_de_un_operador_registrado_solo_muestra_sus_datos_publicos(): void
    {
        $this->seed(PrefijoPaisSeeder::class);
        $operadora = User::factory()->create(['indicativo' => 'LU4BB', 'nombres' => 'Lucía', 'ciudad' => 'Rosario', 'email' => 'lucia@example.com']);
        $operadora->visibilidad()->create(['mostrar_nombre' => false, 'mostrar_pais_ciudad' => true]);

        $respuesta = $this->actingAs(User::factory()->create())
            ->getJson(route('contactos.ficha', ['indicativo' => 'LU4BB']))
            ->assertJsonPath('registrado', true)
            ->assertJsonPath('perfil.nombre', null)
            ->assertJsonPath('perfil.ciudad', 'Rosario');

        $this->assertStringNotContainsString('lucia@example.com', $respuesta->getContent());
    }

    public function test_un_indicativo_ecuatoriano_incluye_su_zona(): void
    {
        $this->seed(PrefijoPaisSeeder::class);

        $this->actingAs(User::factory()->create())
            ->getJson(route('contactos.ficha', ['indicativo' => 'HC5EV']))
            ->assertJson(['pais' => 'Ecuador', 'zona' => 'Zona 5 · Azuay, Cañar, Chimborazo']);
    }

    public function test_un_indicativo_invalido_no_devuelve_ficha(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('contactos.ficha', ['indicativo' => 'HOLA']))
            ->assertExactJson(['valido' => false]);
    }

    public function test_requiere_sesion(): void
    {
        $this->getJson(route('contactos.ficha', ['indicativo' => 'LU4BB']))->assertUnauthorized();
    }
}
