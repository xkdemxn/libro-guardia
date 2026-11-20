import { useSearchParams } from 'react-router';
import { useQuery } from '@tanstack/react-query';
import { ChipBanda, EstadoQsl } from '@/componentes/Contactos';
import { FormularioConsultaQsl } from '@/componentes/FormularioConsultaQsl';
import { Aviso } from '@/componentes/Formulario';
import { Panel } from '@/componentes/Panel';
import { api, erroresDeValidacion, mensajeDeError } from '@/lib/api';
import type { QslVerificada } from '@/lib/tipos';
import { Trans, useTranslation } from 'react-i18next';

/** «Consulta una QSL»: quien recibió una tarjeta comprueba el contacto sin necesidad de cuenta. */
export function ConsultaQsl() {
    const { t } = useTranslation();
    const [parametros] = useSearchParams();
    const qso = parametros.get('qso') ?? '';
    const indicativo = parametros.get('indicativo') ?? '';

    const consulta = useQuery({
        queryKey: ['qsl-verificada', qso, indicativo],
        queryFn: async () => (await api.get<{ data: QslVerificada }>('/qsl/verificar', { params: { qso, indicativo } })).data.data,
        enabled: Boolean(qso && indicativo),
        retry: false,
    });

    const error = consulta.isError ? (Object.values(erroresDeValidacion(consulta.error))[0]?.[0] ?? mensajeDeError(consulta.error)) : null;
    const qsl = consulta.data;

    return (
        <div className="mx-auto box-content max-w-4xl space-y-6 px-4 pb-8 pt-10 sm:px-6 lg:px-8">
            <div>
                <p className="text-[0.75rem] font-medium uppercase tracking-[0.28em] text-slate-500">{t('Sin iniciar sesión')}</p>
                <h1 className="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">{t('Consulta una QSL')}</h1>
                <p className="mt-2 text-slate-400">{t('¿Te llegó una tarjeta de Libro de Guardia? Comprueba que el contacto está registrado.')}</p>
            </div>

            <Panel>
                <FormularioConsultaQsl key={`${qso}-${indicativo}`} qso={qso} indicativo={indicativo} />
            </Panel>

            {consulta.isFetching && <p className="text-sm text-slate-400">{t('Buscando…')}</p>}
            {error && <Aviso tipo="error">{error}</Aviso>}

            {qsl && !consulta.isFetching && (
                <>
                    <Panel ceja={`QSO #${qsl.qso}`}>
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <p className="text-lg text-slate-300">
                                <Trans
                                    i18nKey="<de>{{de}}</de> confirma su contacto con <para>{{para}}</para>"
                                    values={{ de: qsl.de, para: qsl.para }}
                                    components={{ de: <span className="font-mono font-semibold text-white" />, para: <span className="font-mono font-semibold text-amber-200" /> }}
                                />
                            </p>
                            <EstadoQsl estado={qsl.estado_qsl} />
                        </div>
                        <div className="mt-4 flex flex-wrap items-center gap-2 text-sm">
                            <span className="font-mono text-slate-300">
                                {qsl.fecha} · {qsl.hora_utc} UTC
                            </span>
                            <ChipBanda banda={qsl.banda} />
                            <span className="chip border-white/10 bg-white/5 text-slate-300">{qsl.modo}</span>
                            <span className="font-mono text-acento-300">{qsl.frecuencia_mhz.toFixed(3)} MHz</span>
                            <span className="text-slate-400">
                                · Reporte <span className="font-mono text-slate-200">{qsl.reporte}</span>
                            </span>
                        </div>
                        {qsl.verificado && (
                            <p className="mt-4 flex items-center gap-2 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                                <span className="punto h-2 w-2 bg-emerald-400" />
                                {t('Las dos estaciones registraron este contacto en Libro de Guardia: está verificado.')}
                            </p>
                        )}
                    </Panel>

                    <div
                        className="overflow-hidden rounded-[1.6rem] shadow-vidrio-lg ring-1 ring-white/10 [&>svg]:h-auto [&>svg]:w-full"
                        role="img"
                        aria-label={t('Tarjeta QSL de {{de}} para {{para}}', { de: qsl.de, para: qsl.para })}
                        dangerouslySetInnerHTML={{ __html: qsl.svg }}
                    />
                </>
            )}
        </div>
    );
}
