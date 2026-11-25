<?php

namespace App\Http\Resources;

use App\Enums\TipoQsl;
use App\Http\Requests\ContactoRequest;
use App\Models\ConfirmacionQsl;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un contacto del libro de guardia. Con `detalle()` incluye además las confirmaciones QSL,
 * la distancia y lo que necesita el formulario de edición.
 *
 * @mixin Contacto
 */
class ContactoResource extends JsonResource
{
    private bool $conDetalle = false;

    public function detalle(): static
    {
        $this->conDetalle = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $zona = $this->zonaEcuador();

        return [
            'id' => $this->id,
            'estacion_llamada' => $this->estacion_llamada,
            'fecha' => $this->inicio_utc->format('Y-m-d'),
            'hora_inicio' => $this->inicio_utc->format('H:i'),
            'hora_fin' => $this->fin_utc?->format('H:i'),
            'frecuencia_mhz' => $this->frecuencia_mhz,
            'banda' => ['id' => $this->banda->id, 'nombre' => $this->banda->nombre],
            'modo' => ['id' => $this->modo->id, 'nombre' => $this->modo->nombre, 'etiqueta' => $this->modo->etiqueta],
            'pais' => $this->prefijoPais ? ['nombre' => $this->prefijoPais->pais, 'codigo_bandera' => $this->prefijoPais->codigo_bandera] : null,
            'zona' => $zona ? ['nombre' => $zona->nombre(), 'descripcion' => $zona->descripcion()] : null,
            'reporte_enviado' => $this->reporte_enviado,
            'reporte_recibido' => $this->reporte_recibido,
            'estado_qsl' => $this->estadoQsl(),
            $this->mergeWhen($this->conDetalle, fn (): array => [
                'potencia_w' => $this->potencia_w,
                'grid_locator' => $this->grid_locator,
                'nombre_operador' => $this->nombre_operador,
                'equipo' => $this->equipo,
                'activacion' => $this->activacion,
                'observaciones' => $this->observaciones,
                'distancia_km' => $this->distanciaKm(),
                'es_usuario' => User::query()->where('indicativo', $this->estacion_llamada)->exists(),
                'rechazado_por_contraparte' => $this->rechazado_por_contraparte_at?->format('Y-m-d'),
                'confirmado_internamente' => $this->confirmacionesQsl->contains('tipo', TipoQsl::Interna),
                'confirmaciones' => collect(TipoQsl::cases())
                    ->reject(fn (TipoQsl $tipo): bool => $tipo === TipoQsl::Interna)
                    ->mapWithKeys(function (TipoQsl $tipo): array {
                        /** @var ConfirmacionQsl|null $confirmacion */
                        $confirmacion = $this->confirmacionesQsl->firstWhere('tipo', $tipo);

                        return [$tipo->value => [
                            'etiqueta' => $tipo->etiqueta(),
                            'enviada' => (bool) $confirmacion?->enviada,
                            'fecha_envio' => $confirmacion?->fecha_envio?->format('Y-m-d'),
                            'recibida' => (bool) $confirmacion?->recibida,
                            'fecha_recepcion' => $confirmacion?->fecha_recepcion?->format('Y-m-d'),
                        ]];
                    }),
                'fecha_minima' => ContactoRequest::fechaMinima($this->resource),
            ]),
        ];
    }
}
