import type { ReactNode } from 'react';
import { Link } from 'react-router';
import { Ceja } from '@/componentes/Ceja';
import { Icono } from '@/componentes/Icono';
import { Emblema } from '@/componentes/Emblema';
import { PiePagina } from '@/componentes/PiePagina';
import { AntenaAnimada } from '@/componentes/AntenaAnimada';
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

            {/* Pestañas pegadas al borde izquierdo, con el texto en vertical, como separadores de una carpeta */}
            <nav className="fixed left-0 top-1/2 z-30 hidden -translate-y-1/2 flex-col gap-2 xl:flex" aria-label="Información">
                {PAGINAS_INFORMATIVAS.map((pagina) => (
                    <Link
                        key={pagina.ruta}
                        to={pagina.ruta}
                        title={`${pagina.titulo} · ${pagina.detalle}`}
                        aria-label={pagina.titulo}
                        className="vidrio-fuerte group flex w-12 flex-col items-center gap-3 rounded-l-none rounded-r-2xl py-4 transition-all hover:w-14 hover:bg-noche-800/80"
                    >
                        <Icono nombre={pagina.icono} className="h-[18px] w-[18px] shrink-0 text-acento-300" />
                        {/* Letras una debajo de otra, sin girar: Q · S · L */}
                        <span
                            aria-hidden="true"
                            className="font-display text-[0.8125rem] font-semibold uppercase leading-none tracking-[0.18em] text-slate-200 [text-orientation:upright] [writing-mode:vertical-rl] group-hover:text-white"
                        >
                            {pagina.corto}
                        </span>
                    </Link>
                ))}
            </nav>

            <div className="mx-auto grid min-h-[calc(100vh-4.25rem)] max-w-6xl items-center gap-12 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:gap-16">
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

                    {/* Consulta una QSL sin iniciar sesión: lleva a la página de búsqueda */}
                    <div className="vidrio mt-10 flex max-w-md items-center gap-5 p-5">
                        <AntenaAnimada className="h-24 w-24 shrink-0" />
                        <div className="min-w-0">
                            <Ceja className="mb-3">Consulta una QSL</Ceja>
                            <p className="text-sm text-slate-400">¿Recibiste una tarjeta? Verifica el contacto sin crear una cuenta.</p>
                            <Link
                                to="/qsl"
                                className="mt-4 inline-flex items-center gap-2 rounded-full bg-gradient-to-b from-marca-400 to-marca-600 px-5 py-2 text-sm font-semibold text-blanco shadow-boton transition hover:brightness-110 active:scale-[0.97]"
                            >
                                <Icono nombre="lupa" className="h-4 w-4" />
                                Consultar una QSL
                            </Link>
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
                    <nav className="mt-6 flex flex-wrap justify-center gap-2 xl:hidden" aria-label="Información">
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

            <PiePagina />
        </>
    );
}
