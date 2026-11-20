import { Link } from 'react-router';
import type { EstadoQsl as Estado } from '@/lib/tipos';
import { useTranslation } from 'react-i18next';
import { tx } from '@/i18n';

const COLORES_BANDA: Record<string, string> = {
    '160 m': 'border-fuchsia-400/25 bg-fuchsia-400/10 text-fuchsia-300',
    '80 m': 'border-purple-400/25 bg-purple-400/10 text-purple-300',
    '60 m': 'border-violet-400/25 bg-violet-400/10 text-violet-300',
    '40 m': 'border-indigo-400/25 bg-indigo-400/10 text-indigo-300',
    '30 m': 'border-blue-400/25 bg-blue-400/10 text-blue-300',
    '20 m': 'border-sky-400/25 bg-sky-400/10 text-sky-300',
    '17 m': 'border-cyan-400/25 bg-cyan-400/10 text-cyan-300',
    '15 m': 'border-teal-400/25 bg-teal-400/10 text-teal-300',
    '12 m': 'border-emerald-400/25 bg-emerald-400/10 text-emerald-300',
    '10 m': 'border-lime-400/25 bg-lime-400/10 text-lime-300',
    '6 m': 'border-yellow-400/25 bg-yellow-400/10 text-yellow-300',
    '2 m': 'border-amber-400/25 bg-amber-400/10 text-amber-300',
    '1.25 m': 'border-orange-400/25 bg-orange-400/10 text-orange-300',
    '70 cm': 'border-rose-400/25 bg-rose-400/10 text-rose-300',
    '33 cm': 'border-red-400/25 bg-red-400/10 text-red-300',
    '23 cm': 'border-fuchsia-400/25 bg-fuchsia-400/10 text-fuchsia-300',
};

/** Chip con un color propio para cada banda, para reconocerlas de un vistazo. */
export function ChipBanda({ banda }: { banda: string }) {
    return <span className={`chip font-mono ${COLORES_BANDA[banda] ?? 'border-white/15 bg-white/5 text-slate-300'}`}>{banda}</span>;
}

const ESTADOS: Record<Estado, [string, string, string]> = {
    confirmado: ['bg-emerald-400 shadow-[0_0_8px_rgb(52_211_153)]', 'text-emerald-300', tx('Confirmado')],
    enviada: ['bg-amber-400 shadow-[0_0_8px_rgb(251_191_36)]', 'text-amber-300', tx('Enviada')],
    pendiente: ['bg-slate-500', 'text-slate-400', tx('Pendiente')],
};

/** Estado de confirmación QSL con un puntito luminoso. */
export function EstadoQsl({ estado }: { estado: Estado }) {
    const { t } = useTranslation();
    const [punto, texto, etiqueta] = ESTADOS[estado];

    return (
        <span className={`inline-flex items-center gap-2 text-xs font-medium ${texto}`}>
            <span className={`punto h-2 w-2 ${punto}`} />
            {t(etiqueta)}
        </span>
    );
}

/** Paginación de vidrio: anterior, páginas cercanas y siguiente. */
export function Paginacion({ actual, ultima, enlace }: { actual: number; ultima: number; enlace: (pagina: number) => string }) {
    const { t } = useTranslation();
    if (ultima <= 1) {
        return null;
    }

    const paginas = [...new Set([1, actual - 1, actual, actual + 1, ultima])].filter((pagina) => pagina >= 1 && pagina <= ultima).sort((a, b) => a - b);
    const boton = 'grid h-9 min-w-9 place-items-center rounded-full px-3 text-sm transition';

    return (
        <nav className="flex flex-wrap items-center justify-between gap-3" aria-label={t('Paginación')}>
            <p className="text-sm text-slate-500">
                Página {actual} de {ultima}
            </p>
            <div className="flex items-center gap-1">
                {actual > 1 && (
                    <Link to={enlace(actual - 1)} className={`${boton} text-slate-300 hover:bg-white/10`} aria-label={t('Página anterior')}>
                        &lsaquo;
                    </Link>
                )}
                {paginas.map((pagina, indice) => (
                    <span key={pagina} className="flex items-center gap-1">
                        {indice > 0 && pagina - paginas[indice - 1] > 1 && <span className="px-1 text-slate-600">…</span>}
                        <Link
                            to={enlace(pagina)}
                            aria-current={pagina === actual ? 'page' : undefined}
                            className={`${boton} font-mono ${pagina === actual ? 'bg-white/10 font-semibold text-white ring-1 ring-white/10' : 'text-slate-400 hover:bg-white/5 hover:text-white'}`}
                        >
                            {pagina}
                        </Link>
                    </span>
                ))}
                {actual < ultima && (
                    <Link to={enlace(actual + 1)} className={`${boton} text-slate-300 hover:bg-white/10`} aria-label={t('Página siguiente')}>
                        &rsaquo;
                    </Link>
                )}
            </div>
        </nav>
    );
}
