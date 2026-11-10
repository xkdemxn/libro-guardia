<?php

namespace Database\Factories;

use App\Enums\TipoQsl;
use App\Models\ConfirmacionQsl;
use App\Models\Contacto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConfirmacionQsl>
 */
class ConfirmacionQslFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contacto_id' => Contacto::factory(),
            'tipo' => fake()->randomElement([TipoQsl::Tarjeta, TipoQsl::Eqsl, TipoQsl::Lotw]),
            'enviada' => true,
            'fecha_envio' => now()->toDateString(),
            'recibida' => false,
            'fecha_recepcion' => null,
        ];
    }

    public function recibida(): static
    {
        return $this->state(fn (): array => [
            'recibida' => true,
            'fecha_recepcion' => now()->toDateString(),
        ]);
    }
}
