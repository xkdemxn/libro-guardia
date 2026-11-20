import { Suspense, useEffect, useState } from 'react';
import { Link, Outlet } from 'react-router';
import { Cargando } from '@/componentes/Cargando';
import { Emblema } from '@/componentes/Emblema';
import { Icono } from '@/componentes/Icono';
import { RelojUtc } from '@/componentes/RelojUtc';
import { SelectorTema } from '@/componentes/SelectorTema';

/** Páginas públicas (perfil por indicativo) para visitantes sin sesión. */
export function LayoutPublico() {
    const [desplazado, setDesplazado] = useState(() => window.scrollY > 4);

    useEffect(() => {
        const alDesplazar = () => setDesplazado(window.scrollY > 4);
        window.addEventListener('scroll', alDesplazar, { passive: true });

        return () => window.removeEventListener('scroll', alDesplazar);
    }, []);

    return (
        <>
            <div className="fondo-noche" aria-hidden="true">
                <span />
            </div>

            <div className="min-h-screen pb-16">
                <nav className="sticky top-0 z-40 px-3 pt-3 sm:px-6">
                    <div className={`franja-desenfoque ${desplazado ? 'visible' : ''}`} aria-hidden="true" />
                    <div className="vidrio-fuerte mx-auto flex max-w-7xl items-center justify-between rounded-full px-3 py-2 sm:px-4">
                        <Link to="/" className="flex items-center gap-2.5 text-white" aria-label="Libro de Guardia, ir al inicio">
                            <Emblema className="h-10 w-10" />
                            <span className="leading-tight">
                                <span className="block font-display text-sm font-semibold">Libro de Guardia</span>
                                <span className="block text-[0.6875rem] font-medium uppercase tracking-[0.18em] text-amber-300/90">DX Ecuador</span>
                            </span>
                        </Link>
                        <div className="flex items-center gap-2">
                            <RelojUtc className="hidden sm:inline-flex" />
                            <SelectorTema />
                            <Link
                                to="/"
                                className="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10 hover:text-white"
                            >
                                <Icono nombre="casa" className="h-4 w-4" />
                                <span className="hidden sm:inline">Inicio</span>
                            </Link>
                            <Link to="/iniciar-sesion" className="rounded-full bg-white/10 px-4 py-1.5 text-sm font-semibold text-white ring-1 ring-white/10 transition hover:bg-white/15">
                                Iniciar sesión
                            </Link>
                        </div>
                    </div>
                </nav>

                <main>
                    {/* Mientras se descarga una sección, la barra superior sigue visible. */}
                    <Suspense fallback={<Cargando />}>
                        <Outlet />
                    </Suspense>
                </main>
            </div>
        </>
    );
}
