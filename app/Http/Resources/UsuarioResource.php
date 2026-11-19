<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El operador con sesión iniciada, tal como lo recibe la SPA.
 *
 * @mixin User
 */
class UsuarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'indicativo' => $this->indicativo,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'nombre_completo' => $this->nombre_completo,
            'email' => $this->email,
            'correo_verificado' => $this->hasVerifiedEmail(),
            'pais' => $this->pais,
            'codigo_bandera' => $this->codigoBandera(),
            'foto_url' => $this->foto_url,
            'segundo_factor' => [
                'app' => $this->hasEnabledTwoFactorAuthentication(),
                'correo' => (bool) $this->two_factor_email_enabled,
            ],
        ];
    }
}
