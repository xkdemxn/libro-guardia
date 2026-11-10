<?php

namespace App\Models;

use App\Enums\TipoQsl;
use Database\Factories\ConfirmacionQslFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Confirmación QSL de un contacto (tarjeta, eQSL, LoTW o interna), enviada y recibida por separado.
 */
#[Table('confirmaciones_qsl')]
#[Fillable(['tipo', 'enviada', 'fecha_envio', 'recibida', 'fecha_recepcion'])]
class ConfirmacionQsl extends Model
{
    /** @use HasFactory<ConfirmacionQslFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipo' => TipoQsl::class,
            'enviada' => 'boolean',
            'recibida' => 'boolean',
            'fecha_envio' => 'date',
            'fecha_recepcion' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Contacto, $this>
     */
    public function contacto(): BelongsTo
    {
        return $this->belongsTo(Contacto::class);
    }
}
