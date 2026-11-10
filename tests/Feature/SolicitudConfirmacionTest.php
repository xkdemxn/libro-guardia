<?php

namespace Tests\Feature;

use App\Enums\TipoQsl;
use App\Models\Banda;
use App\Models\Contacto;
use App\Models\Modo;
use App\Models\User;
use Database\Seeders\BandaSeeder;
use Database\Seeders\ModoSeeder;
use Database\Seeders\PrefijoPaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Solicitudes de confirmación: contactos que otra estación anotó conmigo y yo no tengo en mi libro.
 */
class SolicitudConfirmacionTest extends TestCase
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

    public function test_la_estacion_contactada_ve_la_solicitud_y_el_aviso_en_su_libro(): void
    {
        [$hc1qrc] = $this->solicitudDeHc2qrcParaHc1qrc();

        $this->actingAs($hc1qrc)->get(route('solicitudes.index'))
            ->assertOk()
            ->assertSee('HC2QRC')
            ->assertSee('dice que habló contigo');

        $this->actingAs($hc1qrc)->get(route('contactos.index'))
            ->assertSee('Otra estación registró un contacto contigo que no está en tu libro.');
    }

    public function test_quien_registro_el_contacto_no_ve_su_propia_solicitud(): void
    {
        [, $hc2qrc] = $this->solicitudDeHc2qrcParaHc1qrc();

        $this->actingAs($hc2qrc)->get(route('solicitudes.index'))
            ->assertSee('No tienes solicitudes pendientes.');
    }

    public function test_aceptar_agrega_el_contacto_espejo_y_confirma_a_los_dos(): void
    {
        [$hc1qrc, , $contactoDeHc2qrc] = $this->solicitudDeHc2qrcParaHc1qrc();

        $this->actingAs($hc1qrc)
            ->post(route('solicitudes.aceptar', $contactoDeHc2qrc))
            ->assertRedirect(route('solicitudes.index'))
            ->assertSessionHas('status', 'Contacto con HC2QRC agregado a tu libro y confirmado para los dos.');

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

        $this->actingAs($hc1qrc)->post(route('solicitudes.rechazar', $contactoDeHc2qrc));

        $this->assertSame(0, $hc1qrc->solicitudesPendientes()->count());
        $this->assertSame(0, $hc1qrc->contactos()->count());

        $this->actingAs($hc2qrc)->get(route('contactos.show', $contactoDeHc2qrc))
            ->assertSee('HC1QRC indicó el', false)
            ->assertSee('que no reconoce este contacto');
    }

    public function test_si_quien_fue_rechazado_corrige_el_contacto_la_solicitud_vuelve_a_aparecer(): void
    {
        [$hc1qrc, $hc2qrc, $contactoDeHc2qrc] = $this->solicitudDeHc2qrcParaHc1qrc();
        $this->actingAs($hc1qrc)->post(route('solicitudes.rechazar', $contactoDeHc2qrc));

        $this->actingAs($hc2qrc)->put(route('contactos.update', $contactoDeHc2qrc), [
            'estacion_llamada' => 'HC1QRC',
            'fecha' => '2026-11-10',
            'hora_inicio' => '15:00',
            'frecuencia_mhz' => '14.225',
            'modo_id' => Modo::where('nombre', 'USB')->value('id'),
            'reporte_enviado' => '59',
            'reporte_recibido' => '57',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $hc1qrc->solicitudesPendientes()->count());
    }

    public function test_no_se_puede_responder_una_solicitud_ajena(): void
    {
        [, , $contactoDeHc2qrc] = $this->solicitudDeHc2qrcParaHc1qrc();
        $intruso = User::factory()->create(['indicativo' => 'HC5XYZ']);

        $this->actingAs($intruso)
            ->post(route('solicitudes.aceptar', $contactoDeHc2qrc))
            ->assertSessionHas('error', 'La solicitud no existe o ya fue respondida.');

        $this->assertSame(0, $intruso->contactos()->count());
        $this->assertNull($contactoDeHc2qrc->refresh()->rechazado_por_contraparte_at);
    }
}
