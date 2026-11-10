<?php

namespace Database\Factories;

use App\Enums\TipoReporte;
use App\Models\Modo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Modo>
 */
class ModoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->lexify('???'),
            'tipo_reporte' => TipoReporte::Rs,
        ];
    }
}
