<?php

namespace Database\Seeders;

use App\Models\PrefijoPais;
use Illuminate\Database\Seeder;

/**
 * Prefijos de indicativo de los países más frecuentes, con continente y coordenadas
 * aproximadas (capital o centro de la región) para el mapa y las estadísticas.
 *
 * Al buscar un país se usa el prefijo más largo que coincida, así KP4 (Puerto Rico)
 * gana sobre K (Estados Unidos) y EA8 (Canarias) sobre EA (España).
 */
class PrefijoPaisSeeder extends Seeder
{
    /**
     * @var list<array{0: string, 1: string, 2: string, 3: float, 4: float, 5: list<string>}> país, bandera, continente, latitud, longitud, prefijos
     */
    private const PAISES = [
        // Sudamérica
        ['Ecuador', 'ec', 'SA', -0.18, -78.47, ['HC', 'HD']],
        ['Islas Galápagos', 'ec', 'SA', -0.74, -90.31, ['HC8', 'HD8']],
        ['Argentina', 'ar', 'SA', -34.60, -58.38, ['LU', 'LW', 'LO', 'LP', 'LQ', 'LR', 'LS', 'LT', 'AY', 'AZ']],
        ['Brasil', 'br', 'SA', -15.79, -47.88, ['PY', 'PP', 'PQ', 'PR', 'PS', 'PT', 'PU', 'PV', 'PW', 'PX', 'ZV', 'ZW', 'ZX', 'ZY', 'ZZ']],
        ['Chile', 'cl', 'SA', -33.45, -70.67, ['CE', 'CA', 'CB', 'CC', 'CD', 'XQ', 'XR', '3G']],
        ['Colombia', 'co', 'SA', 4.71, -74.07, ['HK', 'HJ', '5J', '5K']],
        ['Perú', 'pe', 'SA', -12.05, -77.04, ['OA', 'OB', 'OC', '4T']],
        ['Venezuela', 've', 'SA', 10.49, -66.88, ['YV', 'YW', 'YX', 'YY', '4M']],
        ['Bolivia', 'bo', 'SA', -16.50, -68.15, ['CP']],
        ['Uruguay', 'uy', 'SA', -34.90, -56.16, ['CX', 'CV', 'CW']],
        ['Paraguay', 'py', 'SA', -25.26, -57.58, ['ZP']],
        // Norteamérica, Centroamérica y Caribe
        ['Estados Unidos', 'us', 'NA', 38.90, -77.04, ['K', 'W', 'N', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AI', 'AJ', 'AK']],
        ['Alaska', 'us', 'NA', 61.22, -149.90, ['KL7', 'AL7', 'NL7', 'WL7']],
        ['Hawái', 'us', 'OC', 21.31, -157.86, ['KH6', 'AH6', 'NH6', 'WH6']],
        ['Puerto Rico', 'pr', 'NA', 18.47, -66.11, ['KP3', 'KP4', 'NP4', 'WP4']],
        ['Canadá', 'ca', 'NA', 45.42, -75.70, ['VE', 'VA', 'VO', 'VY', 'CY']],
        ['México', 'mx', 'NA', 19.43, -99.13, ['XE', 'XF', '4A', '6D', '6E', '6F', '6G', '6H', '6I', '6J']],
        ['Cuba', 'cu', 'NA', 23.11, -82.37, ['CO', 'CM', 'CL', 'T4']],
        ['República Dominicana', 'do', 'NA', 18.49, -69.93, ['HI']],
        ['Panamá', 'pa', 'NA', 8.98, -79.52, ['HP', 'HO', '3E', '3F']],
        ['Costa Rica', 'cr', 'NA', 9.93, -84.08, ['TI', 'TE']],
        ['Guatemala', 'gt', 'NA', 14.63, -90.51, ['TG', 'TD']],
        ['Honduras', 'hn', 'NA', 14.07, -87.19, ['HR', 'HQ']],
        ['El Salvador', 'sv', 'NA', 13.69, -89.22, ['YS', 'HU']],
        ['Nicaragua', 'ni', 'NA', 12.11, -86.24, ['YN', 'H6', 'H7']],
        // Europa
        ['España', 'es', 'EU', 40.42, -3.70, ['EA', 'EB', 'EC', 'ED', 'EE', 'EF', 'EG', 'EH', 'AM', 'AN', 'AO']],
        ['Islas Baleares', 'es', 'EU', 39.57, 2.65, ['EA6', 'EB6', 'EC6', 'ED6', 'EE6', 'EF6', 'EG6', 'EH6']],
        ['Islas Canarias', 'es', 'AF', 28.12, -15.43, ['EA8', 'EB8', 'EC8', 'ED8', 'EE8', 'EF8', 'EG8', 'EH8']],
        ['Portugal', 'pt', 'EU', 38.72, -9.14, ['CT', 'CS', 'CQ']],
        ['Francia', 'fr', 'EU', 48.86, 2.35, ['F', 'TM']],
        ['Alemania', 'de', 'EU', 52.52, 13.40, ['DA', 'DB', 'DC', 'DD', 'DF', 'DG', 'DH', 'DJ', 'DK', 'DL', 'DM', 'DN', 'DO', 'DP', 'DQ', 'DR']],
        ['Italia', 'it', 'EU', 41.90, 12.50, ['I']],
        ['Inglaterra', 'gb-eng', 'EU', 51.51, -0.13, ['G', 'M', '2E']],
        ['Escocia', 'gb-sct', 'EU', 55.95, -3.19, ['GM', 'MM', '2M']],
        ['Gales', 'gb-wls', 'EU', 51.48, -3.18, ['GW', 'MW', '2W']],
        ['Irlanda del Norte', 'gb-nir', 'EU', 54.60, -5.93, ['GI', 'MI', '2I']],
        ['Irlanda', 'ie', 'EU', 53.35, -6.26, ['EI', 'EJ']],
        ['Países Bajos', 'nl', 'EU', 52.37, 4.90, ['PA', 'PB', 'PC', 'PD', 'PE', 'PF', 'PG', 'PH', 'PI']],
        ['Bélgica', 'be', 'EU', 50.85, 4.35, ['ON', 'OO', 'OP', 'OQ', 'OR', 'OS', 'OT']],
        ['Suiza', 'ch', 'EU', 46.95, 7.45, ['HB']],
        ['Austria', 'at', 'EU', 48.21, 16.37, ['OE']],
        ['República Checa', 'cz', 'EU', 50.08, 14.44, ['OK', 'OL']],
        ['Polonia', 'pl', 'EU', 52.23, 21.01, ['SP', 'SQ', 'SO', 'SN', '3Z', 'HF']],
        ['Hungría', 'hu', 'EU', 47.50, 19.04, ['HA', 'HG']],
        ['Rumania', 'ro', 'EU', 44.43, 26.10, ['YO', 'YP', 'YQ', 'YR']],
        ['Grecia', 'gr', 'EU', 37.98, 23.73, ['SV', 'SW', 'SX', 'SY', 'SZ', 'J4']],
        ['Dinamarca', 'dk', 'EU', 55.68, 12.57, ['OZ', 'OU', 'OV', '5P', '5Q']],
        ['Suecia', 'se', 'EU', 59.33, 18.07, ['SM', 'SA', 'SB', 'SC', 'SD', 'SE', 'SF', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', '7S', '8S']],
        ['Noruega', 'no', 'EU', 59.91, 10.75, ['LA', 'LB', 'LC', 'LD', 'LE', 'LF', 'LG', 'LH', 'LI', 'LJ', 'LK', 'LL', 'LM', 'LN']],
        ['Finlandia', 'fi', 'EU', 60.17, 24.94, ['OH', 'OF', 'OG', 'OI']],
        ['Ucrania', 'ua', 'EU', 50.45, 30.52, ['UR', 'US', 'UT', 'UV', 'UW', 'UX', 'UY', 'UZ', 'EM', 'EN', 'EO']],
        ['Rusia europea', 'ru', 'EU', 55.76, 37.62, ['R', 'UA', 'UB', 'UC', 'UD', 'UE', 'UF', 'UG', 'UH', 'UI']],
        ['Rusia asiática', 'ru', 'AS', 55.03, 82.92, ['UA9', 'UA0', 'R9', 'R0', 'RA9', 'RA0']],
        // Asia y Oceanía
        ['Turquía', 'tr', 'AS', 39.93, 32.86, ['TA', 'TB', 'TC', 'YM']],
        ['Israel', 'il', 'AS', 31.78, 35.22, ['4X', '4Z']],
        ['Japón', 'jp', 'AS', 35.68, 139.69, ['JA', 'JE', 'JF', 'JG', 'JH', 'JI', 'JJ', 'JK', 'JL', 'JM', 'JN', 'JO', 'JP', 'JQ', 'JR', 'JS', '7J', '7K', '7L', '7M', '7N']],
        ['China', 'cn', 'AS', 39.90, 116.40, ['BY', 'BA', 'BD', 'BG', 'BH', 'BI']],
        ['Corea del Sur', 'kr', 'AS', 37.57, 126.98, ['HL', 'DS', 'DT', '6K', '6L', '6M', '6N']],
        ['India', 'in', 'AS', 28.61, 77.21, ['VU', 'AT']],
        ['Tailandia', 'th', 'AS', 13.76, 100.50, ['HS', 'E2']],
        ['Filipinas', 'ph', 'OC', 14.60, 120.98, ['DU', 'DV', 'DW', 'DX', 'DY', 'DZ', '4D', '4E', '4F', '4G', '4H', '4I']],
        ['Indonesia', 'id', 'OC', -6.21, 106.85, ['YB', 'YC', 'YD', 'YE', 'YF', 'YG', 'YH']],
        ['Australia', 'au', 'OC', -35.28, 149.13, ['VK']],
        ['Nueva Zelanda', 'nz', 'OC', -41.29, 174.78, ['ZL']],
        // África
        ['Sudáfrica', 'za', 'AF', -25.75, 28.19, ['ZS', 'ZR', 'ZT', 'ZU']],
        ['Marruecos', 'ma', 'AF', 34.02, -6.83, ['CN']],
        ['Egipto', 'eg', 'AF', 30.04, 31.24, ['SU']],
    ];

    public function run(): void
    {
        foreach (self::PAISES as [$pais, $bandera, $continente, $latitud, $longitud, $prefijos]) {
            foreach ($prefijos as $prefijo) {
                PrefijoPais::updateOrCreate(
                    ['prefijo' => $prefijo],
                    ['pais' => $pais, 'codigo_bandera' => $bandera, 'continente' => $continente, 'latitud' => $latitud, 'longitud' => $longitud],
                );
            }
        }
    }
}
