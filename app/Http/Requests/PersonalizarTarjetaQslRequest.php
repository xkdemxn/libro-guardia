<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Personalización de las tarjetas QSL del operador: foto (estilo y opacidad) y mensaje del pie.
 * Se aplica a todas sus tarjetas.
 */
class PersonalizarTarjetaQslRequest extends FormRequest
{
    public const MENSAJE_MAXIMO = 150;

    protected function prepareForValidation(): void
    {
        // Saltos de línea de Windows (\r\n) cuentan como uno solo, igual que en el contador del navegador.
        $mensaje = str_replace("\r\n", "\n", trim((string) $this->input('qsl_mensaje')));
        $this->merge(['qsl_mensaje' => $mensaje ?: null]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'qsl_foto' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp'])->max(ProfileUpdateRequest::PORTADA_MAXIMO_KB)],
            'quitar_qsl_foto' => ['nullable', 'boolean'],
            'qsl_foto_estilo' => ['sometimes', Rule::in(ProfileUpdateRequest::ESTILOS_PORTADA)],
            'qsl_foto_opacidad' => ['sometimes', 'integer', 'between:10,100'],
            'qsl_mensaje' => ['nullable', 'string', 'max:'.self::MENSAJE_MAXIMO],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'qsl_foto.mimes' => 'La foto debe ser JPG, PNG o WEBP.',
            'qsl_foto.max' => 'La foto no puede pesar más de 4 MB.',
            'qsl_foto_estilo.in' => 'Elige «Foto a la derecha» o «Fondo completo».',
            'qsl_foto_opacidad.between' => 'La opacidad debe estar entre 10 % y 100 %.',
            'qsl_mensaje.max' => 'El mensaje puede tener hasta '.self::MENSAJE_MAXIMO.' caracteres.',
        ];
    }
}
