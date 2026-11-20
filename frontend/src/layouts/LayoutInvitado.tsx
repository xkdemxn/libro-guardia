import type { ReactNode } from 'react';
import { Link } from 'react-router';
import { Ceja } from '@/componentes/Ceja';
import { Icono } from '@/componentes/Icono';
import { Emblema } from '@/componentes/Emblema';
import { RelojUtc } from '@/componentes/RelojUtc';
import { SelectorTema } from '@/componentes/SelectorTema';
import { PAGINAS_INFORMATIVAS } from '@/paginas/informacion/PaginaInformativa';

/** Pantallas sin sesión (iniciar sesión, registro…): presentación a la izquierda y formulario de vidrio a la derecha. */
export function LayoutInvitado({ children }: { children: ReactNode }) {
    return (
        <>
            <div className="fondo-noche" aria-hidden="true">
                <span />
            </div>

            <div className="fixed right-4 top-4 z-40">
                <SelectorTema className="vidrio-fuerte h-10 w-10" />
            </div>

            {/* Accesos informativos pegados al borde izquierdo (solo donde sobra espacio a los lados) */}
            <nav className="fixed left-0 top-1/2 z-30 hidden -translate-y-1/2 flex-col gap-3 2xl:flex" aria-label="Información">
                {PAGINAS_INFORMATIVAS.map((pagina) => (
                    <Link
                        key={pagina.ruta}
                        to={pagina.ruta}
                        className="vidrio-fuerte group flex w-52 -translate-x-2 items-center gap-3 rounded-l-none rounded-r-2xl py-3.5 pl-6 pr-4 transition hover:translate-x-0 hover:bg-noche-800/80"
                    >
                        <span className="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-acento-400/10 text-acento-300 ring-1 ring-acento-400/25 transition group-hover:bg-acento-400/20">
                            <Icono nombre={pagina.icono} className="h-[18px] w-[18px]" />
                        </span>
                        <span className="min-w-0">
                            <span className="block text-sm font-semibold text-white">{pagina.titulo}</span>
                            <span className="block truncate text-xs text-slate-400">{pagina.detalle}</span>
                        </span>
                    </Link>
                ))}
            </nav>

            <div className="mx-auto grid min-h-screen max-w-6xl items-center gap-12 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:gap-16">
                {/* Presentación */}
                <div className="hidden lg:block">
                    <Link to="/" className="inline-flex items-center gap-4 text-white">
                        <Emblema className="h-20 w-20" />
                        <span className="leading-tight">
                            <span className="block font-display text-xl font-semibold">Libro de Guardia</span>
                            <span className="mt-1 block text-xs font-medium uppercase tracking-[0.22em] text-amber-300/90">Radioaficionados DX Ecuador</span>
                        </span>
                    </Link>

                    <h1 className="mt-10 font-display text-4xl font-semibold leading-tight text-white xl:text-5xl">
                        Cada contacto,
                        <br />
                        <span className="bg-gradient-to-r from-acento-300 to-sky-200 bg-clip-text text-transparent">en su lugar.</span>
                    </h1>
                    <p className="mt-5 max-w-md text-lg leading-relaxed text-slate-400">
                        Registra, busca y confirma tus QSO. La banda y el país se calculan solos, y todo queda en hora UTC.
                    </p>

                    <RelojUtc grande className="mt-8" />

                    {/* QSO de ejemplo */}
                    <div className="vidrio mt-10 max-w-md p-5">
                        <Ceja className="mb-4">QSO de ejemplo</Ceja>
                        <div className="flex items-center justify-between">
                            <p className="font-display text-2xl font-semibold text-white">LU4BB</p>
                            <p className="font-mono text-xs text-slate-500">01 / 05</p>
                        </div>
                        <p className="mt-1 text-sm text-slate-400">Argentina · 20 m · USB · 59 / 59</p>
                        <div className="mt-4 flex items-center justify-between font-mono text-sm">
                            <span className="text-acento-300">14.225 MHz</span>
                            <span className="text-slate-500">12:15 → 12:20 UTC</span>
                        </div>
                        <div className="mt-5 flex justify-center gap-1.5" aria-hidden="true">
                            <span className="punto bg-white" />
                            <span className="punto bg-slate-500" />
                            <span className="punto bg-rose-400" />
                            <span className="punto bg-amber-400" />
                            <span className="punto bg-emerald-400" />
                        </div>
                    </div>
                </div>

                {/* Formulario */}
                <div className="mx-auto w-full max-w-md">
                    <Link to="/" className="mb-8 flex flex-col items-center gap-3 text-center text-white lg:hidden">
                        <Emblema className="h-24 w-24" />
                        <span className="leading-tight">
                            <span className="block font-display text-lg font-semibold">Libro de Guardia</span>
                            <span className="mt-1 block text-[0.75rem] font-medium uppercase tracking-[0.22em] text-amber-300/90">Radioaficionados DX Ecuador</span>
                        </span>
                    </Link>

                    <div className="vidrio p-6 sm:p-8">{children}</div>

                    <div className="mt-6 flex justify-center lg:hidden">
                        <RelojUtc />
                    </div>

                    {/* En pantallas sin espacio a los lados, los mismos accesos van debajo del formulario */}
                    <nav className="mt-6 flex flex-wrap justify-center gap-2 2xl:hidden" aria-label="Información">
                        {PAGINAS_INFORMATIVAS.map((pagina) => (
                            <Link
                                key={pagina.ruta}
                                to={pagina.ruta}
                                className="inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2 text-sm text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10 hover:text-white"
                            >
                                <Icono nombre={pagina.icono} className="h-4 w-4 text-acento-300" />
                                {pagina.titulo}
                            </Link>
                        ))}
                    </nav>
                </div>
            </div>
        </>
    );
}
