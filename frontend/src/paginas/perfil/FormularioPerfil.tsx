import { useEffect, useMemo, useState, type FormEvent, type ReactNode } from 'react';
import { Link } from 'react-router';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Bandera } from '@/componentes/Bandera';
import { Aviso, BotonPrimario, Campo, ErrorCampo, Etiqueta } from '@/componentes/Formulario';
import { Icono } from '@/componentes/Icono';
import { Interruptor } from '@/componentes/Interruptor';
import { Panel } from '@/componentes/Panel';
import { Portada } from '@/componentes/Portada';
import { SelectorFondo, type EstadoFondo } from '@/componentes/SelectorFondo';
import { CLAVE_USUARIO } from '@/hooks/useUsuario';
import { api, erroresDeValidacion, mensajeDeError, type ErroresValidacion } from '@/lib/api';
import type { Perfil, Ubicaciones, Usuario, Visibilidad } from '@/lib/tipos';
import { EditorFoto } from '@/paginas/perfil/EditorFoto';
import { Ubicacion, esEcuador } from '@/paginas/perfil/Ubicacion';

export type SeccionFormulario = 'datos' | 'estacion' | 'portada' | 'privacidad';

/** Sección donde está cada campo, para abrirla si el servidor devuelve un error en él. */
const SECCION_DEL_CAMPO: Record<string, SeccionFormulario> = {
    foto: 'datos', nombres: 'datos', apellidos: 'datos', email: 'datos', pais: 'datos', provincia: 'datos', ciudad: 'datos',
    grid_locator: 'estacion', equipo_radio: 'estacion', antena: 'estacion', biografia: 'estacion',
    portada: 'portada', portada_estilo: 'portada', portada_opacidad: 'portada',
};

interface Valores {
    nombres: string;
    apellidos: string;
    email: string;
    pais: string;
    provincia: string;
    ciudad: string;
    grid_locator: string;
    equipo_radio: string;
    antena: string;
    biografia: string;
    visibilidad: Visibilidad;
}

const valoresDe = (perfil: Perfil): Valores => ({
    nombres: perfil.nombres,
    apellidos: perfil.apellidos,
    email: perfil.email,
    pais: perfil.pais ?? '',
    provincia: perfil.provincia ?? '',
    ciudad: perfil.ciudad ?? '',
    grid_locator: perfil.grid_locator ?? '',
    equipo_radio: perfil.equipo_radio ?? '',
    antena: perfil.antena ?? '',
    biografia: perfil.biografia ?? '',
    visibilidad: perfil.visibilidad,
});

const portadaDe = (perfil: Perfil): EstadoFondo => ({
    previa: perfil.portada.url,
    archivo: null,
    quitar: false,
    estilo: perfil.portada.estilo,
    opacidad: perfil.portada.opacidad,
});

/**
 * Perfil del operador (US-13), repartido en cuatro secciones del menú lateral: datos, estación,
 * portada y privacidad. Es un solo formulario: al guardar se envían todas, así ningún dato se pierde.
 */
export function FormularioPerfil({ perfil, seccion, irA }: { perfil: Perfil; seccion: string; irA: (seccion: SeccionFormulario) => void }) {
    const cliente = useQueryClient();
    const ubicaciones = useQuery({
        queryKey: ['ubicaciones'],
        queryFn: async () => (await api.get<Ubicaciones>('/ubicaciones')).data,
        staleTime: Infinity,
    });

    const [valores, setValores] = useState(() => valoresDe(perfil));
    const [foto, setFoto] = useState<{ archivo: File | null; previa: string | null; quitar: boolean }>({ archivo: null, previa: perfil.foto_url, quitar: false });
    const [fotoOriginal, setFotoOriginal] = useState<string | null>(null);
    const [editorAbierto, setEditorAbierto] = useState<string | null>(null);
    const [portada, setPortada] = useState(() => portadaDe(perfil));
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [estado, setEstado] = useState<{ tipo: 'exito' | 'error'; texto: string } | null>(null);
    const [enviando, setEnviando] = useState(false);

    // Al guardar llega el perfil nuevo: el formulario vuelve a quedar "sin cambios".
    useEffect(() => {
        setValores(valoresDe(perfil));
        setFoto({ archivo: null, previa: perfil.foto_url, quitar: false });
        setPortada(portadaDe(perfil));
    }, [perfil]);

    const hayCambios = useMemo(() => {
        const original = portadaDe(perfil);

        return (
            JSON.stringify(valores) !== JSON.stringify(valoresDe(perfil)) ||
            foto.archivo !== null ||
            foto.quitar ||
            portada.archivo !== null ||
            portada.quitar ||
            portada.estilo !== original.estilo ||
            portada.opacidad !== original.opacidad ||
            Object.keys(errores).length > 0
        );
    }, [valores, foto, portada, perfil, errores]);

    const cambiar = <K extends keyof Valores>(campo: K, valor: Valores[K]) => setValores((actuales) => ({ ...actuales, [campo]: valor }));

    async function guardar(evento: FormEvent) {
        evento.preventDefault();
        setEnviando(true);
        setErrores({});
        setEstado(null);

        const datos = new FormData();
        datos.append('_method', 'PATCH');
        const { visibilidad, ...campos } = valores;
        Object.entries(campos).forEach(([campo, valor]) => datos.append(campo, valor));
        Object.entries(visibilidad).forEach(([campo, valor]) => datos.append(campo, valor ? '1' : '0'));
        if (!esEcuador(valores.pais || perfil.pais_prefijo)) {
            datos.set('provincia', '');
        }
        if (foto.archivo) {
            datos.append('foto', foto.archivo);
        }
        datos.append('quitar_foto', foto.quitar ? '1' : '0');
        if (portada.archivo) {
            datos.append('portada', portada.archivo);
        }
        datos.append('quitar_portada', portada.quitar ? '1' : '0');
        datos.append('portada_estilo', portada.estilo);
        datos.append('portada_opacidad', String(portada.opacidad));

        try {
            const { data } = await api.post<{ data: Perfil }>('/perfil', datos);
            cliente.setQueryData(['perfil'], data.data);
            cliente.setQueryData<Usuario | null>(CLAVE_USUARIO, data.data);
            setFotoOriginal(null);
            setEstado({ tipo: 'exito', texto: 'Perfil actualizado.' });
        } catch (error) {
            const validacion = erroresDeValidacion(error);
            setErrores(validacion);
            const primerCampo = Object.keys(validacion)[0];
            if (primerCampo && SECCION_DEL_CAMPO[primerCampo]) {
                irA(SECCION_DEL_CAMPO[primerCampo]);
            }
            setEstado({ tipo: 'error', texto: primerCampo ? 'Revisa los campos marcados.' : mensajeDeError(error) });
        } finally {
            setEnviando(false);
        }
    }

    const iniciales = `${perfil.nombres.charAt(0)}${perfil.apellidos.charAt(0)}`.toUpperCase();
    const fotoVisible = foto.quitar ? null : foto.previa;
    const avisos = estado && (
        <Aviso tipo={estado.tipo} className="mb-6">
            {estado.texto}
        </Aviso>
    );
    const botonGuardar = <BotonGuardar hayCambios={hayCambios} enviando={enviando} />;

    return (
        <form onSubmit={guardar} noValidate>
            {/* Datos personales */}
            {seccion === 'datos' && (
                <Panel className="sm:!p-8">
                    {avisos}
                    <Encabezado titulo="Datos personales" detalle="Cómo te identifican los demás operadores." />

                    <div className="flex flex-col gap-6 sm:flex-row sm:items-center">
                        <div className="relative h-24 w-24 shrink-0 overflow-hidden rounded-3xl bg-gradient-to-br from-marca-400 to-marca-600 ring-1 ring-white/10">
                            {fotoVisible ? (
                                <img src={fotoVisible} alt="Foto de perfil" className="h-full w-full object-cover" />
                            ) : (
                                <span className="grid h-full w-full place-items-center font-display text-2xl font-semibold text-blanco">{iniciales}</span>
                            )}
                        </div>

                        <div className="flex-1">
                            <p className="flex items-center gap-2.5 font-mono text-2xl font-semibold tracking-wide text-white">
                                {perfil.indicativo} <Bandera codigo={perfil.codigo_bandera} pais={perfil.pais} className="h-4 w-[1.35rem]" />
                            </p>
                            <p className="text-xs text-slate-500">El indicativo no se puede cambiar.</p>
                            <div className="mt-3 flex flex-wrap items-center gap-3">
                                <label className="inline-flex cursor-pointer items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-medium text-slate-100 transition hover:bg-white/10">
                                    Cambiar foto
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        className="sr-only"
                                        onChange={(evento) => {
                                            const archivo = evento.target.files?.[0];
                                            evento.target.value = '';
                                            if (archivo?.type.startsWith('image/')) {
                                                const url = URL.createObjectURL(archivo);
                                                setFotoOriginal(url);
                                                setEditorAbierto(url);
                                            }
                                        }}
                                    />
                                </label>
                                {fotoVisible && (
                                    <button
                                        type="button"
                                        onClick={() => setEditorAbierto(fotoOriginal ?? (perfil.foto_url ? new URL(perfil.foto_url, window.location.href).pathname : null))}
                                        className="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium text-acento-300 transition hover:bg-acento-400/10"
                                    >
                                        Recortar y mover
                                    </button>
                                )}
                                {perfil.tiene_foto && (
                                    <label className="inline-flex items-center gap-2 text-sm text-slate-400">
                                        <input
                                            type="checkbox"
                                            checked={foto.quitar}
                                            onChange={(evento) => setFoto({ ...foto, quitar: evento.target.checked })}
                                            className="h-4 w-4 rounded border-white/20 bg-white/5 text-rose-500 focus:ring-rose-400/30 focus:ring-offset-0"
                                        />
                                        Quitar foto
                                    </label>
                                )}
                                <span className="text-xs text-slate-500">JPG, PNG o WEBP · la puedes recortar antes de guardar</span>
                            </div>
                            <ErrorCampo mensajes={errores.foto} />
                        </div>
                    </div>

                    <div className="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <Campo id="nombres" etiqueta="Nombres" value={valores.nombres} onChange={(e) => cambiar('nombres', e.target.value)} required autoComplete="given-name" errores={errores.nombres} />
                        <Campo id="apellidos" etiqueta="Apellidos" value={valores.apellidos} onChange={(e) => cambiar('apellidos', e.target.value)} required autoComplete="family-name" errores={errores.apellidos} />
                        <div className="sm:col-span-2">
                            <Campo id="email" etiqueta="Correo electrónico" type="email" value={valores.email} onChange={(e) => cambiar('email', e.target.value)} required autoComplete="username" errores={errores.email} />
                            <p className="mt-1.5 text-xs text-slate-500">Nunca se muestra en tu perfil público.</p>
                            {!perfil.correo_verificado && <ReenviarVerificacion />}
                        </div>
                        <Ubicacion
                            ubicaciones={ubicaciones.data}
                            paisPrefijo={perfil.pais_prefijo}
                            valores={{ pais: valores.pais, provincia: valores.provincia, ciudad: valores.ciudad }}
                            ciudadGuardada={perfil.ciudad}
                            paisGuardado={perfil.pais}
                            onCambiar={(ubicacion) => setValores((actuales) => ({ ...actuales, ...ubicacion }))}
                            errores={errores}
                        />
                    </div>

                    <div className="mt-8 flex justify-end">{botonGuardar}</div>
                </Panel>
            )}

            {/* Mi estación */}
            {seccion === 'estacion' && (
                <Panel className="sm:!p-8">
                    {avisos}
                    <Encabezado titulo="Mi estación" detalle="Tu ubicación por grid se usa para calcular las distancias del mapa." />

                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <Campo id="grid_locator" etiqueta="Grid locator" className="font-mono uppercase" maxLength={6} placeholder="FI09" value={valores.grid_locator} onChange={(e) => cambiar('grid_locator', e.target.value)} errores={errores.grid_locator} />
                        <Campo id="equipo_radio" etiqueta="Equipo de radio" placeholder="Yaesu FT-991A" value={valores.equipo_radio} onChange={(e) => cambiar('equipo_radio', e.target.value)} errores={errores.equipo_radio} />
                        <Campo id="antena" etiqueta="Antena" placeholder="Dipolo 40 m" value={valores.antena} onChange={(e) => cambiar('antena', e.target.value)} errores={errores.antena} />
                        <div className="sm:col-span-3">
                            <Etiqueta htmlFor="biografia">Biografía</Etiqueta>
                            <textarea
                                id="biografia"
                                rows={5}
                                maxLength={1000}
                                className="campo"
                                placeholder="Cuéntale a otros operadores sobre ti y tu estación."
                                value={valores.biografia}
                                onChange={(e) => cambiar('biografia', e.target.value)}
                            />
                            <ErrorCampo mensajes={errores.biografia} />
                        </div>
                    </div>

                    <div className="mt-8 flex justify-end">{botonGuardar}</div>
                </Panel>
            )}

            {/* Portada del perfil público */}
            {seccion === 'portada' && (
                <Panel className="sm:!p-8">
                    {avisos}
                    <Encabezado titulo="Portada" detalle="Una imagen de fondo para la tarjeta de tu perfil público." margen="mb-6" />

                    {/* Vista previa: la misma tarjeta que ven los demás */}
                    <div className="relative isolate overflow-hidden rounded-3xl bg-white/[0.03] ring-1 ring-white/10">
                        <Portada url={portada.quitar ? null : portada.previa} estilo={portada.estilo} opacidad={portada.opacidad} className="-z-10" />
                        <div className="flex flex-col items-center gap-5 bg-gradient-to-b from-acento-500/10 to-transparent px-6 pb-7 pt-8 text-center sm:flex-row sm:text-left">
                            <div className="h-24 w-24 shrink-0 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-marca-400 to-marca-600 ring-1 ring-white/15">
                                {fotoVisible ? (
                                    <img src={fotoVisible} alt="" className="h-full w-full object-cover" />
                                ) : (
                                    <span className="grid h-full w-full place-items-center font-display text-2xl font-semibold text-blanco">{iniciales}</span>
                                )}
                            </div>
                            <div>
                                <p className="font-mono text-3xl font-semibold tracking-wide text-white">{perfil.indicativo}</p>
                                <p className="mt-1 text-slate-200">{perfil.nombre_completo}</p>
                                <p className="mt-1 inline-flex items-center gap-2 text-sm text-slate-400">
                                    <Bandera codigo={perfil.codigo_bandera} pais={perfil.pais} />
                                    {perfil.pais_publico ?? 'País desconocido'}
                                </p>
                            </div>
                        </div>
                        <span className="absolute right-3 top-3 rounded-full bg-noche-950/60 px-2.5 py-1 text-[0.6875rem] font-semibold uppercase tracking-[0.18em] text-slate-300 backdrop-blur">
                            Vista previa
                        </span>
                    </div>

                    <SelectorFondo
                        campo="portada"
                        fondo={portada}
                        onCambiar={setPortada}
                        guardada={perfil.portada.url !== null}
                        quitarTexto="Quitar portada"
                        lado={1800}
                        estilos={{
                            degradado: ['Degradado', 'La imagen se desvanece hasta la mitad de la tarjeta.'],
                            completa: ['Tarjeta completa', 'Cubre toda la tarjeta, con la transparencia que elijas.'],
                        }}
                        errores={errores}
                    />

                    <div className="mt-8 flex flex-wrap items-center justify-between gap-3">
                        <EnlacePerfilPublico indicativo={perfil.indicativo}>Ver mi perfil público</EnlacePerfilPublico>
                        {botonGuardar}
                    </div>
                </Panel>
            )}

            {/* Privacidad */}
            {seccion === 'privacidad' && (
                <Panel className="sm:!p-8">
                    {avisos}
                    <Encabezado titulo="Privacidad" detalle="Tu indicativo y tu país siempre son visibles. Tu correo y tus contactos nunca lo son." margen="mb-6" />

                    <div className="divide-y divide-white/5">
                        {(
                            [
                                ['mostrar_nombre', 'Nombre y apellidos'],
                                ['mostrar_pais_ciudad', 'Ciudad'],
                                ['mostrar_equipo', 'Equipo y antena'],
                                ['mostrar_biografia_foto', 'Biografía y foto'],
                            ] as [keyof Visibilidad, string][]
                        ).map(([campo, etiqueta]) => (
                            <Interruptor
                                key={campo}
                                nombre={campo}
                                etiqueta={etiqueta}
                                activo={valores.visibilidad[campo]}
                                onCambiar={(activo) => cambiar('visibilidad', { ...valores.visibilidad, [campo]: activo })}
                            />
                        ))}
                    </div>

                    <div className="mt-8 flex flex-wrap items-center justify-between gap-3">
                        <EnlacePerfilPublico indicativo={perfil.indicativo}>Ver cómo queda mi perfil público</EnlacePerfilPublico>
                        {botonGuardar}
                    </div>
                </Panel>
            )}

            <EditorFoto
                src={editorAbierto}
                onCerrar={() => setEditorAbierto(null)}
                onAplicar={(datos) => {
                    setFoto({ archivo: new File([datos], 'foto-perfil.jpg', { type: 'image/jpeg' }), previa: URL.createObjectURL(datos), quitar: false });
                    setEditorAbierto(null);
                }}
            />
        </form>
    );
}

function Encabezado({ titulo, detalle, margen = 'mb-8' }: { titulo: string; detalle: string; margen?: string }) {
    return (
        <header className={margen}>
            <h2 className="font-display text-lg font-semibold text-white">{titulo}</h2>
            <p className="mt-1 text-sm text-slate-400">{detalle}</p>
        </header>
    );
}

/** «Guardar cambios»: deshabilitado mientras el formulario no tenga cambios. */
function BotonGuardar({ hayCambios, enviando }: { hayCambios: boolean; enviando: boolean }) {
    return (
        <div className="flex flex-wrap items-center justify-end gap-x-3 gap-y-2">
            {!hayCambios && (
                <p className="flex items-center gap-1.5 whitespace-nowrap text-sm text-slate-500" role="status">
                    <svg className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fillRule="evenodd" clipRule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" />
                    </svg>
                    No hay cambios por guardar
                </p>
            )}
            <BotonPrimario
                className="whitespace-nowrap !px-7 disabled:cursor-not-allowed disabled:!brightness-100 disabled:active:!scale-100"
                disabled={!hayCambios || enviando}
                title={hayCambios ? undefined : 'Modifica algún dato para poder guardar'}
            >
                {enviando ? 'Guardando…' : 'Guardar cambios'}
            </BotonPrimario>
        </div>
    );
}

function EnlacePerfilPublico({ indicativo, children }: { indicativo: string; children: ReactNode }) {
    return (
        <Link to={`/indicativos/${indicativo}`} target="_blank" className="inline-flex items-center gap-1.5 text-sm text-acento-300 hover:text-acento-200">
            {children}
            <Icono nombre="enlace-externo" className="h-4 w-4" />
        </Link>
    );
}

function ReenviarVerificacion() {
    const [estado, setEstado] = useState<string | null>(null);

    return (
        <p className="mt-2 text-sm text-slate-300">
            Tu correo no está verificado.{' '}
            {estado ?? (
                <button
                    type="button"
                    className="text-acento-300 hover:text-acento-200"
                    onClick={async () => {
                        try {
                            setEstado((await api.post<{ mensaje: string }>('/correo/verificacion')).data.mensaje);
                        } catch (error) {
                            setEstado(mensajeDeError(error));
                        }
                    }}
                >
                    Reenviar el enlace de verificación
                </button>
            )}
        </p>
    );
}
