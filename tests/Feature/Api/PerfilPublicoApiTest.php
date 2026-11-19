<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\PrefijoPaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Perfil público por indicativo (US-15): sin sesión y solo con lo que el operador hizo público.
 */
class PerfilPublicoApiTest extends ApiTestCase
{
    use RefreshDatabase;

    public function test_a_visitor_only_gets_what_the_operator_made_public(): void
    {
        $this->operadora();

        $respuesta = $this->getJson(route('api.perfil-publico.show', 'lu4 bb'))
            ->assertOk()
            ->assertJsonPath('data.indicativo', 'LU4BB')
            ->assertJsonPath('data.pais', 'Argentina')
            ->assertJsonPath('data.bandera', 'ar')
            ->assertJsonPath('data.nombre', 'Lucía Benítez')
            ->assertJsonPath('data.biografia', 'Me gusta el DX.')
            ->assertJsonPath('data.ciudad', null)
            ->assertJsonPath('data.equipo_radio', null)
            ->assertJsonPath('data.es_propio', false);

        $this->assertStringNotContainsString('lucia@example.com', $respuesta->getContent());
    }

    public function test_a_fully_private_profile_only_shows_callsign_and_country(): void
    {
        $operadora = $this->operadora();
        $operadora->visibilidad->update(['mostrar_nombre' => false, 'mostrar_biografia_foto' => false]);

        $this->getJson(route('api.perfil-publico.show', 'LU4BB'))
            ->assertOk()
            ->assertJsonPath('data.pais', 'Argentina')
            ->assertJsonPath('data.nombre', null)
            ->assertJsonPath('data.biografia', null);
    }

    public function test_the_owner_is_told_it_is_their_own_profile(): void
    {
        $operadora = $this->operadora();

        $this->actingAs($operadora)->getJson(route('api.perfil-publico.show', 'LU4BB'))->assertJsonPath('data.es_propio', true);
    }

    public function test_an_unregistered_callsign_is_reported_with_its_country(): void
    {
        $this->seed(PrefijoPaisSeeder::class);

        $this->getJson(route('api.perfil-publico.show', 'hc9zzz'))
            ->assertNotFound()
            ->assertJsonPath('buscado', 'HC9ZZZ')
            ->assertJsonPath('prefijo.pais', 'Ecuador');
    }

    private function operadora(): User
    {
        $this->seed(PrefijoPaisSeeder::class);

        $operadora = User::factory()->create([
            'indicativo' => 'LU4BB',
            'nombres' => 'Lucía',
            'apellidos' => 'Benítez',
            'email' => 'lucia@example.com',
            'pais' => 'Argentina',
            'ciudad' => 'Rosario',
            'equipo_radio' => 'Icom IC-7300',
            'biografia' => 'Me gusta el DX.',
        ]);
        $operadora->visibilidad()->create([
            'mostrar_nombre' => true,
            'mostrar_pais_ciudad' => false,
            'mostrar_equipo' => false,
            'mostrar_biografia_foto' => true,
        ]);

        return $operadora;
    }
}
