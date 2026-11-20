<?php

namespace App\Http\Requests;

use App\Enums\TipoQsl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Estado de QSL de un contacto por cada medio (US-10). Enviada y recibida se marcan por separado.
 */
class ConfirmacionQslRequest extends FormRequest
{
    /**
     * Medios que el operador marca a mano. La confirmación interna la pone el sistema.
     *
     * @return list<TipoQsl>
     */
    public static function tiposManuales(): array
    {
        return [TipoQsl::Tarjeta, TipoQsl::Eqsl, TipoQsl::Lotw];
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $reglas = [];

        foreach (self::tiposManuales() as $tipo) {
            $prefijo = "qsl.{$tipo->value}";
            $reglas["{$prefijo}.enviada"] = ['nullable', 'boolean'];
            $reglas["{$prefijo}.recibida"] = ['nullable', 'boolean'];
            $reglas["{$prefijo}.fecha_envio"] = ['nullable', "required_if_accepted:{$prefijo}.enviada", 'date_format:Y-m-d'];
            $reglas["{$prefijo}.fecha_recepcion"] = ['nullable', "required_if_accepted:{$prefijo}.recibida", 'date_format:Y-m-d'];
        }

        return $reglas;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'qsl.*.fecha_envio.required_if_accepted' => __('Indica la fecha de envío.'),
            'qsl.*.fecha_recepcion.required_if_accepted' => __('Indica la fecha de recepción.'),
        ];
    }

    /**
     * Estado pedido para un medio, listo para guardar (las fechas solo cuentan si la casilla está marcada).
     *
     * @return array{enviada: bool, fecha_envio: ?string, recibida: bool, fecha_recepcion: ?string}
     */
    public function estadoPara(TipoQsl $tipo): array
    {
        $enviada = $this->boolean("qsl.{$tipo->value}.enviada");
        $recibida = $this->boolean("qsl.{$tipo->value}.recibida");

        return [
            'enviada' => $enviada,
            'fecha_envio' => $enviada ? $this->input("qsl.{$tipo->value}.fecha_envio") : null,
            'recibida' => $recibida,
            'fecha_recepcion' => $recibida ? $this->input("qsl.{$tipo->value}.fecha_recepcion") : null,
        ];
    }
}
