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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class TarjetaQslApiTest extends ApiTestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Contacto}
     */
    private function contactoConTarjeta(): array
    {
        $this->seed([BandaSeeder::class, ModoSeeder::class, PrefijoPaisSeeder::class]);
        $operador = User::factory()->create([
            'indicativo' => 'HC1QRC',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'ciudad' => 'Quito',
            'provincia' => 'Pichincha',
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

        $respuesta = $this->actingAs($operador)
            ->getJson(route('api.tarjeta.show', $contacto))
            ->assertOk()
            ->assertJsonPath('nombre_archivo', 'qsl-HC1QRC-LU4BB-20261027-1215');
        $svg = $respuesta->json('svg');

        $this->assertMatchesRegularExpression('/HC1QRC.*Ana Pérez.*CONFIRMANDO QSO CON.*LU4BB/s', $svg);
        foreach (['Ecuador, Sudamérica   ·   Quito - Pichincha   ·   ITU 12 - CQ 10   ·   Zona 1   ·   Grid FI09', '27 OCT 2026', '12:15', '14.225', '20 m', 'PSE QSL TNX'] as $texto) {
            $this->assertStringContainsString($texto, $svg);
        }
        $this->assertStringNotContainsString('Verificado en Libro de Guardia', $svg);
    }

    public function test_si_esta_confirmado_automaticamente_lo_indica(): void
    {
        [$operador, $contacto] = $this->contactoConTarjeta();
        ConfirmacionQsl::factory()->for($contacto)->recibida()->create(['tipo' => TipoQsl::Interna]);

        $svg = $this->actingAs($operador)->getJson(route('api.tarjeta.show', $contacto))->json('svg');

        $this->assertStringContainsString('Verificado en Libro de Guardia', $svg);
        $this->assertStringContainsString('TNX QSL', $svg);
    }

    public function test_se_descarga_como_svg(): void
    {
        [$operador, $contacto] = $this->contactoConTarjeta();

        $respuesta = $this->actingAs($operador)
            ->get(route('api.tarjeta.svg', $contacto))
            ->assertOk()
            ->assertDownload('qsl-HC1QRC-LU4BB-20261027-1215.svg')
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $respuesta->getContent());
        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg"', $respuesta->getContent());
    }

    public function test_envia_un_png_y_registra_el_envio_sin_alterar_la_recepcion(): void
    {
        [$operador, $contacto] = $this->contactoConTarjeta();
        $recepcion = ConfirmacionQsl::factory()->for($contacto)->recibida()->create([
            'tipo' => TipoQsl::Tarjeta,
        ]);
        $png = "\x89PNG\r\n\x1a\n";
        Process::fake(['*' => Process::result(output: $png)]);

        $this->actingAs($operador)->postJson(route('api.tarjeta.enviar', $contacto), [
            'destinatario' => 'radio@example.test',
        ])->assertOk()->assertJsonPath('mensaje', 'El servidor de correo aceptó la tarjeta QSL. El envío quedó registrado en el contacto.');

        $mensaje = Mail::mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertSame('radio@example.test', $mensaje->getTo()[0]->getAddress());
        $adjunto = $mensaje->getAttachments()[0];
        $this->assertSame('qsl-HC1QRC-LU4BB-20261027-1215.png', $adjunto->getFilename());
        $this->assertSame($png, $adjunto->getBody());
        $this->assertTrue($recepcion->fresh()->enviada);
        $this->assertTrue($recepcion->fresh()->recibida);
        $this->assertSame($recepcion->fecha_recepcion->toDateString(), $recepcion->fresh()->fecha_recepcion->toDateString());
    }

    public function test_un_error_del_renderizador_no_envia_ni_marca_la_tarjeta(): void
    {
        [$operador, $contacto] = $this->contactoConTarjeta();
        Process::fake(['*' => Process::result(exitCode: 1)]);

        $this->actingAs($operador)->postJson(route('api.tarjeta.enviar', $contacto), [
            'destinatario' => 'radio@example.test',
        ])->assertServerError()->assertJsonPath('message', 'No se pudo generar el PNG de la tarjeta.');

        $this->assertDatabaseMissing('confirmaciones_qsl', ['contacto_id' => $contacto->id, 'tipo' => TipoQsl::Tarjeta->value]);
        $this->assertCount(0, Mail::mailer('array')->getSymfonyTransport()->messages());
    }

    public function test_un_operador_no_puede_enviar_tarjetas_de_otro_libro(): void
    {
        [, $contacto] = $this->contactoConTarjeta();
        Process::fake();

        $this->actingAs(User::factory()->create())->postJson(route('api.tarjeta.enviar', $contacto), [
            'destinatario' => 'radio@example.test',
        ])->assertNotFound();

        Process::assertNothingRan();
    }

    public function test_los_textos_se_escapan_para_no_romper_el_svg(): void
    {
        [$operador, $contacto] = $this->contactoConTarjeta();
        $operador->update(['equipo_radio' => 'Yaesu <FT-991A> & "amplificador"']);

        $svg = $this->actingAs($operador)->get(route('api.tarjeta.svg', $contacto))->getContent();

        $this->assertStringContainsString('Yaesu &lt;FT-991A&gt; &amp;', $svg);
        $this->assertNotFalse(simplexml_load_string($svg), 'El SVG debe ser XML válido.');
    }

    public function test_la_foto_personalizada_va_incrustada_en_la_tarjeta(): void
    {
        Storage::fake('public');
        [$operador, $contacto] = $this->contactoConTarjeta();

        $this->actingAs($operador)
            ->post(route('api.tarjeta.personalizar'), [
                '_method' => 'PATCH',
                'qsl_foto' => UploadedFile::fake()->image('estacion.jpg', 1400, 900),
                'qsl_foto_estilo' => 'completa', 'qsl_foto_opacidad' => 30,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('personalizacion.opacidad', 30);
        Storage::disk('public')->assertExists($operador->refresh()->qsl_foto_path);

        // Va como data URI: el PNG se genera en el navegador y un SVG no puede cargar imágenes externas.
        $this->assertStringContainsString('jpeg;base64,', $this->getJson(route('api.tarjeta.show', $contacto))->json('svg'));

        $svg = $this->get(route('api.tarjeta.svg', $contacto))->getContent();
        $this->assertStringContainsString('<image href="data:image/jpeg;base64,', $svg);
        $this->assertStringContainsString('opacity="0.3"', $svg);
        $this->assertNotFalse(simplexml_load_string($svg), 'El SVG con la foto debe seguir siendo XML válido.');
    }

    public function test_el_mensaje_personalizado_sale_en_la_tarjeta_y_vacio_vuelve_al_de_siempre(): void
    {
        [$operador, $contacto] = $this->contactoConTarjeta();

        $this->actingAs($operador)->patchJson(route('api.tarjeta.personalizar'), ['qsl_mensaje' => '  TNX QSO · 73 desde Quito  ']);
        $this->assertSame('TNX QSO · 73 desde Quito', $operador->refresh()->qsl_mensaje);
        $this->assertStringContainsString('TNX QSO · 73 desde Quito', $this->get(route('api.tarjeta.svg', $contacto))->getContent());

        $this->actingAs($operador)->patchJson(route('api.tarjeta.personalizar'), ['qsl_mensaje' => '']);
        $this->assertNull($operador->refresh()->qsl_mensaje);
        $this->assertStringContainsString('Confirmando nuestro QSO. ¡Gracias y 73!', $this->get(route('api.tarjeta.svg', $contacto))->getContent());
    }

    public function test_el_mensaje_y_la_opacidad_tienen_limite(): void
    {
        [$operador] = $this->contactoConTarjeta();

        $this->actingAs($operador)
            ->patchJson(route('api.tarjeta.personalizar'), ['qsl_mensaje' => str_repeat('a', 151), 'qsl_foto_estilo' => 'completa', 'qsl_foto_opacidad' => 110])
            ->assertJsonValidationErrors(['qsl_mensaje', 'qsl_foto_opacidad']);
    }

    public function test_solo_la_vista_previa_lleva_los_ids_para_editarla_en_vivo(): void
    {
        [$operador, $contacto] = $this->contactoConTarjeta();

        $vistaPrevia = $this->actingAs($operador)->getJson(route('api.tarjeta.show', $contacto))->json('svg');
        $descarga = $this->get(route('api.tarjeta.svg', $contacto))->getContent();

        $this->assertStringContainsString('id="qsl-foto"', $vistaPrevia);
        $this->assertStringContainsString('data-linea="0"', $vistaPrevia);
        $this->assertStringNotContainsString('qsl-foto', $descarga);
        $this->assertStringNotContainsString('data-linea', $descarga);
    }

    public function test_sin_foto_la_tarjeta_usa_el_atardecer_y_lleva_emblema_y_bandera(): void
    {
        [$operador, $contacto] = $this->contactoConTarjeta();

        $svg = $this->actingAs($operador)->get(route('api.tarjeta.svg', $contacto))->getContent();

        $this->assertStringNotContainsString('image/jpeg', $svg);
        $this->assertStringContainsString('<image href="data:image/png;base64,', $svg);
        $this->assertStringContainsString('<image href="data:image/svg+xml;base64,', $svg);
        $this->assertStringContainsString('Confirmando nuestro QSO. ¡Gracias y 73!', $svg);
    }

    public function test_el_mensaje_largo_se_parte_en_lineas_respetando_los_saltos(): void
    {
        [$operador, $contacto] = $this->contactoConTarjeta();

        $this->actingAs($operador)->patchJson(route('api.tarjeta.personalizar'), [
            'qsl_mensaje' => "Los Andes, serranía ecuatoriana, puesta de sol a 2668 msnm\r\nTNX QSO 73",
        ]);

        $this->assertSame(
            ['Los Andes, serranía ecuatoriana, puesta de sol a 2668 msnm', 'TNX QSO 73'],
            $operador->refresh()->lineasMensajeQsl(),
        );
        $this->assertStringContainsString('>TNX QSO 73</text>', $this->get(route('api.tarjeta.svg', $contacto))->getContent());
    }

    public function test_no_se_puede_ver_la_tarjeta_de_un_contacto_ajeno(): void
    {
        [, $contacto] = $this->contactoConTarjeta();

        $this->actingAs(User::factory()->create())
            ->getJson(route('api.tarjeta.show', $contacto))
            ->assertNotFound();
    }
}
