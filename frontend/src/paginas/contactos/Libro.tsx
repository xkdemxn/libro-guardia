import { useState, type FormEvent } from 'react';
import { Link, useLocation, useNavigate, useSearchParams } from 'react-router';
import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Bandera } from '@/componentes/Bandera';
import { Ceja } from '@/componentes/Ceja';
import { ChipBanda, EstadoQsl, Paginacion } from '@/componentes/Contactos';
import { Dialogo } from '@/componentes/Dialogo';
import { Aviso, BotonPrimario, BotonSecundario, Etiqueta } from '@/componentes/Formulario';
import { Icono } from '@/componentes/Icono';
import { Panel } from '@/componentes/Panel';
import { useCatalogos } from '@/hooks/useCatalogos';
import { useUsuario } from '@/hooks/useUsuario';
import { api, erroresDeValidacion, mensajeDeError } from '@/lib/api';
import type { Contacto, Paginado, ResumenLibro } from '@/lib/tipos';
import { Trans, useTranslation } from 'react-i18next';
import { configuracionRegional } from '@/i18n';

const FILTROS = ['indicativo', 'desde', 'hasta', 'banda_id', 'modo_id', 'qsl'] as const;

/** Libro de guardia: resumen, búsqueda y filtros (US-09), listado paginado (US-06) y eliminar (US-08). */
export function Libro() {
    const { t } = useTranslation();
    const { data: usuario } = useUsuario();
    const { state } = useLocation() as { state: { estado?: string } | null };
    const [parametros, setParametros] = useSearchParams();
    const navegar = useNavigate();
    const catalogos = useCatalogos();
    const cliente = useQueryClient();
    const [porEliminar, setPorEliminar] = useState<Contacto | null>(null);
    const [aviso, setAviso] = useState<{ tipo: 'exito' | 'error'; texto: string } | null>(() => {
        if (state?.estado) {
            return { tipo: 'exito', texto: state.estado };
        }
        // Al volver del enlace de verificación que llega por correo.
        const verificado = parametros.get('verificado');

        return verificado === null ? null : verificado === '1' ? { tipo: 'exito', texto: t('Tu correo quedó verificado.') } : { tipo: 'error', texto: t('El enlace de verificación no es válido.') };
    });

    const filtros = Object.fromEntries(FILTROS.map((campo) => [campo, parametros.get(campo) ?? '']));
    const pagina = parametros.get('page') ?? '1';
    const hayFiltros = FILTROS.some((campo) => filtros[campo]);

    const consulta = useQuery({
        queryKey: ['contactos', filtros, pagina],
        queryFn: async () =>
            (
                await api.get<Paginado<Contacto> & { resumen: ResumenLibro }>('/contactos', {
                    params: { ...Object.fromEntries(Object.entries(filtros).filter(([, valor]) => valor)), page: pagina },
                })
            ).data,
        placeholderData: keepPreviousData,
        retry: false,
    });

    const eliminar = useMutation({
        mutationFn: async (contacto: Contacto) => (await api.delete<{ mensaje: string }>(`/contactos/${contacto.id}`)).data,
        onSuccess: (respuesta) => {
            setAviso({ tipo: 'exito', texto: respuesta.mensaje });
            setPorEliminar(null);
            void cliente.invalidateQueries({ queryKey: ['contactos'] });
            void cliente.invalidateQueries({ queryKey: ['estadisticas'] });
        },
        onError: (error) => {
            setAviso({ tipo: 'error', texto: mensajeDeError(error) });
            setPorEliminar(null);
        },
    });

    function buscar(evento: FormEvent<HTMLFormElement>) {
        evento.preventDefault();
        const datos = new FormData(evento.currentTarget);
        const nuevos = new URLSearchParams();
        FILTROS.forEach((campo) => {
            const valor = String(datos.get(campo) ?? '').trim();
            if (valor) {
                nuevos.set(campo, valor);
            }
        });
        setParametros(nuevos);
    }

    const enlacePagina = (numero: number) => {
        const nuevos = new URLSearchParams(parametros);
        nuevos.set('page', String(numero));

        return `/contactos?${nuevos.toString()}`;
    };

    const resumen = consulta.data?.resumen;
    const tieneContactos = (resumen?.contactos ?? 0) > 0;
    const erroresFiltro = consulta.isError ? Object.values(erroresDeValidacion(consulta.error)).flat() : [];
    const contactos = consulta.data?.data ?? [];
    const total = consulta.data?.meta.total ?? 0;

    return (
        <>
            <header className="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
                <div className="flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <p className="text-[0.75rem] font-medium uppercase tracking-[0.28em] text-slate-500">
                            Libro de guardia · <span className="font-mono text-acento-300">{usuario?.indicativo}</span>
                        </p>
                        <h1 className="mt-2 font-display text-3xl font-semibold text-white sm:text-4xl">{t('Mis contactos')}</h1>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <BotonEnlace to="/contactos/importar">{t('Importar')}</BotonEnlace>
                        {tieneContactos && <BotonEnlace to="/contactos/exportar">{t('Exportar')}</BotonEnlace>}
                        <Link
                            to="/contactos/nuevo"
                            className="inline-flex items-center gap-2 rounded-full bg-gradient-to-b from-marca-400 to-marca-600 px-5 py-2.5 text-sm font-semibold text-blanco shadow-boton transition hover:brightness-110 active:scale-[0.97]"
                        >
                            <Icono nombre="mas" className="h-4 w-4" />
                            {t('Nuevo contacto')}
                        </Link>
                    </div>
                </div>
            </header>

            <div className="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
                {aviso && <Aviso tipo={aviso.tipo}>{aviso.texto}</Aviso>}

                {(resumen?.solicitudes_pendientes ?? 0) > 0 && (
                    <Link to="/solicitudes" className="vidrio flex items-center gap-4 !rounded-2xl border-rose-400/20 px-5 py-4 transition hover:bg-white/[0.06]">
                        <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-rose-500/15 font-display font-semibold text-rose-200 ring-1 ring-rose-400/25">
                            {resumen!.solicitudes_pendientes}
                        </span>
                        <span className="flex-1 text-sm text-slate-200">
                            {resumen!.solicitudes_pendientes === 1
                                ? t('Otra estación registró un contacto contigo que no está en tu libro.')
                                : t('Otras estaciones registraron {{n}} contactos contigo que no están en tu libro.', { n: resumen!.solicitudes_pendientes })}{' '}
                            <span className="text-slate-400">{t('Revísalo y confírmalo con un clic.')}</span>
                        </span>
                        <span className="text-sm font-medium text-acento-300">{t('Ver')} &rsaquo;</span>
                    </Link>
                )}

                {resumen && tieneContactos && (
                    <>
                        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                            {(
                                [
                                    [t('Contactos'), resumen.contactos, t('QSO registrados')],
                                    [t('Países'), resumen.paises, t('distintos contactados')],
                                    [t('Confirmados'), resumen.confirmados, t('con QSL recibida')],
                                    [t('Bandas'), resumen.bandas, t('en las que operaste')],
                                ] as const
                            ).map(([titulo, valor, detalle], indice) => (
                                <div key={titulo} className="vidrio p-5">
                                    <Ceja className="mb-4">{titulo}</Ceja>
                                    <div className="flex items-end justify-between gap-2">
                                        <p className="font-display text-3xl font-semibold tabular-nums text-white sm:text-4xl">{valor.toLocaleString(configuracionRegional())}</p>
                                        <p className="pb-1 font-mono text-xs text-slate-500">{String(indice + 1).padStart(2, '0')} / 04</p>
                                    </div>
                                    <p className="mt-1 text-sm text-slate-400">{detalle}</p>
                                </div>
                            ))}
                        </div>

                        <Panel ceja={t('Buscar y filtrar')}>
                            <form key={parametros.toString()} onSubmit={buscar}>
                                <div className="grid grid-cols-2 items-end gap-4 md:grid-cols-6">
                                    <div className="col-span-2">
                                        <Etiqueta htmlFor="indicativo">{t('Indicativo')}</Etiqueta>
                                        <div className="relative">
                                            <Icono nombre="lupa" className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                                            <input id="indicativo" name="indicativo" type="search" className="campo !pl-10 font-mono uppercase" defaultValue={filtros.indicativo} placeholder="HC1, LU4BB…" />
                                        </div>
                                    </div>
                                    <div>
                                        <Etiqueta htmlFor="desde">{t('Desde')}</Etiqueta>
                                        <input id="desde" name="desde" type="date" className="campo" defaultValue={filtros.desde} />
                                    </div>
                                    <div>
                                        <Etiqueta htmlFor="hasta">{t('Hasta')}</Etiqueta>
                                        <input id="hasta" name="hasta" type="date" className="campo" defaultValue={filtros.hasta} />
                                    </div>
                                    <div>
                                        <Etiqueta htmlFor="banda_id">{t('Banda')}</Etiqueta>
                                        <select id="banda_id" name="banda_id" className="campo" defaultValue={filtros.banda_id}>
                                            <option value="">{t('Todas')}</option>
                                            {catalogos.data?.bandas.map((banda) => (
                                                <option key={banda.id} value={banda.id}>
                                                    {banda.nombre}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <Etiqueta htmlFor="modo_id">{t('Modo')}</Etiqueta>
                                        <select id="modo_id" name="modo_id" className="campo" defaultValue={filtros.modo_id}>
                                            <option value="">{t('Todos')}</option>
                                            {catalogos.data?.modos.map((modo) => (
                                                <option key={modo.id} value={modo.id}>
                                                    {modo.etiqueta}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <div className="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="grid w-full grid-cols-4 gap-1 rounded-full bg-black/25 p-1 ring-1 ring-white/5 sm:inline-flex sm:w-auto" role="radiogroup" aria-label={t('Estado de QSL')}>
                                        {(
                                            [
                                                ['', t('Todas')],
                                                ['confirmado', t('Confirmado')],
                                                ['enviada', t('Enviada')],
                                                ['pendiente', t('Pendiente')],
                                            ] as const
                                        ).map(([valor, etiqueta]) => (
                                            <label key={valor} className="cursor-pointer">
                                                <input type="radio" name="qsl" value={valor} className="peer sr-only" defaultChecked={filtros.qsl === valor} />
                                                <span className="flex justify-center rounded-full px-1 py-1.5 text-[0.8125rem] text-slate-400 transition hover:text-white peer-checked:bg-white/10 peer-checked:font-semibold peer-checked:text-white peer-checked:ring-1 peer-checked:ring-white/10 peer-focus-visible:ring-2 peer-focus-visible:ring-acento-400 sm:px-3.5 sm:text-sm">
                                                    {etiqueta}
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                    <div className="flex items-center justify-end gap-3">
                                        {hayFiltros && (
                                            <button type="button" onClick={() => navegar('/contactos')} className="text-sm text-slate-400 transition hover:text-white">
                                                {t('Limpiar')}
                                            </button>
                                        )}
                                        <BotonPrimario>{t('Buscar')}</BotonPrimario>
                                    </div>
                                </div>

                                {erroresFiltro.length > 0 && (
                                    <div role="alert" className="mt-4 space-y-1">
                                        {erroresFiltro.map((mensaje) => (
                                            <p key={mensaje} className="flex items-center gap-2 text-sm text-rose-400">
                                                <span className="punto bg-rose-400" />
                                                {mensaje}
                                            </p>
                                        ))}
                                    </div>
                                )}
                            </form>
                        </Panel>
                    </>
                )}

                {resumen && !tieneContactos && (
                    <div className="vidrio mx-auto max-w-xl px-6 py-14 text-center">
                        <span className="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-gradient-to-b from-acento-400/30 to-acento-600/10 ring-1 ring-acento-400/30">
                            <Icono nombre="libro" className="h-8 w-8 text-acento-300" />
                        </span>
                        <h2 className="mt-6 font-display text-xl font-semibold text-white">{t('Aún no tienes contactos registrados.')}</h2>
                        <p className="mt-2 text-slate-400">{t('Cuando hagas tu primer QSO, anótalo aquí. La banda y el país se calculan solos.')}</p>
                        <Link
                            to="/contactos/nuevo"
                            className="mt-8 inline-flex items-center gap-2 rounded-full bg-gradient-to-b from-marca-400 to-marca-600 px-6 py-3 text-sm font-semibold text-blanco shadow-boton transition hover:brightness-110"
                        >
                            {t('Registrar mi primer contacto')}
                        </Link>
                    </div>
                )}

                {resumen && tieneContactos && (
                    <section className="vidrio overflow-hidden !p-0">
                        <div className="px-6 pb-4 pt-6">
                            <Ceja>
                                {hayFiltros ? t('Resultados') : t('Registro')} · {total === 1 ? t('1 contacto') : t('{{n}} contactos', { n: total.toLocaleString(configuracionRegional()) })}
                            </Ceja>
                        </div>

                        {contactos.length === 0 ? (
                            <div className="px-6 pb-14 pt-8 text-center">
                                <p className="font-display text-lg text-white">{t('No se encontraron contactos.')}</p>
                                <p className="mt-1 text-sm text-slate-400">{t('Prueba con otro indicativo o quita algunos filtros.')}</p>
                            </div>
                        ) : (
                            <>
                                <TablaContactos contactos={contactos} onEliminar={setPorEliminar} />
                                <TarjetasContactos contactos={contactos} onEliminar={setPorEliminar} />
                                <div className="border-t border-white/5 px-6 py-4">
                                    <Paginacion actual={consulta.data!.meta.current_page} ultima={consulta.data!.meta.last_page} enlace={enlacePagina} />
                                </div>
                            </>
                        )}
                    </section>
                )}
            </div>

            <Dialogo abierto={porEliminar !== null} onCerrar={() => setPorEliminar(null)} titulo={t('¿Eliminar este contacto?')}>
                <p className="mt-2 text-sm text-slate-400">
                    <Trans
                        i18nKey="Se eliminará el contacto con <dato>{{indicativo}} del {{fecha}} {{hora}} UTC</dato>. Esta acción no se puede deshacer."
                        values={{ indicativo: porEliminar?.estacion_llamada, fecha: porEliminar?.fecha, hora: porEliminar?.hora_inicio }}
                        components={{ dato: <span className="font-mono text-slate-200" /> }}
                    />
                </p>
                <div className="mt-8 flex justify-end gap-3">
                    <BotonSecundario onClick={() => setPorEliminar(null)}>{t('Cancelar')}</BotonSecundario>
                    <button
                        type="button"
                        disabled={eliminar.isPending}
                        onClick={() => porEliminar && eliminar.mutate(porEliminar)}
                        className="inline-flex items-center justify-center rounded-full bg-rose-500 px-5 py-2.5 text-sm font-semibold text-blanco transition hover:bg-rose-400 disabled:opacity-50"
                    >
                        {eliminar.isPending ? t('Eliminando…') : t('Eliminar')}
                    </button>
                </div>
            </Dialogo>
        </>
    );
}

function BotonEnlace({ to, children }: { to: string; children: string }) {
    return (
        <Link to={to} className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-semibold text-slate-100 transition hover:bg-white/10">
            {children}
        </Link>
    );
}

function Pais({ contacto }: { contacto: Contacto }) {
    const { t } = useTranslation();
    return (
        <p className="mt-0.5 flex items-center gap-1.5 text-[0.8125rem] text-slate-400">
            <Bandera codigo={contacto.pais?.codigo_bandera} pais={contacto.pais?.nombre} className="h-3.5 w-5" />
            {contacto.pais?.nombre ?? t('País desconocido')}
            {contacto.zona && (
                <span className="text-amber-300/90" title={contacto.zona.descripcion}>
                    · {contacto.zona.nombre}
                </span>
            )}
        </p>
    );
}

function TablaContactos({ contactos, onEliminar }: { contactos: Contacto[]; onEliminar: (contacto: Contacto) => void }) {
    const { t } = useTranslation();
    return (
        <div className="hidden overflow-x-auto md:block">
            <table className="min-w-full text-sm">
                <thead>
                    <tr className="border-y border-white/5 text-left text-[0.75rem] font-medium uppercase tracking-[0.18em] text-slate-500">
                        <th scope="col" className="py-3 pl-6 pr-3 font-medium">{t('Fecha · UTC')}</th>
                        <th scope="col" className="px-3 py-3 font-medium">{t('Estación')}</th>
                        <th scope="col" className="px-3 py-3 text-right font-medium">MHz</th>
                        <th scope="col" className="px-3 py-3 font-medium">{t('Banda')}</th>
                        <th scope="col" className="px-3 py-3 font-medium">{t('Modo')}</th>
                        <th scope="col" className="px-3 py-3 font-medium">Env / Rec</th>
                        <th scope="col" className="px-3 py-3 font-medium">QSL</th>
                        <th scope="col" className="py-3 pl-3 pr-6">
                            <span className="sr-only">{t('Acciones')}</span>
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-white/5">
                    {contactos.map((contacto) => (
                        <tr key={contacto.id} className="group transition hover:bg-white/[0.03]">
                            <td className="whitespace-nowrap py-3.5 pl-6 pr-3">
                                <p className="tabular-nums text-slate-300">{contacto.fecha}</p>
                                <p className="mt-0.5 font-mono text-[0.8125rem] text-slate-400">
                                    {contacto.hora_inicio}
                                    {contacto.hora_fin ? ` → ${contacto.hora_fin}` : ''}
                                </p>
                            </td>
                            <td className="whitespace-nowrap px-3 py-3.5">
                                <Link to={`/contactos/${contacto.id}`} className="font-mono text-[0.9375rem] font-semibold tracking-wide text-white transition hover:text-acento-300">
                                    {contacto.estacion_llamada}
                                </Link>
                                <Pais contacto={contacto} />
                            </td>
                            <td className="whitespace-nowrap px-3 py-3.5 text-right font-mono tabular-nums text-acento-300">{contacto.frecuencia_mhz.toFixed(3)}</td>
                            <td className="whitespace-nowrap px-3 py-3.5">
                                <ChipBanda banda={contacto.banda.nombre} />
                            </td>
                            <td className="whitespace-nowrap px-3 py-3.5 font-medium text-slate-300">{contacto.modo.etiqueta}</td>
                            <td className="whitespace-nowrap px-3 py-3.5 font-mono tabular-nums text-slate-400">
                                {contacto.reporte_enviado} / {contacto.reporte_recibido}
                            </td>
                            <td className="whitespace-nowrap px-3 py-3.5">
                                <EstadoQsl estado={contacto.estado_qsl} />
                            </td>
                            <td className="whitespace-nowrap py-3.5 pl-3 pr-6 text-right">
                                <div className="inline-flex items-center gap-1 opacity-60 transition group-focus-within:opacity-100 group-hover:opacity-100">
                                    <Link
                                        to={`/contactos/${contacto.id}/editar`}
                                        className="grid h-8 w-8 place-items-center rounded-full text-slate-300 transition hover:bg-white/10 hover:text-white"
                                        title={t('Editar')}
                                        aria-label={t('Editar contacto con {{indicativo}}', { indicativo: contacto.estacion_llamada })}
                                    >
                                        <svg className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                                            <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
                                        </svg>
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => onEliminar(contacto)}
                                        className="grid h-8 w-8 place-items-center rounded-full text-slate-300 transition hover:bg-rose-500/15 hover:text-rose-300"
                                        title={t('Eliminar')}
                                        aria-label={t('Eliminar contacto con {{indicativo}}', { indicativo: contacto.estacion_llamada })}
                                    >
                                        <Icono nombre="papelera" className="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function TarjetasContactos({ contactos, onEliminar }: { contactos: Contacto[]; onEliminar: (contacto: Contacto) => void }) {
    const { t } = useTranslation();
    return (
        <ul className="space-y-3 px-4 pb-4 md:hidden">
            {contactos.map((contacto) => (
                <li key={contacto.id} className="rounded-2xl border border-white/5 bg-white/[0.03] p-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <Link to={`/contactos/${contacto.id}`} className="font-mono text-lg font-semibold text-white">
                                {contacto.estacion_llamada}
                            </Link>
                            <Pais contacto={contacto} />
                        </div>
                        <EstadoQsl estado={contacto.estado_qsl} />
                    </div>
                    <div className="mt-3 flex flex-wrap items-center gap-2 text-sm">
                        <ChipBanda banda={contacto.banda.nombre} />
                        <span className="chip border-white/10 bg-white/5 text-slate-300">{contacto.modo.etiqueta}</span>
                        <span className="font-mono text-acento-300">{contacto.frecuencia_mhz.toFixed(3)}</span>
                        <span className="font-mono text-slate-400">
                            {contacto.reporte_enviado}/{contacto.reporte_recibido}
                        </span>
                    </div>
                    <div className="mt-3 flex items-center justify-between border-t border-white/5 pt-3">
                        <p className="font-mono text-[0.8125rem] text-slate-400">
                            {contacto.fecha} {contacto.hora_inicio} UTC
                        </p>
                        <div className="flex gap-4 text-sm">
                            <Link to={`/contactos/${contacto.id}/editar`} className="text-acento-300">
                                {t('Editar')}
                            </Link>
                            <button type="button" className="text-rose-300" onClick={() => onEliminar(contacto)}>
                                {t('Eliminar')}
                            </button>
                        </div>
                    </div>
                </li>
            ))}
        </ul>
    );
}
