import { useState } from 'react';
import { Link } from 'react-router';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Cargando } from '@/componentes/Cargando';
import { ChipBanda } from '@/componentes/Contactos';
import { Aviso, BotonPrimario, BotonSecundario } from '@/componentes/Formulario';
import { useSolicitudes } from '@/hooks/useSolicitudes';
import { api, mensajeDeError } from '@/lib/api';
import { Trans, useTranslation } from 'react-i18next';

/**
 * Solicitudes de confirmación: contactos que otras estaciones anotaron contigo y que no están en tu libro.
 * Al aceptarla se copia a tu libro y queda confirmada para los dos; al rechazarla, la otra estación lo ve.
 */
export function Solicitudes() {
    const { t } = useTranslation();
    const solicitudes = useSolicitudes();
    const cliente = useQueryClient();
    const [aviso, setAviso] = useState<{ tipo: 'exito' | 'error'; texto: string } | null>(null);

    const responder = useMutation({
        mutationFn: async ({ id, accion }: { id: number; accion: 'aceptar' | 'rechazar' }) => (await api.post<{ mensaje: string }>(`/solicitudes/${id}/${accion}`)).data,
        onSuccess: (respuesta) => {
            setAviso({ tipo: 'exito', texto: respuesta.mensaje });
            void cliente.invalidateQueries({ queryKey: ['solicitudes'] });
            void cliente.invalidateQueries({ queryKey: ['contactos'] });
            void cliente.invalidateQueries({ queryKey: ['estadisticas'] });
        },
        onError: (error) => setAviso({ tipo: 'error', texto: mensajeDeError(error) }),
    });

    if (!solicitudes.data) {
        return <Cargando />;
    }

    const total = solicitudes.data.length;

    return (
        <>
            <header className="mx-auto max-w-3xl px-4 pt-10 sm:px-6 lg:px-8">
                <p className="text-[0.75rem] font-medium uppercase tracking-[0.28em] text-slate-500">{t('Confirmación QSL')}</p>
                <h1 className="mt-2 font-display text-3xl font-semibold text-white sm:text-4xl">{t('Solicitudes de confirmación')}</h1>
                <p className="mt-1 text-slate-400">
                    {t('Otras estaciones registraron estos contactos contigo. Si los reconoces, agrégalos a tu libro y quedan confirmados para los dos.')}
                </p>
            </header>

            <div className="mx-auto box-content max-w-3xl space-y-4 px-4 pt-8 sm:px-6 lg:px-8">
                {aviso && <Aviso tipo={aviso.tipo}>{aviso.texto}</Aviso>}

                {total === 0 ? (
                    <div className="vidrio px-6 py-14 text-center" role="status">
                        <span className="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-400/10 text-emerald-300 ring-1 ring-emerald-400/25">
                            <svg className="h-7 w-7" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fillRule="evenodd" clipRule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" />
                            </svg>
                        </span>
                        <h2 className="mt-5 font-display text-lg font-semibold text-white">{t('No tienes solicitudes pendientes.')}</h2>
                        <p className="mt-1 text-sm text-slate-400">{t('Cuando otra estación registre un contacto contigo que no esté en tu libro, aparecerá aquí.')}</p>
                    </div>
                ) : (
                    solicitudes.data.map((solicitud, indice) => (
                        <article key={solicitud.id} className="vidrio p-5 sm:p-6">
                            <div className="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p className="text-sm text-slate-400">
                                        <Link to={`/indicativos/${solicitud.indicativo}`} className="font-mono text-lg font-semibold text-white hover:text-acento-300">
                                            {solicitud.indicativo}
                                        </Link>{' '}
                                        {t('dice que habló contigo')}
                                    </p>
                                    <p className="mt-1 font-mono text-sm text-slate-300">
                                        {solicitud.fecha} · {solicitud.hora_inicio}
                                        {solicitud.hora_fin ? ` → ${solicitud.hora_fin}` : ''} UTC
                                    </p>
                                </div>
                                <span className="font-mono text-xs text-slate-600">
                                    {String(indice + 1).padStart(2, '0')} / {String(total).padStart(2, '0')}
                                </span>
                            </div>

                            <div className="mt-4 flex flex-wrap items-center gap-2 text-sm">
                                <ChipBanda banda={solicitud.banda} />
                                <span className="chip border-white/10 bg-white/5 text-slate-300">{solicitud.modo}</span>
                                <span className="font-mono text-acento-300">{solicitud.frecuencia_mhz.toFixed(3)} MHz</span>
                                <span className="text-slate-400">
                                    ·{' '}
                                    <Trans
                                        i18nKey="Tú enviaste <dato>{{enviado}}</dato>, recibiste <dato>{{recibido}}</dato>"
                                        values={{ enviado: solicitud.reporte_enviado, recibido: solicitud.reporte_recibido }}
                                        components={{ dato: <span className="font-mono text-slate-200" /> }}
                                    />
                                </span>
                            </div>

                            <div className="mt-5 flex flex-wrap justify-end gap-3 border-t border-white/5 pt-5">
                                <BotonSecundario disabled={responder.isPending} onClick={() => responder.mutate({ id: solicitud.id, accion: 'rechazar' })}>
                                    {t('No fui yo')}
                                </BotonSecundario>
                                <BotonPrimario type="button" disabled={responder.isPending} onClick={() => responder.mutate({ id: solicitud.id, accion: 'aceptar' })}>
                                    {t('Sí, confirmar y agregar a mi libro')}
                                </BotonPrimario>
                            </div>
                        </article>
                    ))
                )}
            </div>
        </>
    );
}
