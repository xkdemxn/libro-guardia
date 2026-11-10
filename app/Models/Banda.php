<?php

namespace App\Models;

use Database\Factories\BandaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Banda de radioaficionados (20 m, 40 m...) con su rango de frecuencias.
 */
#[Fillable(['nombre', 'frecuencia_min_mhz', 'frecuencia_max_mhz'])]
class Banda extends Model
{
    /** @use HasFactory<BandaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'frecuencia_min_mhz' => 'float',
            'frecuencia_max_mhz' => 'float',
        ];
    }

    /**
     * Devuelve la banda que contiene la frecuencia, o null si está fuera de las bandas de radioaficionados.
     */
    public static function paraFrecuencia(float $frecuenciaMhz): ?self
    {
        return self::query()
            ->where('frecuencia_min_mhz', '<=', $frecuenciaMhz)
            ->where('frecuencia_max_mhz', '>=', $frecuenciaMhz)
            ->first();
    }

    /**
     * @return HasMany<Contacto, $this>
     */
    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }
}
