<?php

namespace Tests\Feature\Api;

use App\Enums\TipoQsl;
use App\Models\Banda;
use App\Models\ConfirmacionQsl;
use App\Models\Contacto;
use App\Models\Modo;
use App\Models\User;
use Database\Seeders\BandaSeeder;
use Database\Seeders\ModoSeeder;
use Database\Seeders\PrefijoPaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExportacionApiTest extends ApiTestCase
{
    use RefreshDatabase;

    /**
     * El contacto de ejemplo de la captura de WhatsApp: HC1QRC en 14.225 USB, 59/59.
     */
    private function contactoDeEjemplo(User $operador): Contacto
    {
        $this->seed([BandaSeeder::class, ModoSeeder::class, PrefijoPaisSeeder::class]);

        $contacto = Contacto::factory()->for($operador)->create([
            'estacion_llamada' => 'LU4BB',
            'inicio_utc' => '2026-10-27 12:15:00',
            'fin_utc' => '2026-10-27 12:20:00',
            'frecuencia_mhz' => 14.225,
            'banda_id' => Banda::where('nombre', '20 m')->value('id'),
            'modo_id' => Modo::where('nombre', 'USB')->value('id'),
            'reporte_enviado' => '59',
            'reporte_recibido' => '59',
            'potencia_w' => 100,
            'nombre_operador' => 'José Núñez',
            'activacion' => 'HC-0001',
        ]);
        ConfirmacionQsl::factory()->for($contacto)->recibida()->create([
            'tipo' => TipoQsl::Lotw,
            'fecha_envio' => '2026-10-28',
            'fecha_recepcion' => '2026-11-02',
        ]);

        return $contacto;
    }

    public function test_exporta_en_adif_con_los_campos_del_estandar(): void
    {
        $operador = User::factory()->create(['indicativo' => 'HC1QRC']);
        $this->contactoDeEjemplo($operador);

        $respuesta = $this->actingAs($operador)
            ->postJson(route('api.contactos.exportar'), ['formato' => 'adif'])
            ->assertOk()
            ->assertDownload();

        $adif = $respuesta->getContent();
        $this->assertStringContainsString('<ADIF_VER:5>3.1.4', $adif);
        $this->assertStringContainsString('<EOH>', $adif);
        $this->assertStringContainsString('<STATION_CALLSIGN:6>HC1QRC', $adif);
        $this->assertStringContainsString('<CALL:5>LU4BB', $adif);
        $this->assertStringContainsString('<QSO_DATE:8>20261027', $adif);
        $this->assertStringContainsString('<TIME_ON:6>121500', $adif);
        $this->assertStringContainsString('<TIME_OFF:6>122000', $adif);
        $this->assertStringContainsString('<FREQ:6>14.225', $adif);
        $this->assertStringContainsString('<BAND:3>20m', $adif);
        $this->assertStringContainsString('<MODE:3>SSB <SUBMODE:3>USB', $adif);
        $this->assertStringContainsString('<RST_SENT:2>59', $adif);
        $this->assertStringContainsString('<TX_PWR:3>100', $adif);
        $this->assertStringContainsString('<COUNTRY:9>Argentina', $adif);
        $this->assertStringContainsString('<POTA_REF:7>HC-0001', $adif);
        $this->assertStringContainsString('<LOTW_QSL_RCVD:1>Y', $adif);
        $this->assertStringContainsString('<LOTW_QSLRDATE:8>20261102', $adif);
        $this->assertSame(1, substr_count($adif, '<EOR>'));
    }

    public function test_los_textos_con_tildes_se_exportan_en_ascii_con_el_largo_correcto(): void
    {
        $operador = User::factory()->create();
        $this->contactoDeEjemplo($operador);

        $adif = $this->actingAs($operador)->postJson(route('api.contactos.exportar'), ['formato' => 'adif'])->getContent();

        $this->assertStringContainsString('<NAME:10>Jose Nunez', $adif);
    }

    public function test_exporta_en_csv_legible_por_hojas_de_calculo(): void
    {
        $operador = User::factory()->create();
        $this->contactoDeEjemplo($operador);

        $respuesta = $this->actingAs($operador)
            ->postJson(route('api.contactos.exportar'), ['formato' => 'csv'])
            ->assertOk()
            ->assertDownload();

        $csv = $respuesta->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lineas = array_values(array_filter(explode("\n", substr($csv, 3))));
        $this->assertCount(2, $lineas);
        $this->assertStringStartsWith('"Fecha (UTC)"', $lineas[0]);
        $this->assertStringContainsString('2026-10-27,12:15,12:20,LU4BB,Argentina,14.225,"20 m",USB,59,59', $lineas[1]);
        $this->assertStringContainsString('Confirmado', $lineas[1]);
    }

    public function test_genera_el_reporte_en_pdf(): void
    {
        $operador = User::factory()->create(['indicativo' => 'HC1QRC']);
        $this->contactoDeEjemplo($operador);

        $respuesta = $this->actingAs($operador)
            ->postJson(route('api.contactos.exportar'), ['formato' => 'pdf'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
        $this->assertMatchesRegularExpression('/filename="libro-HC1QRC-\d{8}-\d{4}\.pdf"/', $respuesta->headers->get('Content-Disposition'));
    }

    public function test_respeta_el_rango_de_fechas(): void
    {
        $operador = User::factory()->create();
        $this->contactoDeEjemplo($operador);
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'HC2VIEJO', 'inicio_utc' => '2025-01-10 08:00:00']);

        $adif = $this->actingAs($operador)
            ->postJson(route('api.contactos.exportar'), ['formato' => 'adif', 'desde' => '2026-01-01', 'hasta' => '2026-12-31'])
            ->getContent();

        $this->assertStringContainsString('LU4BB', $adif);
        $this->assertStringNotContainsString('HC2VIEJO', $adif);
    }

    public function test_solo_exporta_contactos_del_operador(): void
    {
        $operador = User::factory()->create();
        $this->contactoDeEjemplo($operador);
        Contacto::factory()->for(User::factory())->create(['estacion_llamada' => 'HC3AJENO']);

        $csv = $this->actingAs($operador)->postJson(route('api.contactos.exportar'), ['formato' => 'csv'])->getContent();

        $this->assertStringNotContainsString('HC3AJENO', $csv);
    }

    public function test_sin_contactos_muestra_un_aviso_en_lugar_de_un_archivo_vacio(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('api.contactos.exportar'), ['formato' => 'csv'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No hay contactos para exportar con esos filtros.');
    }

    public function test_el_formato_es_obligatorio(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('api.contactos.exportar'), ['formato' => 'xlsx'])
            ->assertJsonValidationErrors('formato');
    }
}
