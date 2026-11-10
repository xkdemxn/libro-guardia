<?php

namespace Tests\Feature;

use App\Enums\TipoQsl;
use App\Models\ConfirmacionQsl;
use App\Models\Contacto;
use App\Models\User;
use Database\Seeders\BandaSeeder;
use Database\Seeders\ModoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmacionQslTest extends TestCase
{
    use RefreshDatabase;

    private function contactoDe(User $operador): Contacto
    {
        $this->seed([BandaSeeder::class, ModoSeeder::class]);

        return Contacto::factory()->for($operador)->create(['inicio_utc' => '2026-10-27 12:15:00']);
    }

    public function test_el_detalle_del_contacto_muestra_el_panel_qsl(): void
    {
        $operador = User::factory()->create();
        $contacto = $this->contactoDe($operador);

        $this->actingAs($operador)
            ->get(route('contactos.show', $contacto))
            ->assertOk()
            ->assertSee($contacto->estacion_llamada)
            ->assertSee('Confirmación QSL', false);
    }

    public function test_marca_enviada_y_recibida_por_separado(): void
    {
        $operador = User::factory()->create();
        $contacto = $this->contactoDe($operador);

        $this->actingAs($operador)
            ->put(route('contactos.qsl.update', $contacto), [
                'qsl' => [
                    'tarjeta' => ['enviada' => '1', 'fecha_envio' => '2026-10-28'],
                    'lotw' => ['enviada' => '1', 'fecha_envio' => '2026-10-28', 'recibida' => '1', 'fecha_recepcion' => '2026-11-02'],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contactos.show', $contacto));

        $tarjeta = $contacto->confirmacionesQsl()->where('tipo', TipoQsl::Tarjeta)->sole();
        $this->assertTrue($tarjeta->enviada);
        $this->assertFalse($tarjeta->recibida);
        $this->assertNull($tarjeta->fecha_recepcion);

        $lotw = $contacto->confirmacionesQsl()->where('tipo', TipoQsl::Lotw)->sole();
        $this->assertSame('2026-11-02', $lotw->fecha_recepcion->toDateString());
        $this->assertSame('confirmado', $contacto->refresh()->estadoQsl());
    }

    public function test_la_fecha_de_qsl_no_puede_ser_anterior_al_contacto(): void
    {
        $operador = User::factory()->create();
        $contacto = $this->contactoDe($operador);

        $this->actingAs($operador)
            ->put(route('contactos.qsl.update', $contacto), [
                'qsl' => ['eqsl' => ['recibida' => '1', 'fecha_recepcion' => '2026-10-26']],
            ])
            ->assertSessionHasErrors(['qsl.eqsl.fecha_recepcion' => 'La fecha de QSL no puede ser anterior a la del contacto (2026-10-27).']);

        $this->assertSame(0, $contacto->confirmacionesQsl()->count());
    }

    public function test_marcar_sin_fecha_pide_la_fecha(): void
    {
        $operador = User::factory()->create();
        $contacto = $this->contactoDe($operador);

        $this->actingAs($operador)
            ->put(route('contactos.qsl.update', $contacto), ['qsl' => ['tarjeta' => ['enviada' => '1']]])
            ->assertSessionHasErrors(['qsl.tarjeta.fecha_envio' => 'Indica la fecha de envío.']);
    }

    public function test_desmarcar_todo_elimina_la_confirmacion_de_ese_medio(): void
    {
        $operador = User::factory()->create();
        $contacto = $this->contactoDe($operador);
        ConfirmacionQsl::factory()->for($contacto)->create(['tipo' => TipoQsl::Tarjeta]);

        $this->actingAs($operador)
            ->put(route('contactos.qsl.update', $contacto), ['qsl' => []])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $contacto->confirmacionesQsl()->count());
    }

    public function test_no_puede_ver_ni_cambiar_la_qsl_de_contactos_ajenos(): void
    {
        $contactoAjeno = $this->contactoDe(User::factory()->create());
        $intruso = User::factory()->create();

        $this->actingAs($intruso)->get(route('contactos.show', $contactoAjeno))
            ->assertRedirect(route('contactos.index'))
            ->assertSessionHas('error');

        $this->actingAs($intruso)
            ->put(route('contactos.qsl.update', $contactoAjeno), [
                'qsl' => ['tarjeta' => ['enviada' => '1', 'fecha_envio' => '2026-10-28']],
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, $contactoAjeno->confirmacionesQsl()->count());
    }
}
