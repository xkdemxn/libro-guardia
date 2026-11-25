<?php

namespace App\Models;

use App\Enums\TipoReporte;
use Database\Factories\ModoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modo de emisión (USB, CW, FT8...). Define qué tipo de reporte de señal se usa.
 */
#[Fillable(['nombre', 'tipo_reporte'])]
class Modo extends Model
{
    /** @use HasFactory<ModoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipo_reporte' => TipoReporte::class,
        ];
    }

    /**
     * Nombre para mostrar: USB y LSB son variantes de SSB, así que se muestran como «SSB · USB».
     *
     * @return Attribute<string, never>
     */
    protected function etiqueta(): Attribute
    {
        return Attribute::get(fn (): string => in_array($this->nombre, ['USB', 'LSB'], true) ? 'SSB · '.$this->nombre : $this->nombre);
    }

    /**
     * @return HasMany<Contacto, $this>
     */
    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }
}
