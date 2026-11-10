<?php

namespace Tests\Feature;

use App\Enums\TipoQsl;
use App\Models\Banda;
use App\Models\ConfirmacionQsl;
use App\Models\Contacto;
use App\Models\Modo;
use App\Models\User;
use Database\Seeders\BandaSeeder;
use Database\Seeders\ModoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TarjetaQslTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Contacto}
     */
    private function contactoConTarjeta(): array
    {
        $this->seed([BandaSeeder::class, ModoSeeder::class]);
        $operador = User::factory()->create([
            'indicativo' => 'HC1QRC',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'ciudad' => 'Quito',
            'pais' => 'Ecuador',
            'grid_locator' => 'FI09',
        ]);
        $contacto = Contacto::factory()->for($operador)->create([
            'estacion_llamada' => 'LU4BB',
            'inicio_utc' => '2026-10-27 12:15:00',
            'frecuencia_mhz' => 14.225,
            'banda_id' => Banda::where('nombre', '20 m')->value('id'),
            'modo_id' => Modo::where('nombre', 'USB')->value('id'),
            'reporte_enviado' => '59',
        ]);

        return [$operador, $contacto];
    }

    public function test_la_tarjeta_muestra_los_datos_del_contacto_y_del_operador(): void
    {
        [$operador, $contacto] = $this->contactoConTarjeta();

        $this->actingAs($operador)
            ->get(route('contactos.tarjeta', $contacto))
            ->assertOk()
            ->assertSeeInOrder(['HC1QRC', 'Ana Pérez', 'Quito, Ecuador', 'Grid FI09', 'PARA LA ESTACIÓN', 'LU4BB'])
            ->assertSee('27 OCT 2026')
            ->assertSee('12:15')
            ->assertSee('14.225')
            ->assertSee('20 m')
            ->assertSee('PSE QSL TNX')
            ->assertDontSee('Verificado en Libro de Guardia');
    }

    public function test_si_esta_confirmado_automaticamente_lo_indica(): void
    {
        [$operador, $contacto] = $this->contactoConTarjeta();
        ConfirmacionQsl::factory()->for($contacto)->recibida()->create(['tipo' => TipoQsl::Interna]);

        $this->actingAs($operador)
            ->get(route('contactos.tarjeta', $contacto))
            ->assertSee('Verificado en Libro de Guardia')
            ->assertSee('TNX QSL');
    }

    public function test_se_descarga_como_svg(): void
    {
        [$operador, $contacto] = $this->contactoConTarjeta();

        $respuesta = $this->actingAs($operador)
            ->get(route('contactos.tarjeta.svg', $contacto))
            ->assertOk()
            ->assertDownload('qsl-HC1QRC-LU4BB-20261027-1215.svg')
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $respuesta->getContent());
        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg"', $respuesta->getContent());
    }

    public function test_los_textos_se_escapan_para_no_romper_el_svg(): void
    {
        [$operador, $contacto] = $this->contactoConTarjeta();
        $operador->update(['equipo_radio' => 'Yaesu <FT-991A> & "amplificador"']);

        $svg = $this->actingAs($operador)->get(route('contactos.tarjeta.svg', $contacto))->getContent();

        $this->assertStringContainsString('Yaesu &lt;FT-991A&gt; &amp;', $svg);
        $this->assertNotFalse(simplexml_load_string($svg), 'El SVG debe ser XML válido.');
    }

    public function test_no_se_puede_ver_la_tarjeta_de_un_contacto_ajeno(): void
    {
        [, $contacto] = $this->contactoConTarjeta();

        $this->actingAs(User::factory()->create())
            ->get(route('contactos.tarjeta.svg', $contacto))
            ->assertRedirect(route('contactos.index'))
            ->assertSessionHas('error');
    }
}
