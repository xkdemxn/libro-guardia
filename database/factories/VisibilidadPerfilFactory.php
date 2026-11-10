<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VisibilidadPerfil;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisibilidadPerfil>
 */
class VisibilidadPerfilFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
        ];
    }
}
