<?php

namespace Database\Seeders;

use App\Models\Banda;
use App\Models\ConfirmacionQsl;
use App\Models\Contacto;
use App\Models\Modo;
use App\Models\User;
use App\Services\ConfirmadorQsl;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Carga los catálogos y dos operadores de demostración (contraseña "password"):
     * HC1QRC con 60 contactos, y LU4BB, que registró el mismo QSO que HC1QRC para mostrar
     * la confirmación QSL automática.
     */
    public function run(ConfirmadorQsl $confirmador): void
    {
        $this->call([
            BandaSeeder::class,
            ModoSeeder::class,
            PrefijoPaisSeeder::class,
        ]);

        $operador = User::factory()->create([
            'indicativo' => 'HC1QRC',
            'nombres' => 'Operador',
            'apellidos' => 'Demo',
            'email' => 'demo@example.com',
            'grid_locator' => 'FI09',
            'equipo_radio' => 'Yaesu FT-991A',
            'antena' => 'Dipolo 40 m',
        ]);

        $contactos = Contacto::factory(60)->for($operador)->create();

        $contactos->random(20)->each(function (Contacto $contacto): void {
            ConfirmacionQsl::factory()->for($contacto)->recibida()->create();
        });

        $colega = User::factory()->create([
            'indicativo' => 'LU4BB',
            'nombres' => 'Lucía',
            'apellidos' => 'Benítez',
            'email' => 'lu4bb@example.com',
            'pais' => 'Argentina',
            'ciudad' => 'Buenos Aires',
            'grid_locator' => 'GF05',
            'biografia' => 'Operadora de HF desde 2012. Me encanta el DX en 20 m.',
        ]);

        $veinteMetros = Banda::where('nombre', '20 m')->value('id');
        $usb = Modo::where('nombre', 'USB')->value('id');
        $mismoQso = [
            'frecuencia_mhz' => 14.225,
            'banda_id' => $veinteMetros,
            'modo_id' => $usb,
            'reporte_enviado' => '59',
            'reporte_recibido' => '59',
        ];

        Contacto::factory()->for($operador)->create([
            ...$mismoQso,
            'estacion_llamada' => 'LU4BB',
            'inicio_utc' => now('UTC')->subDays(2)->setTime(12, 15),
            'fin_utc' => now('UTC')->subDays(2)->setTime(12, 20),
        ]);
        $deLaColega = Contacto::factory()->for($colega)->create([
            ...$mismoQso,
            'estacion_llamada' => 'HC1QRC',
            'inicio_utc' => now('UTC')->subDays(2)->setTime(12, 16),
            'fin_utc' => now('UTC')->subDays(2)->setTime(12, 20),
        ]);

        $confirmador->sincronizar($deLaColega);
    }
}
