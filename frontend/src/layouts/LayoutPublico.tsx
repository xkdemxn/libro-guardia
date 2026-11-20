import { Suspense, useEffect, useState } from 'react';
import { Link, Outlet } from 'react-router';
import { Cargando } from '@/componentes/Cargando';
import { Emblema } from '@/componentes/Emblema';
import { PiePagina } from '@/componentes/PiePagina';
import { Icono } from '@/componentes/Icono';
import { RelojUtc } from '@/componentes/RelojUtc';
import { SelectorIdioma } from '@/componentes/SelectorIdioma';
import { SelectorTema } from '@/componentes/SelectorTema';
import { useTranslation } from 'react-i18next';

/** Páginas públicas (perfil por indicativo) para visitantes sin sesión. */
export function LayoutPublico() {
    const { t } = useTranslation();
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

            <div className="flex min-h-screen flex-col">
                <nav className="sticky top-0 z-40 px-3 pt-3 sm:px-6">
                    <div className={`franja-desenfoque ${desplazado ? 'visible' : ''}`} aria-hidden="true" />
                    <div className="vidrio-fuerte mx-auto flex max-w-7xl items-center justify-between gap-2 rounded-full py-2 pe-2 ps-2 sm:px-4">
                        <Link to="/" className="flex min-w-0 items-center gap-2.5 text-white" aria-label={t('Libro de Guardia, ir al inicio')}>
                            <Emblema className="h-9 w-9 sm:h-10 sm:w-10" />
                            {/* En teléfonos muy angostos queda solo el emblema; el subtítulo, desde pantallas medianas */}
                            <span className="hidden min-w-0 leading-tight min-[400px]:block">
                                <span className="block whitespace-nowrap font-display text-sm font-semibold">{t('Libro de Guardia')}</span>
                                <span className="hidden text-[0.6875rem] font-medium uppercase tracking-[0.18em] text-amber-300/90 sm:block">{t('DX Ecuador')}</span>
                            </span>
                        </Link>
                        <div className="flex shrink-0 items-center gap-1.5 sm:gap-2">
                            <RelojUtc className="hidden sm:inline-flex" />
                            <SelectorIdioma />
                            <SelectorTema />
                            {/* En el celular el logo ya lleva al inicio */}
                            <Link
                                to="/"
                                className="hidden items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10 hover:text-white sm:inline-flex"
                            >
                                <Icono nombre="casa" className="h-4 w-4" />
                                <span className="hidden sm:inline">{t('Inicio')}</span>
                            </Link>
                            <Link
                                to="/iniciar-sesion"
                                className="whitespace-nowrap rounded-full bg-gradient-to-b from-marca-400 to-marca-600 px-4 py-2 text-sm font-semibold text-blanco shadow-boton transition hover:brightness-110"
                            >
                                <span className="sm:hidden">{t('Entrar')}</span>
                                <span className="hidden sm:inline">{t('Iniciar sesión')}</span>
                            </Link>
                        </div>
                    </div>
                </nav>

                <main className="flex-1">
                    {/* Mientras se descarga una sección, la barra superior sigue visible. */}
                    <Suspense fallback={<Cargando />}>
                        <Outlet />
                    </Suspense>
                </main>
                <PiePagina className="mt-10" />
            </div>
        </>
    );
}
