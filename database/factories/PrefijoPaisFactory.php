<?php

namespace Database\Factories;

use App\Models\PrefijoPais;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrefijoPais>
 */
class PrefijoPaisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prefijo' => fake()->unique()->lexify('??'),
            'pais' => fake()->country(),
            'continente' => 'SA',
        ];
    }
}
