<?php

namespace Tests\Feature;

use App\Enums\TipoQsl;
use App\Models\Contacto;
use App\Models\Modo;
use App\Models\User;
use Database\Seeders\BandaSeeder;
use Database\Seeders\ModoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Innovación 1: confirmación QSL automática cuando ambos operadores registran el mismo contacto.
 */
class ConfirmadorQslTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function qso(string $estacion, string $horaInicio, array $cambios = []): array
    {
        return [
            'estacion_llamada' => $estacion,
            'fecha' => '2026-10-27',
            'hora_inicio' => $horaInicio,
            'frecuencia_mhz' => '14.225',
            'modo_id' => Modo::where('nombre', 'USB')->value('id'),
            'reporte_enviado' => '59',
            'reporte_recibido' => '59',
            ...$cambios,
        ];
    }

    private function tieneConfirmacionInterna(Contacto $contacto): bool
    {
        return $contacto->confirmacionesQsl()->where('tipo', TipoQsl::Interna)->exists();
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function dosOperadores(): array
    {
        $this->seed([BandaSeeder::class, ModoSeeder::class]);

        return [
            User::factory()->create(['indicativo' => 'HC1QRC']),
            User::factory()->create(['indicativo' => 'LU4BB']),
        ];
    }

    public function test_cuando_ambos_registran_el_mismo_qso_los_dos_quedan_confirmados(): void
    {
        [$hc1qrc, $lu4bb] = $this->dosOperadores();

        $this->actingAs($hc1qrc)->post(route('contactos.store'), $this->qso('LU4BB', '12:15'));
        $this->actingAs($lu4bb)
            ->post(route('contactos.store'), $this->qso('HC1QRC', '12:17'))
            ->assertSessionHas('status', fn (string $mensaje): bool => str_contains($mensaje, 'quedó confirmado automáticamente'));

        $this->assertTrue($this->tieneConfirmacionInterna($hc1qrc->contactos()->sole()));
        $this->assertTrue($this->tieneConfirmacionInterna($lu4bb->contactos()->sole()));
        $this->assertSame('confirmado', $hc1qrc->contactos()->sole()->estadoQsl());
    }

    public function test_no_confirma_si_la_banda_es_distinta(): void
    {
        [$hc1qrc, $lu4bb] = $this->dosOperadores();

        $this->actingAs($hc1qrc)->post(route('contactos.store'), $this->qso('LU4BB', '12:15'));
        $this->actingAs($lu4bb)->post(route('contactos.store'), $this->qso('HC1QRC', '12:15', ['frecuencia_mhz' => '7.150', 'modo_id' => Modo::where('nombre', 'LSB')->value('id')]));

        $this->assertFalse($this->tieneConfirmacionInterna($hc1qrc->contactos()->sole()));
    }

    public function test_no_confirma_si_paso_mas_de_la_tolerancia(): void
    {
        [$hc1qrc, $lu4bb] = $this->dosOperadores();

        $this->actingAs($hc1qrc)->post(route('contactos.store'), $this->qso('LU4BB', '12:00'));
        $this->actingAs($lu4bb)->post(route('contactos.store'), $this->qso('HC1QRC', '12:30'));

        $this->assertFalse($this->tieneConfirmacionInterna($hc1qrc->contactos()->sole()));
    }

    public function test_si_un_operador_corrige_el_contacto_y_ya_no_coincide_se_quita_la_confirmacion(): void
    {
        [$hc1qrc, $lu4bb] = $this->dosOperadores();
        $this->actingAs($hc1qrc)->post(route('contactos.store'), $this->qso('LU4BB', '12:15'));
        $this->actingAs($lu4bb)->post(route('contactos.store'), $this->qso('HC1QRC', '12:15'));
        $contactoDeLu4bb = $lu4bb->contactos()->sole();

        $this->actingAs($lu4bb)->put(route('contactos.update', $contactoDeLu4bb), $this->qso('HC1QRC', '18:00'));

        $this->assertFalse($this->tieneConfirmacionInterna($contactoDeLu4bb));
        $this->assertFalse($this->tieneConfirmacionInterna($hc1qrc->contactos()->sole()));
    }

    public function test_al_eliminar_un_contacto_la_contraparte_deja_de_estar_confirmada(): void
    {
        [$hc1qrc, $lu4bb] = $this->dosOperadores();
        $this->actingAs($hc1qrc)->post(route('contactos.store'), $this->qso('LU4BB', '12:15'));
        $this->actingAs($lu4bb)->post(route('contactos.store'), $this->qso('HC1QRC', '12:15'));

        $this->actingAs($lu4bb)->delete(route('contactos.destroy', $lu4bb->contactos()->sole()));

        $this->assertFalse($this->tieneConfirmacionInterna($hc1qrc->contactos()->sole()));
    }
}
