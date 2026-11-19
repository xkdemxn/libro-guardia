import { Suspense, useEffect, useRef, useState } from 'react';
import { Link, NavLink, Outlet, useLocation } from 'react-router';
import { Avatar } from '@/componentes/Avatar';
import { Cargando } from '@/componentes/Cargando';
import { Emblema } from '@/componentes/Emblema';
import { Icono, type NombreIcono } from '@/componentes/Icono';
import { RelojUtc } from '@/componentes/RelojUtc';
import { SelectorTema } from '@/componentes/SelectorTema';
import { useCerrarSesion } from '@/hooks/useCerrarSesion';
import { useSolicitudes } from '@/hooks/useSolicitudes';
import type { Usuario } from '@/lib/tipos';

const SECCIONES: { ruta: string; titulo: string; fin?: boolean }[] = [
    { ruta: '/contactos', titulo: 'Libro', fin: true },
    { ruta: '/contactos/nuevo', titulo: 'Nuevo contacto' },
    { ruta: '/estadisticas', titulo: 'Estadísticas' },
    { ruta: '/indicativos', titulo: 'Directorio' },
    { ruta: '/perfil', titulo: 'Perfil' },
];

const ACCESOS_CELULAR: { ruta: string; titulo: string; icono: NombreIcono }[] = [
    { ruta: '/contactos', titulo: 'Libro', icono: 'libro' },
    { ruta: '/solicitudes', titulo: 'Solicitudes', icono: 'campana' },
    { ruta: '/estadisticas', titulo: 'Estadísticas', icono: 'grafico' },
    { ruta: '/indicativos', titulo: 'Directorio', icono: 'lupa' },
    { ruta: '/contactos/importar', titulo: 'Importar ADIF', icono: 'importar' },
    { ruta: '/contactos/exportar', titulo: 'Exportar', icono: 'exportar' },
];

/** Páginas con sesión: barra superior flotante de vidrio y el contenido debajo. */
export function LayoutApp({ usuario }: { usuario: Usuario }) {
    return (
        <>
            <div className="fondo-noche" aria-hidden="true">
                <span />
            </div>

            <div className="min-h-screen pb-16">
                <BarraSuperior usuario={usuario} />
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

function BarraSuperior({ usuario }: { usuario: Usuario }) {
    const [menuAbierto, setMenuAbierto] = useState(false);
    const pendientes = useSolicitudes().data?.length ?? 0;
    const [desplazado, setDesplazado] = useState(() => window.scrollY > 4);
    const ubicacion = useLocation();

    useEffect(() => {
        const alDesplazar = () => setDesplazado(window.scrollY > 4);
        window.addEventListener('scroll', alDesplazar, { passive: true });

        return () => window.removeEventListener('scroll', alDesplazar);
    }, []);

    // Al navegar se cierra el menú del celular.
    useEffect(() => setMenuAbierto(false), [ubicacion.pathname]);

    return (
        <nav className="sticky top-0 z-40 px-3 pt-3 sm:px-6">
            {/* Franja de desenfoque a todo el ancho: lo que pasa por debajo de la barra se ve borroso. */}
            <div className={`franja-desenfoque ${desplazado ? 'visible' : ''}`} aria-hidden="true" />
            <div className="vidrio-fuerte mx-auto max-w-7xl rounded-full px-3 py-2 sm:px-4">
                <div className="flex items-center justify-between gap-4">
                    <div className="flex items-center gap-6">
                        <Link to="/contactos" className="flex items-center gap-2.5 rounded-full pe-2 text-white">
                            <Emblema className="h-10 w-10" />
                            <span className="whitespace-nowrap leading-tight lg:hidden xl:block">
                                <span className="block font-display text-sm font-semibold tracking-tight">Libro de Guardia</span>
                                <span className="block text-[0.6875rem] font-medium uppercase tracking-[0.18em] text-amber-300/90">DX Ecuador</span>
                            </span>
                        </Link>

                        {/* Control segmentado */}
                        <div className="hidden items-center gap-1 rounded-full bg-black/25 p-1 ring-1 ring-white/5 lg:flex">
                            {SECCIONES.map((seccion) => (
                                <NavLink
                                    key={seccion.ruta}
                                    to={seccion.ruta}
                                    end={seccion.fin}
                                    className={({ isActive }) =>
                                        isActive
                                            ? 'inline-flex items-center gap-2 whitespace-nowrap rounded-full bg-white/10 px-4 py-1.5 text-sm font-semibold text-white shadow-[inset_0_1px_0_0_rgb(255_255_255/0.12)] ring-1 ring-white/10 transition'
                                            : 'inline-flex items-center gap-2 whitespace-nowrap rounded-full px-4 py-1.5 text-sm font-medium text-slate-400 transition hover:text-white'
                                    }
                                >
                                    {seccion.titulo}
                                </NavLink>
                            ))}
                        </div>
                    </div>

                    <div className="hidden shrink-0 items-center gap-3 lg:flex">
                        <RelojUtc />
                        <SelectorTema />
                        <NavLink
                            to="/solicitudes"
                            title="Solicitudes de confirmación"
                            aria-label={`Solicitudes de confirmación${pendientes ? `: ${pendientes} pendientes` : ''}`}
                            className={({ isActive }) =>
                                `relative grid h-9 w-9 place-items-center rounded-full text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10 hover:text-white ${isActive ? 'bg-white/10 text-white' : ''}`
                            }
                        >
                            <Icono nombre="campana" />
                            {pendientes > 0 && (
                                <span className="absolute -right-1 -top-1 grid h-5 min-w-[1.25rem] place-items-center rounded-full bg-rose-500 px-1 text-[0.75rem] font-bold text-blanco ring-2 ring-noche-900">
                                    {pendientes}
                                </span>
                            )}
                        </NavLink>
                        <MenuUsuario usuario={usuario} />
                    </div>

                    {/* Botón de menú en celular */}
                    <button
                        type="button"
                        onClick={() => setMenuAbierto((abierto) => !abierto)}
                        className="relative grid h-9 w-9 place-items-center rounded-full text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10 lg:hidden"
                        aria-expanded={menuAbierto}
                        aria-label={menuAbierto ? 'Cerrar menú' : 'Abrir menú'}
                    >
                        <svg className="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            {menuAbierto ? (
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            ) : (
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 7h16M4 12h16M4 17h16" />
                            )}
                        </svg>
                        {pendientes > 0 && <span className="absolute right-0 top-0 h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-noche-900" />}
                    </button>
                </div>
            </div>

            {menuAbierto && <MenuCelular usuario={usuario} pendientes={pendientes} />}
        </nav>
    );
}

function MenuUsuario({ usuario }: { usuario: Usuario }) {
    const [abierto, setAbierto] = useState(false);
    const contenedor = useRef<HTMLDivElement>(null);
    const cerrarSesion = useCerrarSesion();

    useEffect(() => {
        if (!abierto) {
            return;
        }

        const alHacerClic = (evento: MouseEvent) => {
            if (!contenedor.current?.contains(evento.target as Node)) {
                setAbierto(false);
            }
        };
        const alPulsarTecla = (evento: KeyboardEvent) => evento.key === 'Escape' && setAbierto(false);
        document.addEventListener('mousedown', alHacerClic);
        document.addEventListener('keydown', alPulsarTecla);

        return () => {
            document.removeEventListener('mousedown', alHacerClic);
            document.removeEventListener('keydown', alPulsarTecla);
        };
    }, [abierto]);

    const enlace = 'flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-slate-200 transition hover:bg-white/10';

    return (
        <div className="relative" ref={contenedor}>
            <button
                type="button"
                onClick={() => setAbierto((valor) => !valor)}
                aria-expanded={abierto}
                aria-haspopup="menu"
                className="flex items-center gap-2 whitespace-nowrap rounded-full bg-white/5 py-1 pe-3 ps-1 text-sm text-slate-200 ring-1 ring-white/10 transition hover:bg-white/10"
            >
                <Avatar usuario={usuario} />
                <span className="font-mono font-semibold">{usuario.indicativo}</span>
                <svg className="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fillRule="evenodd" clipRule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" />
                </svg>
            </button>

            {abierto && (
                <div role="menu" className="menu-flotante absolute right-0 mt-2 w-72 p-2">
                    <div className="flex items-center gap-3 rounded-xl bg-white/[0.04] p-3">
                        <Avatar usuario={usuario} className="h-10 w-10 rounded-xl text-sm" />
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-white">{usuario.nombre_completo}</p>
                            <p className="truncate text-xs text-slate-400">{usuario.email}</p>
                        </div>
                    </div>

                    <p className="px-3 pb-1 pt-3 text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Cuenta</p>
                    <Link role="menuitem" to="/perfil#datos" className={enlace}>
                        <Icono nombre="usuario" className="h-4 w-4 text-slate-400" /> Mi perfil
                    </Link>
                    <Link role="menuitem" to="/perfil#contrasena" className={enlace}>
                        <Icono nombre="candado" className="h-4 w-4 text-slate-400" /> Cambiar contraseña
                    </Link>
                    <Link role="menuitem" to={`/indicativos/${usuario.indicativo}`} className={enlace}>
                        <Icono nombre="enlace-externo" className="h-4 w-4 text-slate-400" /> Ver mi perfil público
                    </Link>

                    <div className="mx-2 my-1.5 h-px bg-white/10" />
                    <button
                        role="menuitem"
                        type="button"
                        onClick={() => cerrarSesion.mutate()}
                        disabled={cerrarSesion.isPending}
                        className={`${enlace} !text-rose-300 hover:!bg-rose-500/10`}
                    >
                        <Icono nombre="salir" className="h-4 w-4" /> Cerrar sesión
                    </button>
                </div>
            )}
        </div>
    );
}

/** Menú en celular: compacto, con accesos en cuadrícula en lugar de una lista larga. */
function MenuCelular({ usuario, pendientes }: { usuario: Usuario; pendientes: number }) {
    const cerrarSesion = useCerrarSesion();

    return (
        <div className="vidrio-fuerte mx-auto mt-2 max-w-7xl rounded-3xl p-3 lg:hidden">
            <div className="flex items-center justify-between gap-3 px-1">
                <div className="flex min-w-0 items-center gap-2.5">
                    <Avatar usuario={usuario} className="h-9 w-9 rounded-xl text-xs" />
                    <div className="min-w-0 leading-tight">
                        <p className="font-mono text-sm font-semibold text-white">{usuario.indicativo}</p>
                        <p className="truncate text-xs text-slate-400">{usuario.nombre_completo}</p>
                    </div>
                </div>
                <div className="flex shrink-0 items-center gap-1.5">
                    <RelojUtc />
                    <SelectorTema />
                </div>
            </div>

            <Link
                to="/contactos/nuevo"
                className="mt-3 flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-b from-marca-400 to-marca-600 py-3 text-sm font-semibold text-blanco shadow-boton"
            >
                <Icono nombre="mas" className="h-4 w-4" /> Nuevo contacto
            </Link>

            <nav className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3" aria-label="Secciones">
                {ACCESOS_CELULAR.map((acceso) => (
                    <NavLink
                        key={acceso.ruta}
                        to={acceso.ruta}
                        end
                        className={({ isActive }) =>
                            `relative flex items-center gap-2.5 rounded-2xl px-3 py-2.5 text-sm font-medium ring-1 transition ${
                                isActive ? 'bg-acento-400/15 text-white ring-acento-400/30' : 'bg-white/[0.03] text-slate-300 ring-white/5 active:bg-white/10'
                            }`
                        }
                    >
                        {({ isActive }) => (
                            <>
                                <Icono nombre={acceso.icono} className={`h-[18px] w-[18px] ${isActive ? 'text-acento-300' : 'text-slate-500'}`} />
                                <span className="truncate">{acceso.titulo}</span>
                                {acceso.ruta === '/solicitudes' && pendientes > 0 && (
                                    <span className="ms-auto grid h-5 min-w-[1.25rem] place-items-center rounded-full bg-rose-500 px-1 text-[0.75rem] font-bold text-blanco">{pendientes}</span>
                                )}
                            </>
                        )}
                    </NavLink>
                ))}
            </nav>

            <div className="mt-3 grid grid-cols-2 gap-2 border-t border-white/10 pt-3">
                <NavLink
                    to="/perfil"
                    className={({ isActive }) =>
                        `flex items-center justify-center gap-2 rounded-2xl py-2.5 text-sm font-medium transition ${isActive ? 'bg-white/10 text-white' : 'text-slate-300 active:bg-white/10'}`
                    }
                >
                    <Icono nombre="usuario" className="h-4 w-4" /> Mi perfil
                </NavLink>
                <button
                    type="button"
                    onClick={() => cerrarSesion.mutate()}
                    disabled={cerrarSesion.isPending}
                    className="flex w-full items-center justify-center gap-2 rounded-2xl py-2.5 text-sm font-medium text-rose-300 transition active:bg-rose-500/10"
                >
                    <Icono nombre="salir" className="h-4 w-4" /> Cerrar sesión
                </button>
            </div>
        </div>
    );
}
