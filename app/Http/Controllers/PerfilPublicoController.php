<?php

namespace App\Http\Controllers;

use App\Models\PrefijoPais;
use App\Models\User;
use App\Rules\IndicativoValido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Búsqueda de indicativos y perfil público, sin necesidad de iniciar sesión (US-15).
 */
class PerfilPublicoController extends Controller
{
    /**
     * Buscador de indicativos. Si se envía un indicativo, lleva a su perfil.
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('indicativo')) {
            return redirect()->route('perfil-publico.show', IndicativoValido::normalizar($request->query('indicativo')));
        }

        return view('perfil-publico.buscar');
    }

    public function show(string $indicativo): View
    {
        $indicativo = IndicativoValido::normalizar($indicativo);
        $operador = User::query()->with('visibilidad')->where('indicativo', $indicativo)->first();

        if ($operador === null) {
            return view('perfil-publico.buscar', [
                'buscado' => $indicativo,
                'prefijoBuscado' => PrefijoPais::paraIndicativo($indicativo),
            ]);
        }

        return view('perfil-publico.show', [
            'perfil' => $operador->datosPublicos(),
        ]);
    }
}
