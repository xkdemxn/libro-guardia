<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PrefijoPaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfilPublicoTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_un_visitante_sin_sesion_ve_solo_lo_que_el_operador_hizo_publico(): void
    {
        $this->operadora();

        $this->get(route('perfil-publico.show', 'lu4bb'))
            ->assertOk()
            ->assertSee('LU4BB')
            ->assertSee('Argentina')
            ->assertSee('Lucía Benítez')
            ->assertSee('Me gusta el DX.')
            ->assertDontSee('Rosario')
            ->assertDontSee('Icom IC-7300')
            ->assertDontSee('lucia@example.com');
    }

    public function test_un_perfil_totalmente_privado_muestra_solo_indicativo_y_pais(): void
    {
        $operadora = $this->operadora();
        $operadora->visibilidad->update(['mostrar_nombre' => false, 'mostrar_biografia_foto' => false]);

        $this->get(route('perfil-publico.show', 'LU4BB'))
            ->assertSee('LU4BB')
            ->assertSee('Argentina')
            ->assertDontSee('Lucía')
            ->assertDontSee('Me gusta el DX.');
    }

    public function test_un_indicativo_no_registrado_lo_dice_claramente(): void
    {
        $this->seed(PrefijoPaisSeeder::class);

        $this->get(route('perfil-publico.show', 'HC9ZZZ'))
            ->assertOk()
            ->assertSee('Este indicativo no está registrado.')
            ->assertSee('Ecuador');
    }

    public function test_el_buscador_lleva_al_perfil_normalizando_el_indicativo(): void
    {
        $this->get(route('perfil-publico.index', ['indicativo' => 'lu4 bb']))
            ->assertRedirect(route('perfil-publico.show', 'LU4BB'));
    }
}
