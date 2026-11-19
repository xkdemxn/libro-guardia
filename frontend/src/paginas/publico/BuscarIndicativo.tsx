import { useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router';
import { Bandera } from '@/componentes/Bandera';
import { BotonPrimario } from '@/componentes/Formulario';

/** Lo que dice la API cuando un indicativo no está registrado. */
export interface NoRegistrado {
    buscado: string;
    prefijo: { pais: string; codigo_bandera: string | null } | null;
}

/** Buscador público de indicativos (US-15). */
export function BuscarIndicativo({ noRegistrado }: { noRegistrado?: NoRegistrado }) {
    const navegar = useNavigate();
    const [indicativo, setIndicativo] = useState(noRegistrado?.buscado ?? '');

    function buscar(evento: FormEvent) {
        evento.preventDefault();
        const limpio = indicativo.replace(/\s+/g, '').toUpperCase();
        if (limpio) {
            navegar(`/indicativos/${encodeURIComponent(limpio)}`);
        }
    }

    return (
        <div className="mx-auto box-content max-w-3xl px-4 pt-10 sm:px-6 lg:px-8">
            <div className="text-center">
                <p className="text-[0.75rem] font-medium uppercase tracking-[0.28em] text-slate-500">Directorio de operadores</p>
                <h1 className="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">Buscar un indicativo</h1>
                <p className="mt-2 text-slate-400">Conoce a la persona con quien hiciste contacto.</p>
            </div>

            <form onSubmit={buscar} className="vidrio mx-auto mt-10 flex max-w-xl gap-2 !rounded-full p-2" role="search">
                <label htmlFor="indicativo" className="sr-only">
                    Indicativo
                </label>
                <input
                    id="indicativo"
                    type="search"
                    value={indicativo}
                    onChange={(evento) => setIndicativo(evento.target.value)}
                    required
                    maxLength={20}
                    autoFocus
                    placeholder="HC1QRC"
                    className="min-w-0 flex-1 rounded-full border-0 bg-transparent px-5 font-mono text-lg uppercase tracking-wider text-white placeholder:text-slate-600 focus:ring-0"
                />
                <BotonPrimario>Buscar</BotonPrimario>
            </form>

            {noRegistrado && (
                <div className="vidrio mx-auto mt-8 max-w-xl p-6 text-center" role="status">
                    <p className="font-mono text-2xl font-semibold text-white">{noRegistrado.buscado}</p>
                    <p className="mt-2 text-slate-300">Este indicativo no está registrado.</p>
                    {noRegistrado.prefijo && (
                        <p className="mt-1 inline-flex items-center gap-2 text-sm text-slate-500">
                            <Bandera codigo={noRegistrado.prefijo.codigo_bandera} pais={noRegistrado.prefijo.pais} />
                            Por su prefijo, es una estación de {noRegistrado.prefijo.pais}.
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
