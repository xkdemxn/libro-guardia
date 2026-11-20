import { useState, type FormEvent } from 'react';
import { Link, useParams } from 'react-router';
import { useQueryClient } from '@tanstack/react-query';
import { Bandera } from '@/componentes/Bandera';
import { Cargando } from '@/componentes/Cargando';
import { ChipBanda, EstadoQsl } from '@/componentes/Contactos';
import { Aviso, BotonPrimario, ErrorCampo } from '@/componentes/Formulario';
import { Panel } from '@/componentes/Panel';
import { useContacto } from '@/hooks/useContacto';
import { api, erroresDeValidacion, mensajeDeError, type ErroresValidacion } from '@/lib/api';
import type { ConfirmacionQsl, ContactoDetalle, TipoQslManual } from '@/lib/tipos';
import { fechaUtc } from '@/lib/utc';
import { useTranslation } from 'react-i18next';
import { configuracionRegional } from '@/i18n';

/** Detalle del contacto con el panel de confirmaciones QSL (US-10). */
export function DetalleContacto() {
    const { t } = useTranslation();
    const { id } = useParams();
    const consulta = useContacto(id);

    if (consulta.isError) {
        return <ContactoNoEncontrado />;
    }

    if (!consulta.data) {
        return <Cargando />;
    }

    const contacto = consulta.data;
    const datos: [string, string, boolean][] = [
        [t('Fecha'), contacto.fecha, false],
        [t('Hora UTC'), contacto.hora_inicio + (contacto.hora_fin ? ` → ${contacto.hora_fin}` : ''), true],
        [t('Frecuencia'), `${contacto.frecuencia_mhz.toFixed(3)} MHz`, true],
        [t('Modo'), contacto.modo.etiqueta, false],
        [t('Reporte env / rec'), `${contacto.reporte_enviado} / ${contacto.reporte_recibido}`, true],
        [t('Potencia'), contacto.potencia_w ? `${contacto.potencia_w} W` : '—', false],
        [t('Grid locator'), contacto.grid_locator ?? '—', true],
        [t('Operador'), contacto.nombre_operador ?? '—', false],
        [t('Equipo'), contacto.equipo ?? '—', false],
        [t('Activación'), contacto.activacion ?? '—', true],
        [t('Distancia'), contacto.distancia_km !== null ? `≈ ${contacto.distancia_km.toLocaleString(configuracionRegional())} km` : '—', true],
    ];

    return (
        <>
            <header className="mx-auto max-w-4xl px-4 pt-10 sm:px-6 lg:px-8">
                <Link to="/contactos" className="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">
                    &lsaquo; Volver al libro
                </Link>
                <div className="mt-3 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="font-mono text-4xl font-semibold tracking-wide text-white">{contacto.estacion_llamada}</h1>
                        <p className="mt-1 inline-flex items-center gap-2 text-slate-400">
                            <Bandera codigo={contacto.pais?.codigo_bandera} pais={contacto.pais?.nombre} className="h-4 w-[1.35rem]" />
                            {contacto.pais?.nombre ?? t('País desconocido')}
                        </p>
                        {contacto.zona && (
                            <p className="mt-2">
                                <span className="chip border-amber-400/25 bg-amber-400/10 text-amber-200">
                                    <span className="punto bg-amber-400" />
                                    {contacto.zona.descripcion}
                                </span>
                            </p>
                        )}
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        {contacto.es_usuario && (
                            <Link
                                to={`/indicativos/${contacto.estacion_llamada}`}
                                className="rounded-full bg-acento-400/10 px-4 py-2 text-sm font-medium text-acento-300 ring-1 ring-acento-400/25 transition hover:bg-acento-400/20"
                            >
                                {t('Ver perfil')}
                            </Link>
                        )}
                        <EstadoQsl estado={contacto.estado_qsl} />
                        <Link
                            to={`/contactos/${contacto.id}/tarjeta`}
                            className="rounded-full bg-gradient-to-b from-marca-400 to-marca-600 px-4 py-2 text-sm font-semibold text-blanco shadow-boton transition hover:brightness-110"
                        >
                            {t('Tarjeta QSL')}
                        </Link>
                        <Link to={`/contactos/${contacto.id}/editar`} className="rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-100 transition hover:bg-white/10">
                            {t('Editar')}
                        </Link>
                    </div>
                </div>
            </header>

            <div className="mx-auto box-content max-w-4xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
                <Panel ceja={t('Datos del contacto')}>
                    <dl className="grid grid-cols-2 gap-x-6 gap-y-5 sm:grid-cols-4">
                        <div>
                            <dt className="text-xs text-slate-500">{t('Banda')}</dt>
                            <dd className="mt-1">
                                <ChipBanda banda={contacto.banda.nombre} />
                            </dd>
                        </div>
                        {datos.map(([etiqueta, valor, mono]) => (
                            <div key={etiqueta}>
                                <dt className="text-xs text-slate-500">{etiqueta}</dt>
                                <dd className={`mt-1 text-slate-100 ${mono ? 'font-mono' : ''}`}>{valor}</dd>
                            </div>
                        ))}
                    </dl>
                    {contacto.observaciones && (
                        <div className="mt-6 whitespace-pre-line rounded-2xl bg-black/20 p-4 text-sm leading-relaxed text-slate-300 ring-1 ring-white/5">{contacto.observaciones}</div>
                    )}
                </Panel>

                <PanelQsl key={JSON.stringify(contacto.confirmaciones)} contacto={contacto} />
            </div>
        </>
    );
}

function PanelQsl({ contacto }: { contacto: ContactoDetalle }) {
    const { t } = useTranslation();
    const cliente = useQueryClient();
    const [estado, setEstado] = useState(contacto.confirmaciones);
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [aviso, setAviso] = useState<{ tipo: 'exito' | 'error'; texto: string } | null>(null);
    const [enviando, setEnviando] = useState(false);
    const hoy = fechaUtc(new Date());

    const cambiar = (tipo: TipoQslManual, cambios: Partial<ConfirmacionQsl>) => setEstado((actual) => ({ ...actual, [tipo]: { ...actual[tipo], ...cambios } }));

    async function guardar(evento: FormEvent) {
        evento.preventDefault();
        setEnviando(true);
        setErrores({});
        setAviso(null);

        const qsl = Object.fromEntries(
            (Object.entries(estado) as [TipoQslManual, ConfirmacionQsl][]).map(([tipo, valor]) => [
                tipo,
                {
                    enviada: valor.enviada,
                    fecha_envio: valor.enviada ? valor.fecha_envio : null,
                    recibida: valor.recibida,
                    fecha_recepcion: valor.recibida ? valor.fecha_recepcion : null,
                },
            ]),
        );

        try {
            const { data } = await api.put<{ data: ContactoDetalle; mensaje: string }>(`/contactos/${contacto.id}/qsl`, { qsl });
            cliente.setQueryData(['contacto', String(contacto.id)], data.data);
            void cliente.invalidateQueries({ queryKey: ['contactos'] });
            void cliente.invalidateQueries({ queryKey: ['estadisticas'] });
            setAviso({ tipo: 'exito', texto: data.mensaje });
        } catch (error) {
            const validacion = erroresDeValidacion(error);
            setErrores(validacion);
            if (Object.keys(validacion).length === 0) {
                setAviso({ tipo: 'error', texto: mensajeDeError(error) });
            }
        } finally {
            setEnviando(false);
        }
    }

    return (
        <Panel ceja={t('Confirmación QSL')}>
            <p className="-mt-2 mb-5 text-center text-xs text-slate-500">
                Marca por separado si enviaste y si recibiste la confirmación. La fecha no puede ser anterior al contacto ({contacto.fecha}).
            </p>

            {aviso && (
                <Aviso tipo={aviso.tipo} className="mb-5">
                    {aviso.texto}
                </Aviso>
            )}

            {contacto.rechazado_por_contraparte && (
                <div className="mb-5 flex items-center gap-3 rounded-2xl border border-rose-400/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                    <span className="punto h-2 w-2 bg-rose-400" />
                    {contacto.estacion_llamada} indicó el {contacto.rechazado_por_contraparte} que no reconoce este contacto. Revisa la fecha, la hora y la banda.
                </div>
            )}

            {contacto.confirmado_internamente && (
                <div className="mb-5 flex items-center gap-3 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                    <span className="punto h-2 w-2 bg-emerald-400 shadow-[0_0_8px_rgb(52_211_153)]" />
                    Confirmado automáticamente: {contacto.estacion_llamada} también registró este contacto en Libro de Guardia.
                </div>
            )}

            <form onSubmit={guardar} noValidate>
                <div className="divide-y divide-white/5">
                    {(Object.entries(estado) as [TipoQslManual, ConfirmacionQsl][]).map(([tipo, valor]) => (
                        <fieldset key={tipo} className="grid grid-cols-1 gap-4 py-5 first:pt-0 last:pb-0 sm:grid-cols-[8rem_1fr_1fr] sm:items-start">
                            <legend className="sr-only">{valor.etiqueta}</legend>
                            <p className="pt-2 font-display text-sm font-semibold text-white" aria-hidden="true">
                                {valor.etiqueta}
                            </p>
                            {(
                                [
                                    ['enviada', t('Enviada'), 'fecha_envio'],
                                    ['recibida', t('Recibida'), 'fecha_recepcion'],
                                ] as const
                            ).map(([marca, etiqueta, campoFecha]) => (
                                <div key={marca}>
                                    <label className="flex items-center gap-2.5 text-sm text-slate-300">
                                        <input
                                            type="checkbox"
                                            checked={valor[marca]}
                                            onChange={(evento) => cambiar(tipo, { [marca]: evento.target.checked, [campoFecha]: valor[campoFecha] ?? hoy })}
                                            className="h-4 w-4 rounded border-white/20 bg-white/5 text-emerald-500 focus:ring-emerald-400/30 focus:ring-offset-0"
                                        />
                                        {etiqueta}
                                    </label>
                                    {valor[marca] && (
                                        <div className="mt-2">
                                            <label htmlFor={`${tipo}-${campoFecha}`} className="sr-only">
                                                Fecha {etiqueta.toLowerCase()} ({valor.etiqueta})
                                            </label>
                                            <input
                                                id={`${tipo}-${campoFecha}`}
                                                type="date"
                                                className="campo"
                                                min={contacto.fecha}
                                                value={valor[campoFecha] ?? ''}
                                                onChange={(evento) => cambiar(tipo, { [campoFecha]: evento.target.value })}
                                            />
                                        </div>
                                    )}
                                    <ErrorCampo mensajes={errores[`qsl.${tipo}.${campoFecha}`]} />
                                </div>
                            ))}
                        </fieldset>
                    ))}
                </div>

                <div className="mt-6 flex justify-end">
                    <BotonPrimario disabled={enviando}>{enviando ? t('Guardando…') : t('Guardar QSL')}</BotonPrimario>
                </div>
            </form>
        </Panel>
    );
}

export function ContactoNoEncontrado() {
    const { t } = useTranslation();
    return (
        <div className="mx-auto max-w-xl px-4 pt-16">
            <div className="vidrio p-8 text-center">
                <h1 className="font-display text-xl font-semibold text-white">{t('El contacto no existe o no pertenece a tu libro de guardia.')}</h1>
                <Link to="/contactos" className="mt-6 inline-block text-sm font-medium text-acento-300 hover:text-acento-200">
                    Volver al libro &rsaquo;
                </Link>
            </div>
        </div>
    );
}
