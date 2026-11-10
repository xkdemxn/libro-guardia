<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Valida un indicativo de radioaficionado (ej. HC1QRC, LU4BB, EA8/HC1QRC, HC1QRC/P).
 */
class IndicativoValido implements ValidationRule
{
    /**
     * Prefijo opcional con barra, indicativo base (prefijo + número + sufijo) y sufijo opcional con barra.
     */
    private const PATRON = '/^([A-Z0-9]{1,4}\/)?[A-Z0-9]{1,3}[0-9][A-Z0-9]{0,3}[A-Z](\/[A-Z0-9]{1,4})?$/';

    /**
     * Pasa el indicativo a mayúsculas y le quita todos los espacios.
     */
    public static function normalizar(?string $indicativo): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $indicativo));
    }

    public static function esValido(?string $indicativo): bool
    {
        return preg_match(self::PATRON, self::normalizar($indicativo)) === 1;
    }

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::esValido($value)) {
            $fail('El :attribute no es un indicativo válido (ej. HC1QRC).');
        }
    }
}
