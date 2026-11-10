<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Rules\IndicativoValido;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Operador: radioaficionado registrado dueño de un libro de guardia.
 */
#[Fillable([
    'indicativo', 'nombres', 'apellidos', 'pais', 'ciudad', 'grid_locator',
    'equipo_radio', 'antena', 'biografia', 'email', 'password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * El indicativo siempre se guarda en mayúsculas y sin espacios.
     */
    protected function indicativo(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): string => IndicativoValido::normalizar($value),
        );
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim("{$this->nombres} {$this->apellidos}"),
        );
    }

    /**
     * @return HasMany<Contacto, $this>
     */
    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }
}
