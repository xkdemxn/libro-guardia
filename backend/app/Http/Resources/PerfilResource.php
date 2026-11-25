<?php

namespace App\Http\Resources;

use App\Models\PrefijoPais;
use App\Models\User;
use App\Services\SegundoFactor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Todo lo que necesita la pantalla «Mi perfil» para editarse (US-13).
 *
 * @mixin User
 */
class PerfilResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $visibilidad = $this->visibilidad;
        $segundoFactor = app(SegundoFactor::class);

        return [
            ...(new UsuarioResource($this->resource))->toArray($request),
            'provincia' => $this->provincia,
            'ciudad' => $this->ciudad,
            'pais_prefijo' => PrefijoPais::paraIndicativo($this->indicativo)?->pais,
            'pais_publico' => $this->datosPublicos()['pais'],
            'grid_locator' => $this->grid_locator,
            'equipo_radio' => $this->equipo_radio,
            'antena' => $this->antena,
            'biografia' => $this->biografia,
            'tiene_foto' => $this->foto_path !== null,
            'portada' => [
                'url' => $this->portada_url,
                'estilo' => $this->portada_estilo,
                'opacidad' => (int) $this->portada_opacidad,
            ],
            'visibilidad' => [
                'mostrar_nombre' => $visibilidad->mostrar_nombre,
                'mostrar_pais_ciudad' => $visibilidad->mostrar_pais_ciudad,
                'mostrar_equipo' => $visibilidad->mostrar_equipo,
                'mostrar_biografia_foto' => $visibilidad->mostrar_biografia_foto,
            ],
            'envio_correo_disponible' => $segundoFactor->envioCorreoDisponible(),
        ];
    }
}
