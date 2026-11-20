<?php

namespace App\Http\Requests;

use App\Models\PrefijoPais;
use App\Models\User;
use App\Support\Ubicaciones;
use Closure;
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

        // La provincia solo existe para Ecuador.
        if (! Ubicaciones::esEcuador($this->paisEfectivo())) {
            $this->merge(['provincia' => null]);
        }
    }

    /**
     * País elegido o, si se dejó vacío, el que corresponde al prefijo del indicativo.
     */
    public function paisEfectivo(): ?string
    {
        return $this->input('pais') ?: PrefijoPais::paraIndicativo($this->user()->indicativo)?->pais;
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
            'pais' => ['nullable', 'string', $this->deLaLista(Ubicaciones::paises(), $this->user()->pais)],
            'provincia' => ['nullable', 'string', Rule::in(Ubicaciones::provincias())],
            'ciudad' => ['nullable', 'string', $this->deLaLista(
                Ubicaciones::ciudades($this->paisEfectivo(), $this->input('provincia')),
                $this->user()->ciudad,
            )],
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
            'pais' => __('país'),
            'equipo_radio' => __('equipo de radio'),
            'biografia' => __('biografía'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'foto.mimes' => __('La foto debe ser JPG, PNG o WEBP.'),
            'foto.max' => __('La foto no puede pesar más de 2 MB.'),
            'portada.mimes' => __('La portada debe ser JPG, PNG o WEBP.'),
            'portada.max' => __('La portada no puede pesar más de 4 MB.'),
            'portada_estilo.in' => __('Elige «Degradado» o «Tarjeta completa».'),
            'portada_opacidad.between' => __('La opacidad debe estar entre 10 % y 50 %.'),
            'provincia.in' => __('Elige una provincia de la lista.'),
            'grid_locator.regex' => __('El grid locator debe tener el formato FI09 o FI09AA.'),
        ];
    }

    /**
     * Datos del operador sin las imágenes (foto y portada) ni las opciones de visibilidad.
     *
     * @return array<string, mixed>
     */
    public function datosOperador(): array
    {
        $datos = $this->safe()->only([
            'nombres', 'apellidos', 'email', 'pais', 'provincia', 'ciudad', 'grid_locator', 'equipo_radio', 'antena', 'biografia',
            'portada_estilo', 'portada_opacidad',
        ]);

        // Un cantón ecuatoriano elegido sin provincia: se completa con la suya.
        if (empty($datos['provincia']) && ! empty($datos['ciudad']) && Ubicaciones::esEcuador($this->paisEfectivo())) {
            $datos['provincia'] = Ubicaciones::provinciaDe($datos['ciudad']);
        }

        return $datos;
    }

    /**
     * El valor tiene que salir del desplegable; se acepta también el que ya estaba guardado
     * (perfiles escritos a mano antes de que existieran las listas).
     *
     * @param  list<string>  $opciones
     */
    private function deLaLista(array $opciones, ?string $guardado): Closure
    {
        return function (string $atributo, mixed $valor, Closure $fallar) use ($opciones, $guardado): void {
            if (! in_array($valor, $opciones, true) && $valor !== $guardado) {
                $fallar($atributo === 'pais' ? __('Elige un país de la lista.') : __('Elige una ciudad de la lista.'));
            }
        };
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
