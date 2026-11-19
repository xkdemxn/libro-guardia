import type { ReactNode } from 'react';
import { Link } from 'react-router';
import { Ceja } from '@/componentes/Ceja';
import { Emblema } from '@/componentes/Emblema';
import { RelojUtc } from '@/componentes/RelojUtc';
import { SelectorTema } from '@/componentes/SelectorTema';

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
                </div>
            </div>
        </>
    );
}
