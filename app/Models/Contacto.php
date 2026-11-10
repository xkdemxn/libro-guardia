<?php

namespace App\Models;

use App\Rules\IndicativoValido;
use App\Services\Maidenhead;
use Carbon\CarbonInterface;
use Database\Factories\ContactoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Contacto de radio (QSO) registrado en el libro de guardia de un operador.
 */
#[Fillable([
    'estacion_llamada', 'inicio_utc', 'fin_utc', 'frecuencia_mhz', 'banda_id', 'modo_id',
    'prefijo_pais_id', 'reporte_enviado', 'reporte_recibido', 'potencia_w', 'grid_locator',
    'nombre_operador', 'equipo', 'activacion', 'observaciones',
])]
class Contacto extends Model
{
    /** @use HasFactory<ContactoFactory> */
    use HasFactory;

    /**
     * Minutos de diferencia dentro de los cuales un contacto igual se considera posible duplicado.
     */
    public const MINUTOS_DUPLICADO = 10;

    protected function casts(): array
    {
        return [
            'inicio_utc' => 'datetime',
            'rechazado_por_contraparte_at' => 'datetime',
            'fin_utc' => 'datetime',
            'frecuencia_mhz' => 'float',
            'potencia_w' => 'float',
        ];
    }

    /**
     * El indicativo de la estación contactada siempre se guarda en mayúsculas y sin espacios.
     */
    protected function estacionLlamada(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): string => IndicativoValido::normalizar($value),
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Banda, $this>
     */
    public function banda(): BelongsTo
    {
        return $this->belongsTo(Banda::class);
    }

    /**
     * @return BelongsTo<Modo, $this>
     */
    public function modo(): BelongsTo
    {
        return $this->belongsTo(Modo::class);
    }

    /**
     * @return BelongsTo<PrefijoPais, $this>
     */
    public function prefijoPais(): BelongsTo
    {
        return $this->belongsTo(PrefijoPais::class);
    }

    /**
     * @return HasMany<ConfirmacionQsl, $this>
     */
    public function confirmacionesQsl(): HasMany
    {
        return $this->hasMany(ConfirmacionQsl::class);
    }

    /**
     * Ubicación de la otra estación: su grid locator si se anotó; si no, el país de su prefijo.
     *
     * @return array{lat: float, lon: float, precision: string}|null
     */
    public function coordenadas(): ?array
    {
        $porGrid = Maidenhead::aCoordenadas($this->grid_locator);

        if ($porGrid !== null) {
            return [...$porGrid, 'precision' => 'grid'];
        }

        if ($this->prefijoPais?->latitud === null) {
            return null;
        }

        return ['lat' => $this->prefijoPais->latitud, 'lon' => $this->prefijoPais->longitud, 'precision' => 'pais'];
    }

    /**
     * Distancia aproximada en km entre la estación del operador y la contactada.
     */
    public function distanciaKm(): ?int
    {
        $origen = $this->user->coordenadas();
        $destino = $this->coordenadas();

        if ($origen === null || $destino === null) {
            return null;
        }

        return (int) round(Maidenhead::distanciaKm($origen['lat'], $origen['lon'], $destino['lat'], $destino['lon']));
    }

    /**
     * Estado de QSL para mostrar en el listado: confirmado, enviada o pendiente.
     */
    public function estadoQsl(): string
    {
        if ($this->confirmacionesQsl->contains('recibida', true)) {
            return 'confirmado';
        }

        if ($this->confirmacionesQsl->contains('enviada', true)) {
            return 'enviada';
        }

        return 'pendiente';
    }

    /**
     * Aplica los filtros del listado: indicativo parcial, rango de fechas, banda, modo y estado de QSL.
     *
     * @param  Builder<Contacto>  $query
     * @param  array{indicativo?: ?string, desde?: ?string, hasta?: ?string, banda_id?: ?int, modo_id?: ?int, qsl?: ?string}  $filtros
     */
    #[Scope]
    protected function filtrar(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['indicativo'] ?? null, function (Builder $query, string $indicativo): void {
                $query->where('estacion_llamada', 'like', '%'.IndicativoValido::normalizar($indicativo).'%');
            })
            ->when($filtros['desde'] ?? null, fn (Builder $query, string $desde) => $query->where('inicio_utc', '>=', $desde.' 00:00:00'))
            ->when($filtros['hasta'] ?? null, fn (Builder $query, string $hasta) => $query->where('inicio_utc', '<=', $hasta.' 23:59:59'))
            ->when($filtros['banda_id'] ?? null, fn (Builder $query, int|string $bandaId) => $query->where('banda_id', $bandaId))
            ->when($filtros['modo_id'] ?? null, fn (Builder $query, int|string $modoId) => $query->where('modo_id', $modoId))
            ->when($filtros['qsl'] ?? null, function (Builder $query, string $estado): void {
                match ($estado) {
                    'confirmado' => $query->whereHas('confirmacionesQsl', fn (Builder $q) => $q->where('recibida', true)),
                    'enviada' => $query
                        ->whereHas('confirmacionesQsl', fn (Builder $q) => $q->where('enviada', true))
                        ->whereDoesntHave('confirmacionesQsl', fn (Builder $q) => $q->where('recibida', true)),
                    'pendiente' => $query->whereDoesntHave('confirmacionesQsl', fn (Builder $q) => $q->where('enviada', true)->orWhere('recibida', true)),
                    default => null,
                };
            });
    }

    /**
     * Contactos con la misma estación, banda y modo a pocos minutos de distancia.
     *
     * @param  Builder<Contacto>  $query
     */
    #[Scope]
    protected function posiblesDuplicados(
        Builder $query,
        string $estacionLlamada,
        int $bandaId,
        int $modoId,
        CarbonInterface $inicioUtc,
        ?int $exceptoId = null,
    ): void {
        $query
            ->where('estacion_llamada', IndicativoValido::normalizar($estacionLlamada))
            ->where('banda_id', $bandaId)
            ->where('modo_id', $modoId)
            ->whereBetween('inicio_utc', [
                $inicioUtc->copy()->subMinutes(self::MINUTOS_DUPLICADO),
                $inicioUtc->copy()->addMinutes(self::MINUTOS_DUPLICADO),
            ])
            ->when($exceptoId, fn (Builder $query, int $id) => $query->whereKeyNot($id));
    }
}
