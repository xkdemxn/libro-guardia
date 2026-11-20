<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\IndicativoValido;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Registro de un nuevo operador (US-01). Lo usan la versión Blade y la API de la SPA.
 */
class RegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['indicativo' => IndicativoValido::normalizar($this->input('indicativo'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'indicativo' => ['required', 'string', 'max:20', new IndicativoValido, 'unique:'.User::class],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['indicativo.unique' => __('Este indicativo ya está registrado.')];
    }

    /**
     * Crea el operador con los datos validados.
     */
    public function crearUsuario(): User
    {
        return User::create($this->safe()->only(['indicativo', 'nombres', 'apellidos', 'email', 'password']));
    }
}
