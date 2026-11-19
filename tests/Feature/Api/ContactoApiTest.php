<?php

namespace Tests\Feature\Api;

use App\Models\Banda;
use App\Models\ConfirmacionQsl;
use App\Models\Contacto;
use App\Models\Modo;
use App\Models\User;
use Database\Seeders\BandaSeeder;
use Database\Seeders\ModoSeeder;
use Database\Seeders\PrefijoPaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;

class ContactoApiTest extends ApiTestCase
{
    use RefreshDatabase;

    /**
     * Los datos de ejemplo son del 27 de octubre: el contacto se registra el mismo día (UTC).
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now('UTC')->setDate(2026, 10, 27)->setTime(20, 0));
    }

    private function sembrarCatalogos(): void
    {
        $this->seed([BandaSeeder::class, ModoSeeder::class, PrefijoPaisSeeder::class]);
    }

    private function modo(string $nombre): Modo
    {
        return Modo::where('nombre', $nombre)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datosValidos(array $cambios = []): array
    {
        return [
            'estacion_llamada' => 'lu4 bb',
            'fecha' => '2026-10-27',
            'hora_inicio' => '12:15',
            'hora_fin' => '12:20',
            'frecuencia_mhz' => '14.225',
            'modo_id' => $this->modo('USB')->id,
            'reporte_enviado' => '59',
            'reporte_recibido' => '57',
            'potencia_w' => '100',
            ...$cambios,
        ];
    }

    public function test_without_a_session_the_log_is_unauthorized(): void
    {
        $this->getJson(route('api.contactos.index'))->assertUnauthorized();
        $this->postJson(route('api.contactos.store'), [])->assertUnauthorized();
    }

    public function test_registra_un_contacto_con_hora_utc_banda_y_pais_calculados(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();

        $this->actingAs($operador)
            ->postJson(route('api.contactos.store'), $this->datosValidos())
            ->assertCreated()
            ->assertJsonPath('data.banda.nombre', '20 m')
            ->assertJsonPath('mensaje', 'Contacto con LU4BB guardado.');

        $contacto = $operador->contactos()->sole();
        $this->assertSame('LU4BB', $contacto->estacion_llamada);
        $this->assertSame('2026-10-27 12:15:00', $contacto->inicio_utc->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-27 12:20:00', $contacto->fin_utc->format('Y-m-d H:i:s'));
        $this->assertSame('20 m', $contacto->banda->nombre);
        $this->assertSame('Argentina', $contacto->prefijoPais->pais);
    }

    public function test_el_contacto_registrado_aparece_en_el_listado_con_el_resumen(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'HC2ABC']);

        $this->actingAs($operador)
            ->getJson(route('api.contactos.index'))
            ->assertOk()
            ->assertJsonPath('data.0.estacion_llamada', 'HC2ABC')
            ->assertJsonPath('resumen.contactos', 1)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_campos_obligatorios_vacios_se_rechazan(): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.contactos.store'), [])
            ->assertJsonValidationErrors(['estacion_llamada', 'fecha', 'hora_inicio', 'frecuencia_mhz', 'modo_id', 'reporte_enviado', 'reporte_recibido']);

        $this->assertDatabaseCount('contactos', 0);
    }

    public function test_rechaza_un_indicativo_invalido(): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.contactos.store'), $this->datosValidos(['estacion_llamada' => 'HOLA']))
            ->assertJsonValidationErrors(['estacion_llamada' => 'El indicativo no es un indicativo válido (ej. HC1QRC).']);
    }

    #[TestWith(['15.000'], 'entre bandas')]
    #[TestWith(['abc'], 'no numérica')]
    public function test_rechaza_frecuencias_fuera_de_las_bandas_o_no_numericas(string $frecuencia): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.contactos.store'), $this->datosValidos(['frecuencia_mhz' => $frecuencia]))
            ->assertJsonValidationErrors('frecuencia_mhz');

        $this->assertDatabaseCount('contactos', 0);
    }

    public function test_el_mensaje_de_frecuencia_fuera_de_banda_es_claro(): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.contactos.store'), $this->datosValidos(['frecuencia_mhz' => '15.000']))
            ->assertJsonValidationErrors(['frecuencia_mhz' => 'La frecuencia está fuera de las bandas de radioaficionados.']);
    }

    #[TestWith(['USB', '599', 'En este modo el reporte es RS (ej. 59).'])]
    #[TestWith(['CW', '59', 'En este modo el reporte es RST (ej. 599).'])]
    #[TestWith(['FT8', '59', 'En este modo el reporte es dB (ej. -12).'])]
    public function test_el_reporte_debe_corresponder_al_modo(string $modo, string $reporte, string $mensaje): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.contactos.store'), $this->datosValidos([
                'modo_id' => $this->modo($modo)->id,
                'reporte_enviado' => $reporte,
            ]))
            ->assertJsonValidationErrors(['reporte_enviado' => $mensaje]);
    }

    public function test_acepta_reportes_en_db_para_ft8(): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.contactos.store'), $this->datosValidos([
                'frecuencia_mhz' => '7.074',
                'modo_id' => $this->modo('FT8')->id,
                'reporte_enviado' => '-12',
                'reporte_recibido' => '+3',
            ]))
            ->assertSuccessful();

        $this->assertDatabaseHas('contactos', ['reporte_enviado' => '-12', 'reporte_recibido' => '+3']);
    }

    public function test_los_catalogos_traen_bandas_modos_y_la_fecha_utc_de_hoy(): void
    {
        $this->sembrarCatalogos();
        $this->travelTo(now('UTC')->setDate(2026, 11, 14)->setTime(15, 42));

        $this->actingAs(User::factory()->create())
            ->getJson(route('api.catalogos'))
            ->assertOk()
            ->assertJsonPath('hoy_utc', '2026-11-14')
            ->assertJsonFragment(['nombre' => '20 m', 'rango' => 'HF'])
            ->assertJsonFragment(['nombre' => 'FT8', 'tipo' => 'dB', 'ejemplo' => '-12']);
    }

    public function test_no_acepta_una_fecha_futura(): void
    {
        $this->sembrarCatalogos();
        $this->travelTo(now('UTC')->setDate(2026, 11, 14)->setTime(12, 0));

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.contactos.store'), $this->datosValidos(['fecha' => '2026-11-15']))
            ->assertJsonValidationErrors(['fecha' => 'La fecha no puede ser posterior a hoy (2026-11-14 UTC).']);
    }

    public function test_no_acepta_una_fecha_pasada_al_registrar(): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.contactos.store'), $this->datosValidos(['fecha' => '2026-10-26']))
            ->assertJsonValidationErrors(['fecha' => 'La fecha no puede ser anterior a hoy (2026-10-27 UTC).']);
    }

    public function test_al_editar_conserva_la_fecha_original_aunque_sea_pasada(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $this->actingAs($operador)->postJson(route('api.contactos.store'), $this->datosValidos());
        $contacto = $operador->contactos()->sole();

        $this->travelTo(now('UTC')->setDate(2026, 11, 3));

        $this->getJson(route('api.contactos.show', $contacto))->assertJsonPath('data.fecha_minima', '2026-10-27');
        $this->putJson(route('api.contactos.update', $contacto), $this->datosValidos(['observaciones' => 'Corregido']))
            ->assertSuccessful();
        $this->putJson(route('api.contactos.update', $contacto), $this->datosValidos(['fecha' => '2026-10-20']))
            ->assertJsonValidationErrors('fecha');
    }

    #[TestWith(['146.520', 'FM', '2 m'], 'VHF')]
    #[TestWith(['223.500', 'FM', '1.25 m'], 'VHF 1,25 m')]
    #[TestWith(['435.000', 'FM', '70 cm'], 'UHF')]
    #[TestWith(['1296.200', 'CW', '23 cm'], 'UHF 23 cm')]
    public function test_registra_contactos_en_bandas_de_vhf_y_uhf(string $frecuencia, string $modo, string $banda): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();

        $this->actingAs($operador)
            ->postJson(route('api.contactos.store'), $this->datosValidos([
                'frecuencia_mhz' => $frecuencia,
                'modo_id' => $this->modo($modo)->id,
                'reporte_enviado' => $modo === 'CW' ? '599' : '59',
                'reporte_recibido' => $modo === 'CW' ? '579' : '57',
            ]))
            ->assertSuccessful();

        $this->assertSame($banda, $operador->contactos()->sole()->banda->nombre);
    }

    public function test_la_hora_de_fin_no_puede_ser_menor_que_la_de_inicio(): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.contactos.store'), $this->datosValidos(['hora_inicio' => '12:15', 'hora_fin' => '12:00']))
            ->assertJsonValidationErrors(['hora_fin' => 'La hora de fin no puede ser menor que la hora de inicio.']);
    }

    public function test_avisa_de_un_posible_duplicado_y_no_lo_guarda_sin_confirmar(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $this->actingAs($operador)->postJson(route('api.contactos.store'), $this->datosValidos());

        $this->actingAs($operador)
            ->postJson(route('api.contactos.store'), $this->datosValidos(['hora_inicio' => '12:20', 'hora_fin' => '12:25']))
            ->assertJsonValidationErrors('duplicado');

        $this->assertSame(1, $operador->contactos()->count());
    }

    public function test_guarda_el_duplicado_si_el_operador_lo_confirma(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $this->actingAs($operador)->postJson(route('api.contactos.store'), $this->datosValidos());

        $this->actingAs($operador)
            ->postJson(route('api.contactos.store'), $this->datosValidos(['hora_inicio' => '12:20', 'hora_fin' => '12:25', 'confirmar_duplicado' => '1']))
            ->assertSuccessful();

        $this->assertSame(2, $operador->contactos()->count());
    }

    public function test_no_es_duplicado_si_pasaron_mas_de_diez_minutos(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $this->actingAs($operador)->postJson(route('api.contactos.store'), $this->datosValidos());

        $this->actingAs($operador)
            ->postJson(route('api.contactos.store'), $this->datosValidos(['hora_inicio' => '12:26', 'hora_fin' => '12:30']))
            ->assertSuccessful();
    }

    public function test_el_listado_muestra_solo_los_contactos_del_operador(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'HC2MIO']);
        Contacto::factory()->for(User::factory())->create(['estacion_llamada' => 'HC3AJENO']);

        $this->actingAs($operador)
            ->getJson(route('api.contactos.index'))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.estacion_llamada', 'HC2MIO');
    }

    public function test_el_listado_vacio_lo_indica_en_el_resumen(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('api.contactos.index'))
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('resumen.contactos', 0);
    }

    public function test_el_listado_va_del_mas_reciente_al_mas_antiguo(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'HC2VIEJO', 'inicio_utc' => '2025-01-01 10:00:00']);
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'HC2NUEVO', 'inicio_utc' => '2026-01-01 10:00:00']);

        $this->actingAs($operador)
            ->getJson(route('api.contactos.index'))
            ->assertJsonPath('data.*.estacion_llamada', ['HC2NUEVO', 'HC2VIEJO']);
    }

    public function test_busca_por_indicativo_parcial_sin_distinguir_mayusculas(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'LU4BB']);
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'HC1QRC']);

        $this->actingAs($operador)
            ->getJson(route('api.contactos.index', ['indicativo' => 'lu4']))
            ->assertJsonPath('data.*.estacion_llamada', ['LU4BB']);
    }

    public function test_combina_filtros_de_banda_y_estado_qsl(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $veinteMetros = Banda::where('nombre', '20 m')->firstOrFail();
        $cuarentaMetros = Banda::where('nombre', '40 m')->firstOrFail();
        $confirmadoEn20 = Contacto::factory()->for($operador)->for($veinteMetros)->create(['estacion_llamada' => 'HC2UNO', 'frecuencia_mhz' => 14.2]);
        $confirmadoEn40 = Contacto::factory()->for($operador)->for($cuarentaMetros)->create(['estacion_llamada' => 'HC2DOS', 'frecuencia_mhz' => 7.1]);
        Contacto::factory()->for($operador)->for($veinteMetros)->create(['estacion_llamada' => 'HC2TRES', 'frecuencia_mhz' => 14.2]);
        ConfirmacionQsl::factory()->for($confirmadoEn20)->recibida()->create();
        ConfirmacionQsl::factory()->for($confirmadoEn40)->recibida()->create();

        $this->actingAs($operador)
            ->getJson(route('api.contactos.index', ['banda_id' => $veinteMetros->id, 'qsl' => 'confirmado']))
            ->assertJsonPath('data.*.estacion_llamada', ['HC2UNO']);
    }

    public function test_sin_coincidencias_la_lista_viene_vacia_pero_el_resumen_no(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'LU4BB']);

        $this->actingAs($operador)
            ->getJson(route('api.contactos.index', ['indicativo' => 'ZZ9']))
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('resumen.contactos', 1);
    }

    public function test_un_filtro_de_banda_no_numerico_se_rechaza(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'LU4BB']);

        $this->actingAs($operador)
            ->getJson(route('api.contactos.index', ['banda_id' => 'abc', 'modo_id' => '1 OR 1=1']))
            ->assertJsonValidationErrors(['banda_id', 'modo_id']);
    }

    public function test_pide_corregir_si_la_fecha_desde_es_mayor_que_hasta(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create();

        $this->actingAs($operador)
            ->getJson(route('api.contactos.index', ['desde' => '2026-05-10', 'hasta' => '2026-05-01']))
            ->assertJsonValidationErrors(['hasta' => 'La fecha de inicio no puede ser mayor que la fecha de fin.']);
    }

    public function test_modifica_un_contacto_propio_con_las_mismas_validaciones(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $contacto = Contacto::factory()->for($operador)->create();

        $this->actingAs($operador)
            ->putJson(route('api.contactos.update', $contacto), $this->datosValidos(['frecuencia_mhz' => '7.150']))
            ->assertOk()
            ->assertJsonPath('mensaje', 'Contacto con LU4BB actualizado.');

        $this->assertSame('40 m', $contacto->refresh()->banda->nombre);

        $this->actingAs($operador)
            ->putJson(route('api.contactos.update', $contacto), $this->datosValidos(['frecuencia_mhz' => '15.000']))
            ->assertJsonValidationErrors('frecuencia_mhz');
    }

    public function test_modificar_no_se_marca_como_duplicado_de_si_mismo(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $this->actingAs($operador)->postJson(route('api.contactos.store'), $this->datosValidos());
        $contacto = $operador->contactos()->sole();

        $this->actingAs($operador)
            ->putJson(route('api.contactos.update', $contacto), $this->datosValidos(['observaciones' => 'Corregido']))
            ->assertSuccessful();
    }

    public function test_no_puede_ver_ni_modificar_ni_eliminar_contactos_de_otro_operador(): void
    {
        $this->sembrarCatalogos();
        $ajeno = Contacto::factory()->for(User::factory())->create(['estacion_llamada' => 'HC3AJENO']);
        $intruso = User::factory()->create();
        $mensaje = 'El contacto no existe o no pertenece a tu libro de guardia.';

        $this->actingAs($intruso)->getJson(route('api.contactos.show', $ajeno))
            ->assertNotFound()
            ->assertJsonPath('message', $mensaje);
        $this->actingAs($intruso)->putJson(route('api.contactos.update', $ajeno), $this->datosValidos())->assertNotFound();
        $this->actingAs($intruso)->deleteJson(route('api.contactos.destroy', $ajeno))->assertNotFound();

        $this->assertSame('HC3AJENO', $ajeno->refresh()->estacion_llamada);
    }

    public function test_un_contacto_inexistente_responde_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('api.contactos.show', 999999))
            ->assertNotFound();
    }

    public function test_elimina_un_contacto_propio(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $contacto = Contacto::factory()->for($operador)->create();

        $this->actingAs($operador)
            ->deleteJson(route('api.contactos.destroy', $contacto))
            ->assertOk();

        $this->assertModelMissing($contacto);
    }
}
