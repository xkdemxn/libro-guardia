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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportacionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cuatro registros: uno nuevo, uno ya existente en el libro, uno con frecuencia fuera de banda
     * y uno en SSB sin submodo (en 40 m debe quedar como LSB) con LoTW confirmado.
     */
    private const ADIF = "Exportado por otro programa\n<ADIF_VER:5>3.1.4 <EOH>\n"
        ."<CALL:5>LU4BB <QSO_DATE:8>20261027 <TIME_ON:4>1215 <TIME_OFF:4>1220 <FREQ:6>14.225 <MODE:3>SSB <SUBMODE:3>USB <RST_SENT:2>59 <RST_RCVD:2>57 <NAME:5>Lucia <EOR>\n"
        ."<CALL:6>HC2ABC <QSO_DATE:8>20261028 <TIME_ON:6>080000 <FREQ:5>7.074 <MODE:3>FT8 <RST_SENT:3>-10 <RST_RCVD:2>-5 <EOR>\n"
        ."<CALL:5>W1AW <QSO_DATE:8>20261029 <TIME_ON:4>1000 <FREQ:6>15.000 <MODE:2>CW <RST_SENT:3>599 <RST_RCVD:3>599 <EOR>\n"
        ."<call:6>EA8XYZ <qso_date:8>20261030 <time_on:4>2330 <freq:5>7.150 <mode:3>SSB <rst_sent:2>55 <rst_rcvd:2>55 <lotw_qsl_rcvd:1>Y <lotw_qslrdate:8>20261101 <EOR>\n";

    private function operadorConUnContactoPrevio(): User
    {
        $this->seed([BandaSeeder::class, ModoSeeder::class, PrefijoPaisSeeder::class]);
        $operador = User::factory()->create();

        Contacto::factory()->for($operador)->create([
            'estacion_llamada' => 'HC2ABC',
            'inicio_utc' => '2026-10-28 08:03:00',
            'frecuencia_mhz' => 7.074,
            'banda_id' => Banda::where('nombre', '40 m')->value('id'),
            'modo_id' => Modo::where('nombre', 'FT8')->value('id'),
        ]);

        return $operador;
    }

    private function archivo(string $contenido = self::ADIF, string $nombre = 'mi-log.adi'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nombre, $contenido);
    }

    public function test_muestra_la_vista_previa_con_nuevos_duplicados_y_errores(): void
    {
        Storage::fake('local');
        $operador = $this->operadorConUnContactoPrevio();

        $this->actingAs($operador)
            ->post(route('importacion.store'), ['archivo' => $this->archivo()])
            ->assertRedirect(route('importacion.vista-previa'));

        $this->actingAs($operador)
            ->get(route('importacion.vista-previa'))
            ->assertOk()
            ->assertViewHas('nuevos', 2)
            ->assertViewHas('duplicados', 1)
            ->assertViewHas('errores', 1)
            ->assertSee('La frecuencia 15 MHz está fuera de las bandas de radioaficionados.');

        $this->assertSame(1, $operador->contactos()->count(), 'La vista previa no debe guardar nada.');
    }

    public function test_al_confirmar_importa_los_nuevos_en_utc_y_muestra_el_resumen(): void
    {
        Storage::fake('local');
        $operador = $this->operadorConUnContactoPrevio();
        $this->actingAs($operador)->post(route('importacion.store'), ['archivo' => $this->archivo()]);

        $this->actingAs($operador)
            ->post(route('importacion.confirmar'))
            ->assertRedirect(route('importacion.create'))
            ->assertSessionHas('resultado', fn (array $resultado): bool => $resultado['importados'] === 2
                && $resultado['omitidos'] === 1
                && $resultado['fallidos'] === 1
                && $resultado['errores'][0]['indicativo'] === 'W1AW');

        $lu4bb = $operador->contactos()->where('estacion_llamada', 'LU4BB')->sole();
        $this->assertSame('2026-10-27 12:15:00', $lu4bb->inicio_utc->format('Y-m-d H:i:s'));
        $this->assertSame('USB', $lu4bb->modo->nombre);
        $this->assertSame('Lucia', $lu4bb->nombre_operador);

        $ea8 = $operador->contactos()->where('estacion_llamada', 'EA8XYZ')->sole();
        $this->assertSame('LSB', $ea8->modo->nombre);
        $this->assertSame('Islas Canarias', $ea8->prefijoPais->pais);
        $this->assertTrue($ea8->confirmacionesQsl()->where('tipo', TipoQsl::Lotw)->where('recibida', true)->exists());

        $this->assertSame(3, $operador->contactos()->count());
        $this->assertSame([], Storage::disk('local')->allFiles('importaciones'));
    }

    public function test_rechaza_un_archivo_que_no_es_adif(): void
    {
        Storage::fake('local');
        $operador = $this->operadorConUnContactoPrevio();

        $this->actingAs($operador)
            ->post(route('importacion.store'), ['archivo' => $this->archivo('hola, esto no es un log', 'notas.txt')])
            ->assertSessionHasErrors(['archivo' => 'El archivo no tiene un formato ADIF válido o no contiene contactos.']);

        $this->actingAs($operador)
            ->post(route('importacion.store'), ['archivo' => $this->archivo('sin registros <EOH>', 'vacio.adi')])
            ->assertSessionHasErrors('archivo');
    }

    public function test_confirmar_sin_archivo_pendiente_no_importa_nada(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('importacion.confirmar'))
            ->assertRedirect(route('importacion.create'))
            ->assertSessionHas('error');
    }
}
