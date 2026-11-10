<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros del libro de guardia, compartidos por el listado (US-09) y la exportación (US-11, US-12).
 */
class FiltroContactosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function reglas(): array
    {
        return [
            'indicativo' => ['nullable', 'string', 'max:20'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'banda_id' => ['nullable', 'integer', 'exists:bandas,id'],
            'modo_id' => ['nullable', 'integer', 'exists:modos,id'],
            'qsl' => ['nullable', 'in:confirmado,enviada,pendiente'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function mensajes(): array
    {
        return [
            'hasta.after_or_equal' => 'La fecha de inicio no puede ser mayor que la fecha de fin.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...self::reglas(),
            'formato' => ['required', 'in:csv,adif'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::mensajes();
    }

    /**
     * Solo los filtros (sin el formato), para el scope Contacto::filtrar().
     *
     * @return array<string, mixed>
     */
    public function filtros(): array
    {
        return $this->safe()->except('formato');
    }
}
