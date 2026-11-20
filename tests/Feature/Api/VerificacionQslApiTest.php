<?php

namespace Tests\Feature\Api;

use App\Enums\TipoQsl;
use App\Models\ConfirmacionQsl;
use App\Models\Contacto;
use App\Models\User;
use Database\Seeders\BandaSeeder;
use Database\Seeders\ModoSeeder;
use Database\Seeders\PrefijoPaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * «Consulta una QSL»: verificar sin cuenta un contacto con el número de QSO y el indicativo contactado.
 */
class VerificacionQslApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private Contacto $contacto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([BandaSeeder::class, ModoSeeder::class, PrefijoPaisSeeder::class]);
        $this->contacto = Contacto::factory()->for(User::factory()->create(['indicativo' => 'HC1QRC']))->create([
            'estacion_llamada' => 'LU4BB',
            'inicio_utc' => '2026-10-27 12:15:00',
        ]);
    }

    public function test_a_visitor_sees_the_qso_and_the_card_with_number_and_callsign(): void
    {
        ConfirmacionQsl::factory()->for($this->contacto)->recibida()->create(['tipo' => TipoQsl::Interna]);

        $respuesta = $this->getJson(route('api.qsl.verificar', ['qso' => $this->contacto->id, 'indicativo' => 'lu4 bb']))
            ->assertOk()
            ->assertJsonPath('data.de', 'HC1QRC')
            ->assertJsonPath('data.para', 'LU4BB')
            ->assertJsonPath('data.fecha', '2026-10-27')
            ->assertJsonPath('data.verificado', true)
            ->assertJsonPath('data.estado_qsl', 'confirmado');

        $this->assertStringContainsString('CONFIRMANDO QSO CON', $respuesta->json('data.svg'));
        $this->assertStringNotContainsString('data-linea', $respuesta->json('data.svg'));
    }

    public function test_the_wrong_callsign_answers_as_if_the_qso_did_not_exist(): void
    {
        $otro = $this->getJson(route('api.qsl.verificar', ['qso' => $this->contacto->id, 'indicativo' => 'EA8XYZ']));
        $inexistente = $this->getJson(route('api.qsl.verificar', ['qso' => 999999, 'indicativo' => 'LU4BB']));

        $otro->assertNotFound();
        $inexistente->assertNotFound();
        $this->assertSame($inexistente->json('message'), $otro->json('message'));
        $this->assertStringNotContainsString('HC1QRC', $otro->getContent());
    }

    public function test_both_fields_are_required(): void
    {
        $this->getJson(route('api.qsl.verificar'))->assertJsonValidationErrors(['qso', 'indicativo']);
        $this->getJson(route('api.qsl.verificar', ['qso' => 'abc', 'indicativo' => 'LU4BB']))
            ->assertJsonValidationErrors(['qso' => 'El número de QSO son solo dígitos (el que va después de «QSO #»).']);
    }

    public function test_guessing_is_limited(): void
    {
        for ($intento = 0; $intento < 15; $intento++) {
            $this->getJson(route('api.qsl.verificar', ['qso' => 999999, 'indicativo' => 'LU4BB']))->assertNotFound();
        }

        $this->getJson(route('api.qsl.verificar', ['qso' => $this->contacto->id, 'indicativo' => 'LU4BB']))->assertTooManyRequests();
    }
}
