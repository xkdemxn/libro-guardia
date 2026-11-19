<?php

namespace Tests\Feature\Api;

use App\Enums\TipoQsl;
use App\Models\Banda;
use App\Models\Contacto;
use App\Models\Modo;
use App\Models\User;
use Database\Seeders\BandaSeeder;
use Database\Seeders\ModoSeeder;
use Database\Seeders\PrefijoPaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Solicitudes de confirmación: contactos que otra estación anotó conmigo y yo no tengo en mi libro.
 */
class SolicitudConfirmacionApiTest extends ApiTestCase
{
    use RefreshDatabase;

    /**
     * HC2QRC anotó un contacto con HC1QRC (que no lo tiene en su libro).
     *
     * @return array{0: User, 1: User, 2: Contacto}
     */
    private function solicitudDeHc2qrcParaHc1qrc(): array
    {
        $this->seed([BandaSeeder::class, ModoSeeder::class, PrefijoPaisSeeder::class]);
        $hc1qrc = User::factory()->create(['indicativo' => 'HC1QRC']);
        $hc2qrc = User::factory()->create(['indicativo' => 'HC2QRC', 'grid_locator' => 'FI07']);

        $contactoDeHc2qrc = Contacto::factory()->for($hc2qrc)->create([
            'estacion_llamada' => 'HC1QRC',
            'inicio_utc' => '2026-11-10 14:00:00',
            'fin_utc' => '2026-11-10 14:05:00',
            'frecuencia_mhz' => 14.225,
            'banda_id' => Banda::where('nombre', '20 m')->value('id'),
            'modo_id' => Modo::where('nombre', 'USB')->value('id'),
            'reporte_enviado' => '59',
            'reporte_recibido' => '57',
        ]);

        return [$hc1qrc, $hc2qrc, $contactoDeHc2qrc];
    }

    public function test_la_estacion_contactada_ve_la_solicitud_desde_su_lado_y_el_aviso_en_su_libro(): void
    {
        [$hc1qrc] = $this->solicitudDeHc2qrcParaHc1qrc();

        $this->actingAs($hc1qrc)->getJson(route('api.solicitudes.index'))
            ->assertOk()
            ->assertJsonPath('data.0.indicativo', 'HC2QRC')
            ->assertJsonPath('data.0.reporte_enviado', '57')
            ->assertJsonPath('data.0.reporte_recibido', '59');

        $this->actingAs($hc1qrc)->getJson(route('api.contactos.index'))
            ->assertJsonPath('resumen.solicitudes_pendientes', 1);
    }

    public function test_quien_registro_el_contacto_no_ve_su_propia_solicitud(): void
    {
        [, $hc2qrc] = $this->solicitudDeHc2qrcParaHc1qrc();

        $this->actingAs($hc2qrc)->getJson(route('api.solicitudes.index'))->assertJsonCount(0, 'data');
    }

    public function test_aceptar_agrega_el_contacto_espejo_y_confirma_a_los_dos(): void
    {
        [$hc1qrc, , $contactoDeHc2qrc] = $this->solicitudDeHc2qrcParaHc1qrc();

        $this->actingAs($hc1qrc)
            ->postJson(route('api.solicitudes.aceptar', $contactoDeHc2qrc))
            ->assertOk()
            ->assertJsonPath('mensaje', 'Contacto con HC2QRC agregado a tu libro y confirmado para los dos.');

        $miContacto = $hc1qrc->contactos()->sole();
        $this->assertSame('HC2QRC', $miContacto->estacion_llamada);
        $this->assertSame('2026-11-10 14:00:00', $miContacto->inicio_utc->format('Y-m-d H:i:s'));
        $this->assertSame('20 m', $miContacto->banda->nombre);
        $this->assertSame('57', $miContacto->reporte_enviado, 'Lo que HC2QRC recibió es lo que HC1QRC envió.');
        $this->assertSame('59', $miContacto->reporte_recibido);
        $this->assertSame('FI07', $miContacto->grid_locator);

        $this->assertTrue($miContacto->confirmacionesQsl()->where('tipo', TipoQsl::Interna)->exists());
        $this->assertTrue($contactoDeHc2qrc->confirmacionesQsl()->where('tipo', TipoQsl::Interna)->exists());
        $this->assertSame(0, $hc1qrc->solicitudesPendientes()->count());
    }

    public function test_rechazar_la_quita_de_pendientes_y_la_otra_estacion_lo_ve(): void
    {
        [$hc1qrc, $hc2qrc, $contactoDeHc2qrc] = $this->solicitudDeHc2qrcParaHc1qrc();

        $this->actingAs($hc1qrc)->postJson(route('api.solicitudes.rechazar', $contactoDeHc2qrc));

        $this->assertSame(0, $hc1qrc->solicitudesPendientes()->count());
        $this->assertSame(0, $hc1qrc->contactos()->count());

        $this->actingAs($hc2qrc)->getJson(route('api.contactos.show', $contactoDeHc2qrc))
            ->assertJsonPath('data.rechazado_por_contraparte', now()->toDateString());
    }

    public function test_si_quien_fue_rechazado_corrige_el_contacto_la_solicitud_vuelve_a_aparecer(): void
    {
        [$hc1qrc, $hc2qrc, $contactoDeHc2qrc] = $this->solicitudDeHc2qrcParaHc1qrc();
        $this->actingAs($hc1qrc)->postJson(route('api.solicitudes.rechazar', $contactoDeHc2qrc));

        $this->actingAs($hc2qrc)->putJson(route('api.contactos.update', $contactoDeHc2qrc), [
            'estacion_llamada' => 'HC1QRC',
            'fecha' => '2026-11-10',
            'hora_inicio' => '15:00',
            'frecuencia_mhz' => '14.225',
            'modo_id' => Modo::where('nombre', 'USB')->value('id'),
            'reporte_enviado' => '59',
            'reporte_recibido' => '57',
        ])->assertSuccessful();

        $this->assertSame(1, $hc1qrc->solicitudesPendientes()->count());
    }

    public function test_no_se_puede_responder_una_solicitud_ajena(): void
    {
        [, , $contactoDeHc2qrc] = $this->solicitudDeHc2qrcParaHc1qrc();
        $intruso = User::factory()->create(['indicativo' => 'HC5XYZ']);

        $this->actingAs($intruso)
            ->postJson(route('api.solicitudes.aceptar', $contactoDeHc2qrc))
            ->assertNotFound()
            ->assertJsonPath('message', 'La solicitud no existe o ya fue respondida.');

        $this->assertSame(0, $intruso->contactos()->count());
        $this->assertNull($contactoDeHc2qrc->refresh()->rechazado_por_contraparte_at);
    }
}
