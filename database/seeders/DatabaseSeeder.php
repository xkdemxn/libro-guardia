<?php

namespace Database\Seeders;

use App\Models\ConfirmacionQsl;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Carga los catálogos y un operador de demostración (HC1QRC / password) con contactos.
     */
    public function run(): void
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
        ]);

        $contactos = Contacto::factory(60)->for($operador)->create();

        $contactos->random(20)->each(function (Contacto $contacto): void {
            ConfirmacionQsl::factory()->for($contacto)->recibida()->create();
        });
    }
}
