<?php

namespace App\Http\Controllers\Api;

use App\Enums\ZonaEcuador;
use App\Http\Controllers\Controller;
use App\Models\Contacto;
use App\Services\Maidenhead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Estadísticas del operador (US-16) con el mapa de contactos (innovación 2)
 * y el progreso hacia diplomas de radioafición (innovación 4), incluidas las zonas de Ecuador.
 */
class EstadisticasController extends Controller
{
    /**
     * Países confirmados que pide el diploma DXCC de la ARRL.
     */
    public const META_DXCC = 100;

    /**
     * Continentes que pide el diploma WAC (Worked All Continents) de la IARU.
     */
    public const CONTINENTES = ['NA' => 'Norteamérica', 'SA' => 'Sudamérica', 'EU' => 'Europa', 'AF' => 'África', 'AS' => 'Asia', 'OC' => 'Oceanía'];

    private const PAISES_EN_RANKING = 10;

    public function __invoke(Request $request): JsonResponse
    {
        $rango = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ], [
            'hasta.after_or_equal' => __('La fecha de inicio no puede ser mayor que la fecha de fin.'),
        ]);

        $operador = $request->user();
        $contactos = $operador->contactos()
            ->with(['banda', 'modo', 'prefijoPais', 'confirmacionesQsl'])
            ->filtrar($rango)
            ->get();

        $confirmados = $contactos->filter(fn (Contacto $contacto): bool => $contacto->estadoQsl() === 'confirmado');
        $paisesConfirmados = $confirmados->pluck('prefijoPais.pais')->filter()->unique();
        $continentesTrabajados = $contactos->pluck('prefijoPais.continente')->filter()->unique();
        $contactosPorZona = $contactos->map(fn (Contacto $contacto): ?int => $contacto->zonaEcuador()?->value)->filter()->countBy();
        $origen = $operador->coordenadas();

        return response()->json([
            'hay_contactos' => $contactos->isNotEmpty(),
            'totales' => [
                'contactos' => $contactos->count(),
                'paises' => $contactos->pluck('prefijoPais.pais')->filter()->unique()->count(),
                'confirmados' => $confirmados->count(),
                'bandas' => $contactos->pluck('banda_id')->unique()->count(),
            ],
            'por_banda' => $this->contar($contactos, fn (Contacto $c): string => $c->banda->nombre),
            'por_modo' => $this->contar($contactos, fn (Contacto $c): string => $c->modo->etiqueta),
            'por_pais' => $this->contar($contactos, fn (Contacto $c): string => $c->prefijoPais?->pais ?? __('Desconocido'))
                ->take(self::PAISES_EN_RANKING),
            'dxcc' => ['confirmados' => $paisesConfirmados->count(), 'meta' => self::META_DXCC],
            'continentes' => collect(self::CONTINENTES)->map(fn (string $nombre, string $codigo): array => [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'trabajado' => $continentesTrabajados->contains($codigo),
            ])->values(),
            'zonas' => collect(ZonaEcuador::cases())->map(fn (ZonaEcuador $zona): array => [
                'numero' => $zona->value,
                'provincias' => $zona->provincias(),
                'contactos' => $contactosPorZona->get($zona->value, 0),
                'propia' => $operador->zonaEcuador() === $zona,
            ]),
            'origen' => $origen,
            'puntos' => $this->puntosDelMapa($contactos),
            'mas_lejano' => $this->masLejano($contactos, $origen),
        ]);
    }

    /**
     * Cantidad de contactos por categoría, de mayor a menor.
     *
     * @param  Collection<int, Contacto>  $contactos
     * @param  callable(Contacto): string  $categoria
     * @return Collection<int, array{nombre: string, cantidad: int}>
     */
    private function contar(Collection $contactos, callable $categoria): Collection
    {
        return $contactos->countBy($categoria)->sortDesc()
            ->map(fn (int $cantidad, string $nombre): array => ['nombre' => $nombre, 'cantidad' => $cantidad])
            ->values();
    }

    /**
     * Agrupa los contactos por ubicación para no dibujar cientos de marcadores encimados.
     *
     * @param  Collection<int, Contacto>  $contactos
     * @return list<array{lat: float, lon: float, lugar: string, cantidad: int, indicativos: list<string>}>
     */
    private function puntosDelMapa(Collection $contactos): array
    {
        return $contactos
            ->map(fn (Contacto $contacto): ?array => ($coordenadas = $contacto->coordenadas()) === null ? null : [
                ...$coordenadas,
                'lugar' => $coordenadas['precision'] === 'grid' ? $contacto->grid_locator : ($contacto->prefijoPais->pais ?? ''),
                'indicativo' => $contacto->estacion_llamada,
            ])
            ->filter()
            ->groupBy(fn (array $punto): string => $punto['lat'].','.$punto['lon'])
            ->map(fn (Collection $grupo): array => [
                'lat' => $grupo->first()['lat'],
                'lon' => $grupo->first()['lon'],
                'lugar' => $grupo->first()['lugar'],
                'cantidad' => $grupo->count(),
                'indicativos' => $grupo->pluck('indicativo')->unique()->take(5)->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Contacto>  $contactos
     * @param  array{lat: float, lon: float}|null  $origen
     * @return array{indicativo: string, lugar: string, km: int}|null
     */
    private function masLejano(Collection $contactos, ?array $origen): ?array
    {
        if ($origen === null) {
            return null;
        }

        return $contactos
            ->map(function (Contacto $contacto) use ($origen): ?array {
                $destino = $contacto->coordenadas();

                return $destino === null ? null : [
                    'indicativo' => $contacto->estacion_llamada,
                    'lugar' => $contacto->prefijoPais->pais ?? $contacto->grid_locator,
                    'km' => (int) round(Maidenhead::distanciaKm($origen['lat'], $origen['lon'], $destino['lat'], $destino['lon'])),
                ];
            })
            ->filter()
            ->sortByDesc('km')
            ->first();
    }
}
