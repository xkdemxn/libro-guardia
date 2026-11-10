<?php

namespace App\Models;

use Database\Factories\VisibilidadPerfilFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Qué datos del perfil decide mostrar el operador al público (US-13, US-15).
 */
#[Table('visibilidad_perfil')]
#[Fillable(['mostrar_nombre', 'mostrar_pais_ciudad', 'mostrar_equipo', 'mostrar_biografia_foto'])]
class VisibilidadPerfil extends Model
{
    /** @use HasFactory<VisibilidadPerfilFactory> */
    use HasFactory;

    /**
     * Valores por defecto: el equipo se oculta hasta que el operador decida mostrarlo.
     *
     * @var array<string, bool>
     */
    protected $attributes = [
        'mostrar_nombre' => true,
        'mostrar_pais_ciudad' => true,
        'mostrar_equipo' => false,
        'mostrar_biografia_foto' => true,
    ];

    protected function casts(): array
    {
        return [
            'mostrar_nombre' => 'boolean',
            'mostrar_pais_ciudad' => 'boolean',
            'mostrar_equipo' => 'boolean',
            'mostrar_biografia_foto' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
