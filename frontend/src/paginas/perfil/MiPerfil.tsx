import { useCallback, useEffect, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router';
import { useQuery } from '@tanstack/react-query';
import { Avatar } from '@/componentes/Avatar';
import { Bandera } from '@/componentes/Bandera';
import { Cargando } from '@/componentes/Cargando';
import { Icono, type NombreIcono } from '@/componentes/Icono';
import { Panel } from '@/componentes/Panel';
import { api } from '@/lib/api';
import type { Perfil } from '@/lib/tipos';
import { CambiarContrasena } from '@/paginas/perfil/CambiarContrasena';
import { EliminarCuenta } from '@/paginas/perfil/EliminarCuenta';
import { FormularioPerfil } from '@/paginas/perfil/FormularioPerfil';
import { Seguridad } from '@/paginas/perfil/Seguridad';

const SECCIONES: [string, string, string, NombreIcono][] = [
    ['datos', 'Datos personales', 'Nombre, correo, país y foto', 'usuario'],
    ['estacion', 'Mi estación', 'Grid, equipo y antena', 'antena'],
    ['portada', 'Portada', 'Fondo de tu perfil público', 'imagen'],
    ['privacidad', 'Privacidad', 'Lo que ven los demás', 'ojo'],
    ['contrasena', 'Contraseña', 'Cambia tu clave de acceso', 'candado'],
    ['seguridad', 'Seguridad', 'Verificación en dos pasos', 'candado'],
    ['cuenta', 'Eliminar cuenta', 'Borrar la cuenta y su libro', 'papelera'],
];

/**
 * Mi perfil: menú lateral con secciones; solo se ve una a la vez y queda en el #hash de la URL,
 * así el enlace «Cambiar contraseña» del menú abre directamente esa sección.
 */
export function MiPerfil() {
    const { hash } = useLocation();
    const navegar = useNavigate();
    const perfil = useQuery({
        queryKey: ['perfil'],
        queryFn: async () => (await api.get<{ data: Perfil }>('/perfil')).data.data,
    });

    const delHash = hash.slice(1);
    const [seccion, setSeccion] = useState(SECCIONES.some(([clave]) => clave === delHash) ? delHash : 'datos');

    useEffect(() => {
        if (SECCIONES.some(([clave]) => clave === delHash)) {
            setSeccion(delHash);
        }
    }, [delHash]);

    const irA = useCallback((nueva: string) => navegar({ hash: nueva }, { replace: true }), [navegar]);

    if (!perfil.data) {
        return <Cargando />;
    }

    const usuario = perfil.data;

    return (
        <>
            <header className="mx-auto max-w-6xl px-4 pt-10 sm:px-6 lg:px-8">
                <p className="text-[0.75rem] font-medium uppercase tracking-[0.28em] text-slate-500">Cuenta</p>
                <h1 className="mt-2 font-display text-3xl font-semibold text-white sm:text-4xl">Mi perfil</h1>
            </header>

            <div className="mx-auto box-content max-w-6xl px-4 pt-8 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-[17rem_minmax(0,1fr)] lg:items-start">
                    {/* Menú lateral (en celular, fila de pestañas deslizable) */}
                    <aside className="min-w-0 lg:sticky lg:top-28">
                        <div className="vidrio hidden p-5 lg:block">
                            <div className="flex items-center gap-3">
                                <Avatar usuario={usuario} className="h-12 w-12 rounded-2xl text-sm ring-1 ring-white/10" />
                                <div className="min-w-0">
                                    <p className="flex items-center gap-2 font-mono text-lg font-semibold text-white">
                                        {usuario.indicativo}
                                        <Bandera codigo={usuario.codigo_bandera} pais={usuario.pais} />
                                    </p>
                                    <p className="truncate text-sm text-slate-400">{usuario.nombre_completo}</p>
                                </div>
                            </div>
                            <div className="my-4 h-px bg-white/10" />

                            <nav className="space-y-1" aria-label="Secciones del perfil">
                                {SECCIONES.map(([clave, titulo, detalle, icono]) => {
                                    const activa = seccion === clave;
                                    const peligro = clave === 'cuenta';

                                    return (
                                        <button
                                            key={clave}
                                            type="button"
                                            onClick={() => irA(clave)}
                                            aria-current={activa ? 'page' : undefined}
                                            className={`group flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-left transition ${
                                                activa ? (peligro ? 'bg-rose-500/10 ring-1 ring-rose-400/25' : 'bg-acento-400/10 ring-1 ring-acento-400/25') : 'hover:bg-white/5'
                                            }`}
                                        >
                                            <span
                                                className={`grid h-9 w-9 shrink-0 place-items-center rounded-xl transition ${
                                                    activa
                                                        ? peligro
                                                            ? 'bg-rose-500 text-blanco'
                                                            : 'bg-gradient-to-b from-marca-400 to-marca-600 text-blanco shadow-boton'
                                                        : peligro
                                                          ? 'bg-rose-500/10 text-rose-300'
                                                          : 'bg-white/5 text-slate-400 group-hover:text-slate-200'
                                                }`}
                                            >
                                                <Icono nombre={icono} className="h-[18px] w-[18px]" />
                                            </span>
                                            <span className="min-w-0">
                                                <span className={`block text-sm font-medium ${peligro ? 'text-rose-300' : 'text-slate-100'}`}>{titulo}</span>
                                                <span className="block truncate text-xs text-slate-500">{detalle}</span>
                                            </span>
                                        </button>
                                    );
                                })}
                            </nav>

                            <div className="my-4 h-px bg-white/10" />
                            <Link
                                to={`/indicativos/${usuario.indicativo}`}
                                target="_blank"
                                className="flex items-center justify-between rounded-2xl px-3 py-2 text-sm text-slate-300 transition hover:bg-white/5 hover:text-white"
                            >
                                Ver mi perfil público
                                <Icono nombre="enlace-externo" className="h-4 w-4 text-slate-500" />
                            </Link>
                        </div>

                        <nav className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 lg:hidden" aria-label="Secciones del perfil">
                            {SECCIONES.map(([clave, titulo, , icono]) => (
                                <button
                                    key={clave}
                                    type="button"
                                    onClick={() => irA(clave)}
                                    className={`flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-medium ring-1 transition ${
                                        seccion === clave
                                            ? 'bg-gradient-to-b from-marca-400 to-marca-600 text-blanco shadow-boton ring-transparent'
                                            : 'bg-white/5 text-slate-300 ring-white/10'
                                    }`}
                                >
                                    <Icono nombre={icono} className="h-4 w-4" />
                                    {titulo}
                                </button>
                            ))}
                        </nav>
                    </aside>

                    {/* Contenido de la sección elegida */}
                    <div className="min-w-0">
                        <FormularioPerfil perfil={usuario} seccion={seccion} irA={irA} />

                        {seccion === 'contrasena' && (
                            <Panel className="sm:!p-8">
                                <CambiarContrasena perfil={usuario} />
                            </Panel>
                        )}
                        {seccion === 'seguridad' && (
                            <Panel className="sm:!p-8">
                                <Seguridad perfil={usuario} />
                            </Panel>
                        )}
                        {seccion === 'cuenta' && (
                            <Panel className="sm:!p-8">
                                <EliminarCuenta />
                            </Panel>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
