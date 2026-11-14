<?php

namespace App\Models;

use App\Rules\IndicativoValido;
use Database\Factories\PrefijoPaisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Prefijo de indicativo y el país al que corresponde (HC → Ecuador, LU → Argentina).
 */
#[Table('prefijos_pais')]
#[Fillable(['prefijo', 'pais', 'codigo_bandera', 'continente', 'latitud', 'longitud'])]
class PrefijoPais extends Model
{
    /** @use HasFactory<PrefijoPaisFactory> */
    use HasFactory;

    /**
     * Sufijos que indican tipo de operación, no país (portátil, móvil, marítimo...).
     */
    private const SUFIJOS_OPERACION = ['P', 'M', 'MM', 'AM', 'QRP', 'A'];

    protected function casts(): array
    {
        return [
            'latitud' => 'float',
            'longitud' => 'float',
        ];
    }

    /**
     * Busca el país de un indicativo usando el prefijo conocido más largo que coincida.
     *
     * Si el indicativo opera desde otro país (EA8/HC1QRC) se usa el prefijo antepuesto.
     */
    public static function paraIndicativo(string $indicativo): ?self
    {
        $base = self::parteQueIndicaPais(IndicativoValido::normalizar($indicativo));

        if ($base === '') {
            return null;
        }

        $candidatos = [];
        for ($largo = min(strlen($base), 5); $largo >= 1; $largo--) {
            $candidatos[] = substr($base, 0, $largo);
        }

        return self::query()
            ->whereIn('prefijo', $candidatos)
            ->orderByRaw('CHAR_LENGTH(prefijo) DESC')
            ->first();
    }

    /**
     * Código de bandera de un país escrito a mano (el campo «país» del perfil), sin importar
     * mayúsculas ni espacios extra: «ecuador» → ec.
     */
    public static function codigoBanderaDePais(string $pais): ?string
    {
        return self::query()
            ->whereRaw('LOWER(pais) = ?', [mb_strtolower(trim($pais))])
            ->whereNotNull('codigo_bandera')
            ->value('codigo_bandera');
    }

    private static function parteQueIndicaPais(string $indicativo): string
    {
        $partes = array_values(array_filter(
            explode('/', $indicativo),
            fn (string $parte): bool => $parte !== ''
                && ! ctype_digit($parte)
                && ! in_array($parte, self::SUFIJOS_OPERACION, true),
        ));

        if (count($partes) <= 1) {
            return $partes[0] ?? '';
        }

        usort($partes, fn (string $a, string $b): int => strlen($a) <=> strlen($b));

        return $partes[0];
    }
}
