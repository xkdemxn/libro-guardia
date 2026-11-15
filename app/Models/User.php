<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\TipoQsl;
use App\Enums\ZonaEcuador;
use App\Rules\IndicativoValido;
use App\Services\Maidenhead;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

/**
 * Operador: radioaficionado registrado dueño de un libro de guardia.
 */
#[Fillable([
    'indicativo', 'nombres', 'apellidos', 'pais', 'ciudad', 'grid_locator',
    'equipo_radio', 'antena', 'biografia', 'email', 'password', 'portada_estilo', 'portada_opacidad',
    'qsl_foto_estilo', 'qsl_foto_opacidad', 'qsl_mensaje',
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
     * URL pública de la foto de perfil, o null si no tiene.
     */
    protected function fotoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->foto_path ? Storage::disk('public')->url($this->foto_path) : null,
        );
    }

    /**
     * URL pública de la portada (fondo de la tarjeta del perfil público), o null si no tiene.
     */
    protected function portadaUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->portada_path ? Storage::disk('public')->url($this->portada_path) : null,
        );
    }

    /**
     * Mensaje del pie de las tarjetas QSL cuando el operador no escribió uno propio.
     */
    public const MENSAJE_QSL_PREDETERMINADO = 'Confirmando nuestro QSO. ¡Gracias y 73!';

    public function mensajeQsl(): string
    {
        return $this->qsl_mensaje ?: self::MENSAJE_QSL_PREDETERMINADO;
    }

    /**
     * Reemplaza o quita una imagen del perfil (foto, portada o foto de la tarjeta QSL) en el disco
     * público, borrando la anterior. No guarda el modelo.
     */
    public function cambiarImagen(string $columna, string $carpeta, ?UploadedFile $archivo, bool $quitar): void
    {
        if ($archivo === null && ! $quitar) {
            return;
        }

        if ($this->{$columna} !== null) {
            Storage::disk('public')->delete($this->{$columna});
        }

        $this->{$columna} = $archivo?->store($carpeta, 'public');
    }

    /**
     * URL pública de la foto de las tarjetas QSL, o null si no tiene.
     */
    protected function qslFotoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->qsl_foto_path ? Storage::disk('public')->url($this->qsl_foto_path) : null,
        );
    }

    /**
     * Foto de la tarjeta QSL incrustada como data URI. La tarjeta es un SVG y, al convertirla a PNG
     * en el navegador, un SVG no puede cargar imágenes externas: por eso la foto va dentro.
     *
     * @return array{data: string, estilo: string, opacidad: int}|null
     */
    public function fondoTarjetaQsl(): ?array
    {
        $disco = Storage::disk('public');

        if ($this->qsl_foto_path === null || ! $disco->exists($this->qsl_foto_path)) {
            return null;
        }

        return [
            'data' => 'data:'.$disco->mimeType($this->qsl_foto_path).';base64,'.base64_encode($disco->get($this->qsl_foto_path)),
            'estilo' => $this->qsl_foto_estilo,
            'opacidad' => min(50, max(10, (int) $this->qsl_foto_opacidad)),
        ];
    }

    /**
     * Visibilidad del perfil; si el operador nunca la configuró se usan los valores por defecto.
     *
     * @return HasOne<VisibilidadPerfil, $this>
     */
    public function visibilidad(): HasOne
    {
        return $this->hasOne(VisibilidadPerfil::class)->withDefault();
    }

    /**
     * Ubicación de la estación del operador: su grid locator; si no lo configuró, el país de su indicativo.
     *
     * @return array{lat: float, lon: float}|null
     */
    public function coordenadas(): ?array
    {
        $porGrid = Maidenhead::aCoordenadas($this->grid_locator);

        if ($porGrid !== null) {
            return $porGrid;
        }

        $prefijo = PrefijoPais::paraIndicativo($this->indicativo);

        return $prefijo?->latitud === null ? null : ['lat' => $prefijo->latitud, 'lon' => $prefijo->longitud];
    }

    /**
     * Zona de Ecuador del operador según su indicativo, o null si no es ecuatoriano.
     */
    public function zonaEcuador(): ?ZonaEcuador
    {
        return ZonaEcuador::paraIndicativo($this->indicativo);
    }

    /**
     * Bandera del operador: la del país que escribió en su perfil y, si no lo reconoce
     * (o lo dejó vacío), la que corresponde al prefijo de su indicativo.
     */
    public function codigoBandera(?PrefijoPais $prefijo = null): ?string
    {
        if ($this->pais && ($codigo = PrefijoPais::codigoBanderaDePais($this->pais))) {
            return $codigo;
        }

        return ($prefijo ?? PrefijoPais::paraIndicativo($this->indicativo))?->codigo_bandera;
    }

    /**
     * Datos que cualquier persona puede ver, según lo que el operador decidió mostrar (US-13, US-15).
     *
     * El indicativo y el país siempre son visibles; el correo y los contactos nunca.
     *
     * @return array{indicativo: string, pais: ?string, bandera: ?string, zona: ?string, nombre: ?string, ciudad: ?string, equipo_radio: ?string, antena: ?string, biografia: ?string, foto_url: ?string, portada: ?array{url: string, estilo: string, opacidad: int}}
     */
    public function datosPublicos(): array
    {
        $visibilidad = $this->visibilidad;

        $prefijo = PrefijoPais::paraIndicativo($this->indicativo);

        return [
            'indicativo' => $this->indicativo,
            'pais' => $this->pais ?: $prefijo?->pais,
            'bandera' => $this->codigoBandera($prefijo),
            'zona' => $this->zonaEcuador()?->descripcion(),
            'nombre' => $visibilidad->mostrar_nombre ? $this->nombre_completo : null,
            'ciudad' => $visibilidad->mostrar_pais_ciudad ? $this->ciudad : null,
            'equipo_radio' => $visibilidad->mostrar_equipo ? $this->equipo_radio : null,
            'antena' => $visibilidad->mostrar_equipo ? $this->antena : null,
            'biografia' => $visibilidad->mostrar_biografia_foto ? $this->biografia : null,
            'foto_url' => $visibilidad->mostrar_biografia_foto ? $this->foto_url : null,
            'portada' => $this->portada_url === null ? null : [
                'url' => $this->portada_url,
                'estilo' => $this->portada_estilo,
                'opacidad' => $this->portada_opacidad,
            ],
        ];
    }

    /**
     * @return HasMany<Contacto, $this>
     */
    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }

    /**
     * Solicitudes de confirmación: contactos que otros operadores registraron con este indicativo
     * y que este operador todavía no tiene en su libro (ni rechazó).
     *
     * Si ya los tuviera, estarían confirmados con una QSL interna (ver ConfirmadorQsl).
     *
     * @return Builder<Contacto>
     */
    public function solicitudesPendientes(): Builder
    {
        return Contacto::query()
            ->where('estacion_llamada', $this->indicativo)
            ->where('user_id', '!=', $this->id)
            ->whereNull('rechazado_por_contraparte_at')
            ->whereDoesntHave('confirmacionesQsl', fn (Builder $query) => $query->where('tipo', TipoQsl::Interna));
    }
}
