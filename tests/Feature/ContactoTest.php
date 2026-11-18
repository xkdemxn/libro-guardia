<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class ContactoTest extends TestCase
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

    public function test_un_visitante_sin_sesion_es_enviado_al_login(): void
    {
        $this->get(route('contactos.index'))->assertRedirect(route('login'));
        $this->post(route('contactos.store'), [])->assertRedirect(route('login'));
    }

    public function test_registra_un_contacto_con_hora_utc_banda_y_pais_calculados(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();

        $this->actingAs($operador)
            ->post(route('contactos.store'), $this->datosValidos())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contactos.index'));

        $contacto = $operador->contactos()->sole();
        $this->assertSame('LU4BB', $contacto->estacion_llamada);
        $this->assertSame('2026-10-27 12:15:00', $contacto->inicio_utc->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-27 12:20:00', $contacto->fin_utc->format('Y-m-d H:i:s'));
        $this->assertSame('20 m', $contacto->banda->nombre);
        $this->assertSame('Argentina', $contacto->prefijoPais->pais);
    }

    public function test_el_contacto_registrado_aparece_en_el_listado(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'HC2ABC']);

        $this->actingAs($operador)
            ->get(route('contactos.index'))
            ->assertOk()
            ->assertSee('HC2ABC');
    }

    public function test_campos_obligatorios_vacios_se_rechazan(): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->post(route('contactos.store'), [])
            ->assertSessionHasErrors(['estacion_llamada', 'fecha', 'hora_inicio', 'frecuencia_mhz', 'modo_id', 'reporte_enviado', 'reporte_recibido']);

        $this->assertDatabaseCount('contactos', 0);
    }

    public function test_rechaza_un_indicativo_invalido(): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->post(route('contactos.store'), $this->datosValidos(['estacion_llamada' => 'HOLA']))
            ->assertSessionHasErrors(['estacion_llamada' => 'El indicativo no es un indicativo válido (ej. HC1QRC).']);
    }

    #[TestWith(['15.000'], 'entre bandas')]
    #[TestWith(['abc'], 'no numérica')]
    public function test_rechaza_frecuencias_fuera_de_las_bandas_o_no_numericas(string $frecuencia): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->post(route('contactos.store'), $this->datosValidos(['frecuencia_mhz' => $frecuencia]))
            ->assertSessionHasErrors('frecuencia_mhz');

        $this->assertDatabaseCount('contactos', 0);
    }

    public function test_el_mensaje_de_frecuencia_fuera_de_banda_es_claro(): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->post(route('contactos.store'), $this->datosValidos(['frecuencia_mhz' => '15.000']))
            ->assertSessionHasErrors(['frecuencia_mhz' => 'La frecuencia está fuera de las bandas de radioaficionados.']);
    }

    #[TestWith(['USB', '599', 'En este modo el reporte es RS (ej. 59).'])]
    #[TestWith(['CW', '59', 'En este modo el reporte es RST (ej. 599).'])]
    #[TestWith(['FT8', '59', 'En este modo el reporte es dB (ej. -12).'])]
    public function test_el_reporte_debe_corresponder_al_modo(string $modo, string $reporte, string $mensaje): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->post(route('contactos.store'), $this->datosValidos([
                'modo_id' => $this->modo($modo)->id,
                'reporte_enviado' => $reporte,
            ]))
            ->assertSessionHasErrors(['reporte_enviado' => $mensaje]);
    }

    public function test_acepta_reportes_en_db_para_ft8(): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->post(route('contactos.store'), $this->datosValidos([
                'frecuencia_mhz' => '7.074',
                'modo_id' => $this->modo('FT8')->id,
                'reporte_enviado' => '-12',
                'reporte_recibido' => '+3',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contactos', ['reporte_enviado' => '-12', 'reporte_recibido' => '+3']);
    }

    public function test_el_formulario_nuevo_trae_la_fecha_y_hora_utc_actuales(): void
    {
        $this->sembrarCatalogos();
        $this->travelTo(now('UTC')->setDate(2026, 11, 14)->setTime(15, 42));

        $this->actingAs(User::factory()->create())
            ->get(route('contactos.create'))
            ->assertOk()
            ->assertSee('value="2026-11-14"', false)
            ->assertSee('value="15:42"', false);
    }

    public function test_no_acepta_una_fecha_futura(): void
    {
        $this->sembrarCatalogos();
        $this->travelTo(now('UTC')->setDate(2026, 11, 14)->setTime(12, 0));

        $this->actingAs(User::factory()->create())
            ->post(route('contactos.store'), $this->datosValidos(['fecha' => '2026-11-15']))
            ->assertSessionHasErrors(['fecha' => 'La fecha no puede ser posterior a hoy (2026-11-14 UTC).']);
    }

    public function test_no_acepta_una_fecha_pasada_al_registrar(): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->post(route('contactos.store'), $this->datosValidos(['fecha' => '2026-10-26']))
            ->assertSessionHasErrors(['fecha' => 'La fecha no puede ser anterior a hoy (2026-10-27 UTC).']);
    }

    public function test_al_editar_conserva_la_fecha_original_aunque_sea_pasada(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $this->actingAs($operador)->post(route('contactos.store'), $this->datosValidos());
        $contacto = $operador->contactos()->sole();

        $this->travelTo(now('UTC')->setDate(2026, 11, 3));

        $this->get(route('contactos.edit', $contacto))->assertSee('min="2026-10-27"', false);
        $this->put(route('contactos.update', $contacto), $this->datosValidos(['observaciones' => 'Corregido']))
            ->assertSessionHasNoErrors();
        $this->put(route('contactos.update', $contacto), $this->datosValidos(['fecha' => '2026-10-20']))
            ->assertSessionHasErrors('fecha');
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
            ->post(route('contactos.store'), $this->datosValidos([
                'frecuencia_mhz' => $frecuencia,
                'modo_id' => $this->modo($modo)->id,
                'reporte_enviado' => $modo === 'CW' ? '599' : '59',
                'reporte_recibido' => $modo === 'CW' ? '579' : '57',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame($banda, $operador->contactos()->sole()->banda->nombre);
    }

    public function test_la_hora_de_fin_no_puede_ser_menor_que_la_de_inicio(): void
    {
        $this->sembrarCatalogos();

        $this->actingAs(User::factory()->create())
            ->post(route('contactos.store'), $this->datosValidos(['hora_inicio' => '12:15', 'hora_fin' => '12:00']))
            ->assertSessionHasErrors(['hora_fin' => 'La hora de fin no puede ser menor que la hora de inicio.']);
    }

    public function test_avisa_de_un_posible_duplicado_y_no_lo_guarda_sin_confirmar(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $this->actingAs($operador)->post(route('contactos.store'), $this->datosValidos());

        $this->actingAs($operador)
            ->post(route('contactos.store'), $this->datosValidos(['hora_inicio' => '12:20', 'hora_fin' => '12:25']))
            ->assertSessionHasErrors('duplicado');

        $this->assertSame(1, $operador->contactos()->count());
    }

    public function test_guarda_el_duplicado_si_el_operador_lo_confirma(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $this->actingAs($operador)->post(route('contactos.store'), $this->datosValidos());

        $this->actingAs($operador)
            ->post(route('contactos.store'), $this->datosValidos(['hora_inicio' => '12:20', 'hora_fin' => '12:25', 'confirmar_duplicado' => '1']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $operador->contactos()->count());
    }

    public function test_no_es_duplicado_si_pasaron_mas_de_diez_minutos(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $this->actingAs($operador)->post(route('contactos.store'), $this->datosValidos());

        $this->actingAs($operador)
            ->post(route('contactos.store'), $this->datosValidos(['hora_inicio' => '12:26', 'hora_fin' => '12:30']))
            ->assertSessionHasNoErrors();
    }

    public function test_el_listado_muestra_solo_los_contactos_del_operador(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'HC2MIO']);
        Contacto::factory()->for(User::factory())->create(['estacion_llamada' => 'HC3AJENO']);

        $this->actingAs($operador)
            ->get(route('contactos.index'))
            ->assertSee('HC2MIO')
            ->assertDontSee('HC3AJENO');
    }

    public function test_el_listado_vacio_invita_a_registrar_el_primer_contacto(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('contactos.index'))
            ->assertOk()
            ->assertSee('Aún no tienes contactos registrados.');
    }

    public function test_el_listado_va_del_mas_reciente_al_mas_antiguo(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'HC2VIEJO', 'inicio_utc' => '2025-01-01 10:00:00']);
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'HC2NUEVO', 'inicio_utc' => '2026-01-01 10:00:00']);

        $this->actingAs($operador)
            ->get(route('contactos.index'))
            ->assertSeeInOrder(['HC2NUEVO', 'HC2VIEJO']);
    }

    public function test_busca_por_indicativo_parcial_sin_distinguir_mayusculas(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'LU4BB']);
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'HC1QRC']);

        $this->actingAs($operador)
            ->get(route('contactos.index', ['indicativo' => 'lu4']))
            ->assertSee('LU4BB')
            ->assertDontSee('HC1QRC');
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
            ->get(route('contactos.index', ['banda_id' => $veinteMetros->id, 'qsl' => 'confirmado']))
            ->assertSee('HC2UNO')
            ->assertDontSee('HC2DOS')
            ->assertDontSee('HC2TRES');
    }

    public function test_avisa_cuando_ningun_contacto_coincide_con_la_busqueda(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'LU4BB']);

        $this->actingAs($operador)
            ->get(route('contactos.index', ['indicativo' => 'ZZ9']))
            ->assertSee('No se encontraron contactos.');
    }

    public function test_un_filtro_de_banda_no_numerico_se_ignora_sin_romper_la_pagina(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create(['estacion_llamada' => 'LU4BB']);

        $this->actingAs($operador)
            ->get(route('contactos.index', ['banda_id' => 'abc', 'modo_id' => '1 OR 1=1']))
            ->assertOk()
            ->assertSee('LU4BB');
    }

    public function test_pide_corregir_si_la_fecha_desde_es_mayor_que_hasta(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        Contacto::factory()->for($operador)->create();

        $this->actingAs($operador)
            ->get(route('contactos.index', ['desde' => '2026-05-10', 'hasta' => '2026-05-01']))
            ->assertOk()
            ->assertSee('La fecha de inicio no puede ser mayor que la fecha de fin.');
    }

    public function test_modifica_un_contacto_propio_con_las_mismas_validaciones(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $contacto = Contacto::factory()->for($operador)->create();

        $this->actingAs($operador)
            ->put(route('contactos.update', $contacto), $this->datosValidos(['frecuencia_mhz' => '7.150']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contactos.index'));

        $this->assertSame('40 m', $contacto->refresh()->banda->nombre);

        $this->actingAs($operador)
            ->put(route('contactos.update', $contacto), $this->datosValidos(['frecuencia_mhz' => '15.000']))
            ->assertSessionHasErrors('frecuencia_mhz');
    }

    public function test_modificar_no_se_marca_como_duplicado_de_si_mismo(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $this->actingAs($operador)->post(route('contactos.store'), $this->datosValidos());
        $contacto = $operador->contactos()->sole();

        $this->actingAs($operador)
            ->put(route('contactos.update', $contacto), $this->datosValidos(['observaciones' => 'Corregido']))
            ->assertSessionHasNoErrors();
    }

    public function test_no_puede_ver_ni_modificar_ni_eliminar_contactos_de_otro_operador(): void
    {
        $this->sembrarCatalogos();
        $ajeno = Contacto::factory()->for(User::factory())->create(['estacion_llamada' => 'HC3AJENO']);
        $intruso = User::factory()->create();
        $mensaje = 'El contacto no existe o no pertenece a tu libro de guardia.';

        $this->actingAs($intruso)->get(route('contactos.edit', $ajeno))
            ->assertRedirect(route('contactos.index'))
            ->assertSessionHas('error', $mensaje);
        $this->actingAs($intruso)->put(route('contactos.update', $ajeno), $this->datosValidos())
            ->assertSessionHas('error', $mensaje);
        $this->actingAs($intruso)->delete(route('contactos.destroy', $ajeno))
            ->assertSessionHas('error', $mensaje);

        $this->assertSame('HC3AJENO', $ajeno->refresh()->estacion_llamada);
    }

    public function test_editar_un_contacto_inexistente_muestra_un_error(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('contactos.edit', 999999))
            ->assertRedirect(route('contactos.index'))
            ->assertSessionHas('error');
    }

    public function test_elimina_un_contacto_propio(): void
    {
        $this->sembrarCatalogos();
        $operador = User::factory()->create();
        $contacto = Contacto::factory()->for($operador)->create();

        $this->actingAs($operador)
            ->delete(route('contactos.destroy', $contacto))
            ->assertRedirect(route('contactos.index'));

        $this->assertModelMissing($contacto);
    }
}
