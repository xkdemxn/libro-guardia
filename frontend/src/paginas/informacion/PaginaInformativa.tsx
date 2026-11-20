import type { ReactNode } from 'react';
import { Link } from 'react-router';
import { Ceja } from '@/componentes/Ceja';
import { Icono, type NombreIcono } from '@/componentes/Icono';
import { useTranslation } from 'react-i18next';
import { tx } from '@/i18n';

export const PAGINAS_INFORMATIVAS: { ruta: string; titulo: string; corto: string; detalle: string; icono: NombreIcono }[] = [
    { ruta: '/que-es-una-qsl', titulo: tx('¿Qué es una QSL?'), corto: tx('QSL'), detalle: tx('Indicativos, QSO y confirmaciones'), icono: 'tarjeta' },
    { ruta: '/sobre-nosotros', titulo: tx('Sobre nosotros'), corto: tx('Nosotros'), detalle: tx('Quiénes somos y por qué'), icono: 'antena' },
    { ruta: '/privacidad', titulo: tx('Privacidad'), corto: tx('Privacidad'), detalle: tx('Qué datos guardamos y para qué'), icono: 'candado' },
];

/** Marco común de las páginas informativas: encabezado, secciones numeradas y enlaces a las otras dos. */
export function PaginaInformativa({ ceja, titulo, introduccion, children }: { ceja: string; titulo: string; introduccion: ReactNode; children: ReactNode }) {
    const { t } = useTranslation();
    return (
        <div className="mx-auto max-w-7xl px-4 pb-8 pt-10 sm:px-6 lg:px-8">
            <p className="text-[0.75rem] font-medium uppercase tracking-[0.28em] text-slate-500">{ceja}</p>
            <h1 className="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">{titulo}</h1>
            <div className="mt-3 max-w-3xl text-lg leading-relaxed text-slate-400">{introduccion}</div>

            <div className="mt-10 space-y-6">{children}</div>

            <nav className="mt-10 grid gap-3 sm:grid-cols-3" aria-label={t('Más información')}>
                {PAGINAS_INFORMATIVAS.map((pagina) => (
                    <Link key={pagina.ruta} to={pagina.ruta} className="vidrio flex items-center gap-3 !rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/[0.06] hover:text-white">
                        <Icono nombre={pagina.icono} className="h-4 w-4 text-acento-300" />
                        {t(pagina.titulo)}
                    </Link>
                ))}
            </nav>
        </div>
    );
}

/** Una sección numerada «01 · Título» dentro de un panel de vidrio. */
export function Seccion({ numero, titulo, children }: { numero: number; titulo: string; children: ReactNode }) {
    return (
        <section className="vidrio p-6 sm:p-8">
            <Ceja className="mb-5">
                {String(numero).padStart(2, '0')} · {titulo}
            </Ceja>
            <div className="space-y-4 leading-relaxed text-slate-300 [&_strong]:font-semibold [&_strong]:text-white">{children}</div>
        </section>
    );
}

export function Lista({ elementos }: { elementos: ReactNode[] }) {
    return (
        <ul className="space-y-2">
            {elementos.map((elemento, indice) => (
                <li key={indice} className="flex gap-3">
                    <span className="punto mt-2.5 bg-acento-400" />
                    <span>{elemento}</span>
                </li>
            ))}
        </ul>
    );
}
