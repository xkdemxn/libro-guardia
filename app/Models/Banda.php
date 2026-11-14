<?php

namespace App\Models;

use Database\Factories\BandaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
     * Rangos del espectro según la frecuencia (en MHz). 160 m queda en MF, por debajo de HF.
     *
     * @var array<string, array{0: float, 1: float, 2: string}>
     */
    public const RANGOS = [
        'MF' => [0.3, 3.0, 'Onda media'],
        'HF' => [3.0, 30.0, 'Onda corta'],
        'VHF' => [30.0, 300.0, 'Muy alta frecuencia'],
        'UHF' => [300.0, 3000.0, 'Ultra alta frecuencia'],
    ];

    /**
     * Rango del espectro de la banda: MF, HF, VHF o UHF.
     */
    protected function rango(): Attribute
    {
        return Attribute::make(get: fn (): string => self::rangoPara($this->frecuencia_min_mhz));
    }

    public static function rangoPara(float $frecuenciaMhz): string
    {
        foreach (self::RANGOS as $rango => [$minimo, $maximo]) {
            if ($frecuenciaMhz >= $minimo && $frecuenciaMhz < $maximo) {
                return $rango;
            }
        }

        return 'UHF';
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
