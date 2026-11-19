import type { Conteo } from '@/lib/tipos';

/**
 * Barras horizontales ordenadas para una sola serie (cantidad por categoría). Un solo color;
 * el valor va como texto al final, así la lista también sirve de tabla.
 */
export function Barras({ datos, unidad = 'contactos', mono = false }: { datos: Conteo[]; unidad?: string; mono?: boolean }) {
    const maximo = Math.max(1, ...datos.map((dato) => dato.cantidad));

    return (
        <ul className="space-y-1" role="list">
            {datos.map(({ nombre, cantidad }) => (
                <li
                    key={nombre}
                    className="group grid grid-cols-[7.5rem_1fr_2.75rem] items-center gap-3 rounded-lg px-1 py-1.5 transition hover:bg-white/[0.04]"
                    title={`${nombre}: ${cantidad} ${unidad}`}
                >
                    <span className={`truncate text-sm text-slate-300 ${mono ? 'font-mono' : ''}`}>{nombre}</span>
                    <span className="relative h-2.5 rounded-full bg-white/[0.04]" aria-hidden="true">
                        <span
                            className="absolute inset-y-0 left-0 rounded-[4px] bg-acento-400 transition group-hover:bg-acento-300"
                            style={{ width: `${Math.max(2, Math.round((cantidad / maximo) * 1000) / 10)}%` }}
                        />
                    </span>
                    <span className="text-right font-mono text-sm tabular-nums text-slate-200">{cantidad.toLocaleString('es')}</span>
                </li>
            ))}
        </ul>
    );
}
