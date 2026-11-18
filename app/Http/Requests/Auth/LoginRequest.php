<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\IndicativoValido;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Inicio de sesión con indicativo o correo (US-02).
 */
class LoginRequest extends FormRequest
{
    /**
     * Intentos fallidos permitidos antes de bloquear el acceso.
     */
    public const MAXIMO_INTENTOS = 5;

    /**
     * Segundos que dura el bloqueo tras superar los intentos permitidos.
     */
    public const SEGUNDOS_BLOQUEO = 300;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['login' => 'indicativo o correo'];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function validarCredenciales(): User
    {
        $this->ensureIsNotRateLimited();

        $guard = Auth::guard('web');
        $credenciales = $this->credenciales();

        if (! $guard->validate($credenciales)) {
            event(new Failed('web', $guard->getLastAttempted(), $credenciales));
            RateLimiter::hit($this->throttleKey(), self::SEGUNDOS_BLOQUEO);

            throw ValidationException::withMessages([
                'login' => 'Indicativo o contraseña incorrectos.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $guard->getLastAttempted();
    }

    /**
     * Si el usuario escribió un correo se busca por correo; si no, por indicativo.
     *
     * @return array{email?: string, indicativo?: string, password: string}
     */
    public function credenciales(): array
    {
        $login = trim($this->string('login'));

        if (str_contains($login, '@')) {
            return ['email' => Str::lower($login), 'password' => $this->string('password')->value()];
        }

        return ['indicativo' => IndicativoValido::normalizar($login), 'password' => $this->string('password')->value()];
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAXIMO_INTENTOS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $credenciales = $this->credenciales();

        return Str::transliterate(Str::lower($credenciales['email'] ?? $credenciales['indicativo']).'|'.$this->ip());
    }
}
