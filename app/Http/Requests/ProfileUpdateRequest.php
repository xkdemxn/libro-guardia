<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Datos del perfil del operador (US-13). El indicativo no se puede cambiar, por eso no está aquí.
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * Tamaño máximo de la foto de perfil en kilobytes.
     */
    public const FOTO_MAXIMO_KB = 2048;

    /**
     * Tamaño máximo de la portada en kilobytes (el navegador ya la reduce antes de subirla).
     */
    public const PORTADA_MAXIMO_KB = 4096;

    /**
     * Estilos de portada y de foto de la tarjeta QSL: degradado hasta la mitad, o la tarjeta completa con opacidad.
     */
    public const ESTILOS_PORTADA = ['degradado', 'completa'];

    protected function prepareForValidation(): void
    {
        if ($this->filled('grid_locator')) {
            $this->merge(['grid_locator' => strtoupper(trim($this->input('grid_locator')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'pais' => ['nullable', 'string', 'max:100'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'grid_locator' => ['nullable', 'regex:/^[A-R]{2}[0-9]{2}([A-X]{2})?$/'],
            'equipo_radio' => ['nullable', 'string', 'max:255'],
            'antena' => ['nullable', 'string', 'max:255'],
            'biografia' => ['nullable', 'string', 'max:1000'],
            'foto' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp'])->max(self::FOTO_MAXIMO_KB)],
            'quitar_foto' => ['nullable', 'boolean'],
            'portada' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp'])->max(self::PORTADA_MAXIMO_KB)],
            'quitar_portada' => ['nullable', 'boolean'],
            'portada_estilo' => ['sometimes', Rule::in(self::ESTILOS_PORTADA)],
            'portada_opacidad' => ['sometimes', 'integer', 'between:10,50'],
            'mostrar_nombre' => ['nullable', 'boolean'],
            'mostrar_pais_ciudad' => ['nullable', 'boolean'],
            'mostrar_equipo' => ['nullable', 'boolean'],
            'mostrar_biografia_foto' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pais' => 'país',
            'equipo_radio' => 'equipo de radio',
            'biografia' => 'biografía',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'foto.mimes' => 'La foto debe ser JPG, PNG o WEBP.',
            'foto.max' => 'La foto no puede pesar más de 2 MB.',
            'portada.mimes' => 'La portada debe ser JPG, PNG o WEBP.',
            'portada.max' => 'La portada no puede pesar más de 4 MB.',
            'portada_estilo.in' => 'Elige «Degradado» o «Tarjeta completa».',
            'portada_opacidad.between' => 'La opacidad debe estar entre 10 % y 50 %.',
            'grid_locator.regex' => 'El grid locator debe tener el formato FI09 o FI09AA.',
        ];
    }

    /**
     * Datos del operador sin las imágenes (foto y portada) ni las opciones de visibilidad.
     *
     * @return array<string, mixed>
     */
    public function datosOperador(): array
    {
        return $this->safe()->only([
            'nombres', 'apellidos', 'email', 'pais', 'ciudad', 'grid_locator', 'equipo_radio', 'antena', 'biografia',
            'portada_estilo', 'portada_opacidad',
        ]);
    }

    /**
     * Las casillas sin marcar no se envían, así que se interpretan como "no mostrar".
     *
     * @return array<string, bool>
     */
    public function visibilidad(): array
    {
        return [
            'mostrar_nombre' => $this->boolean('mostrar_nombre'),
            'mostrar_pais_ciudad' => $this->boolean('mostrar_pais_ciudad'),
            'mostrar_equipo' => $this->boolean('mostrar_equipo'),
            'mostrar_biografia_foto' => $this->boolean('mostrar_biografia_foto'),
        ];
    }
}
