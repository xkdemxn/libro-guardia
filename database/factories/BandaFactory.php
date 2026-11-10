<?php

namespace Database\Factories;

use App\Models\Banda;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banda>
 */
class BandaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->numerify('### m'),
            'frecuencia_min_mhz' => 1.0,
            'frecuencia_max_mhz' => 2.0,
        ];
    }
}
