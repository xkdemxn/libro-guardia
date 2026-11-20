import type { ReactNode } from 'react';
import { Link } from 'react-router';
import { Ceja } from '@/componentes/Ceja';
import { Icono } from '@/componentes/Icono';
import { Emblema } from '@/componentes/Emblema';
import { FormularioConsultaQsl } from '@/componentes/FormularioConsultaQsl';
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
                        Tu estación,
                        <br />
                        <span className="bg-gradient-to-r from-acento-300 to-sky-200 bg-clip-text text-transparent">conectada.</span>
                    </h1>
                    <p className="mt-5 max-w-md text-lg leading-relaxed text-slate-400">
                        Registra tus QSO, confírmalos con otras estaciones, crea tus tarjetas QSL y sigue tu camino a los diplomas, todo en un solo lugar.
                    </p>

                    <RelojUtc grande className="mt-8" />

                    {/* Consulta una QSL sin iniciar sesión */}
                    <div className="vidrio mt-10 max-w-md p-5">
                        <Ceja className="mb-4">Consulta una QSL</Ceja>
                        <p className="mb-4 text-sm text-slate-400">¿Recibiste una tarjeta? Verifica el contacto sin crear una cuenta.</p>
                        <FormularioConsultaQsl compacto />
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
                        {/* En el celular la columna izquierda (con la consulta) no se muestra */}
                        <Link
                            to="/qsl"
                            className="inline-flex items-center gap-2 rounded-full bg-acento-400/10 px-4 py-2 text-sm text-acento-200 ring-1 ring-acento-400/25 transition hover:bg-acento-400/20 lg:hidden"
                        >
                            <Icono nombre="lupa" className="h-4 w-4" />
                            Consulta una QSL
                        </Link>
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
