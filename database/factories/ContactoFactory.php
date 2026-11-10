<?php

namespace Database\Factories;

use App\Enums\TipoReporte;
use App\Models\Banda;
use App\Models\Contacto;
use App\Models\Modo;
use App\Models\PrefijoPais;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Requiere que existan bandas y modos (BandaSeeder y ModoSeeder).
 *
 * @extends Factory<Contacto>
 */
class ContactoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $banda = Banda::query()->whereIn('nombre', ['40 m', '20 m', '15 m', '10 m'])->inRandomOrder()->firstOrFail();
        $modo = Modo::query()->whereIn('nombre', ['USB', 'LSB', 'CW', 'FT8'])->inRandomOrder()->firstOrFail();
        $estacion = fake()->randomElement(['HC', 'LU', 'PY', 'CE', 'HK', 'OA', 'K', 'W', 'EA', 'JA', 'DL', 'XE'])
            .fake()->numberBetween(1, 9)
            .fake()->regexify('[A-Z]{2,3}');
        $inicio = fake()->dateTimeBetween('-1 year', 'now', 'UTC');

        return [
            'user_id' => User::factory(),
            'estacion_llamada' => $estacion,
            'inicio_utc' => $inicio,
            'fin_utc' => (clone $inicio)->modify('+'.fake()->numberBetween(1, 15).' minutes'),
            'frecuencia_mhz' => round(fake()->randomFloat(4, $banda->frecuencia_min_mhz, $banda->frecuencia_max_mhz), 3),
            'banda_id' => $banda->id,
            'modo_id' => $modo->id,
            'prefijo_pais_id' => PrefijoPais::paraIndicativo($estacion)?->id,
            'reporte_enviado' => $this->reporteDeEjemplo($modo->tipo_reporte),
            'reporte_recibido' => $this->reporteDeEjemplo($modo->tipo_reporte),
            'potencia_w' => fake()->randomElement([5, 10, 50, 100]),
            'observaciones' => null,
        ];
    }

    private function reporteDeEjemplo(TipoReporte $tipo): string
    {
        return match ($tipo) {
            TipoReporte::Rs => fake()->randomElement(['59', '57', '55', '45']),
            TipoReporte::Rst => fake()->randomElement(['599', '579', '559']),
            TipoReporte::Db => (string) fake()->numberBetween(-20, 5),
        };
    }
}
