<?php

namespace Tests\Feature\Api;

use App\Enums\TipoQsl;
use App\Models\ConfirmacionQsl;
use App\Models\Contacto;
use App\Models\User;
use Database\Seeders\BandaSeeder;
use Database\Seeders\ModoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConfirmacionQslApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private function contactoDe(User $operador): Contacto
    {
        $this->seed([BandaSeeder::class, ModoSeeder::class]);

        return Contacto::factory()->for($operador)->create(['inicio_utc' => '2026-10-27 12:15:00']);
    }

    public function test_el_detalle_del_contacto_trae_el_estado_qsl_por_medio(): void
    {
        $operador = User::factory()->create();
        $contacto = $this->contactoDe($operador);

        $this->actingAs($operador)
            ->getJson(route('api.contactos.show', $contacto))
            ->assertOk()
            ->assertJsonPath('data.estacion_llamada', $contacto->estacion_llamada)
            ->assertJsonPath('data.confirmaciones.tarjeta.enviada', false)
            ->assertJsonMissingPath('data.confirmaciones.interna');
    }

    public function test_marca_enviada_y_recibida_por_separado(): void
    {
        $operador = User::factory()->create();
        $contacto = $this->contactoDe($operador);

        $this->actingAs($operador)
            ->putJson(route('api.contactos.qsl', $contacto), [
                'qsl' => [
                    'tarjeta' => ['enviada' => '1', 'fecha_envio' => '2026-10-28'],
                    'lotw' => ['enviada' => '1', 'fecha_envio' => '2026-10-28', 'recibida' => '1', 'fecha_recepcion' => '2026-11-02'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.estado_qsl', 'confirmado')
            ->assertJsonPath('mensaje', 'Estado de QSL actualizado.');

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
            ->putJson(route('api.contactos.qsl', $contacto), [
                'qsl' => ['eqsl' => ['recibida' => '1', 'fecha_recepcion' => '2026-10-26']],
            ])
            ->assertJsonValidationErrors(['qsl.eqsl.fecha_recepcion' => 'La fecha de QSL no puede ser anterior a la del contacto (2026-10-27).']);

        $this->assertSame(0, $contacto->confirmacionesQsl()->count());
    }

    public function test_marcar_sin_fecha_pide_la_fecha(): void
    {
        $operador = User::factory()->create();
        $contacto = $this->contactoDe($operador);

        $this->actingAs($operador)
            ->putJson(route('api.contactos.qsl', $contacto), ['qsl' => ['tarjeta' => ['enviada' => '1']]])
            ->assertJsonValidationErrors(['qsl.tarjeta.fecha_envio' => 'Indica la fecha de envío.']);
    }

    public function test_desmarcar_todo_elimina_la_confirmacion_de_ese_medio(): void
    {
        $operador = User::factory()->create();
        $contacto = $this->contactoDe($operador);
        ConfirmacionQsl::factory()->for($contacto)->create(['tipo' => TipoQsl::Tarjeta]);

        $this->actingAs($operador)
            ->putJson(route('api.contactos.qsl', $contacto), ['qsl' => []])
            ->assertSuccessful();

        $this->assertSame(0, $contacto->confirmacionesQsl()->count());
    }

    public function test_no_puede_ver_ni_cambiar_la_qsl_de_contactos_ajenos(): void
    {
        $contactoAjeno = $this->contactoDe(User::factory()->create());
        $intruso = User::factory()->create();

        $this->actingAs($intruso)->getJson(route('api.contactos.show', $contactoAjeno))->assertNotFound();

        $this->actingAs($intruso)
            ->putJson(route('api.contactos.qsl', $contactoAjeno), [
                'qsl' => ['tarjeta' => ['enviada' => '1', 'fecha_envio' => '2026-10-28']],
            ])
            ->assertNotFound();

        $this->assertSame(0, $contactoAjeno->confirmacionesQsl()->count());
    }
}
