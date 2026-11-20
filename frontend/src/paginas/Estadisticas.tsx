import { useState, type FormEvent } from 'react';
import { Link, useSearchParams } from 'react-router';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Barras } from '@/componentes/Barras';
import { Ceja } from '@/componentes/Ceja';
import { BotonPrimario, BotonSecundario, Campo } from '@/componentes/Formulario';
import { MapaContactos } from '@/componentes/MapaContactos';
import { Panel } from '@/componentes/Panel';
import { useUsuario } from '@/hooks/useUsuario';
import { api, erroresDeValidacion, mensajeDeError } from '@/lib/api';
import { descargar, errorDeDescarga } from '@/lib/descargas';
import type { Estadisticas as Datos } from '@/lib/tipos';
import { Trans, useTranslation } from 'react-i18next';
import { configuracionRegional } from '@/i18n';

/** Estadísticas (US-16), mapa de contactos (innovación 2) y diplomas (innovación 4). */
export function Estadisticas() {
    const { t } = useTranslation();
    const { data: usuario } = useUsuario();
    const [parametros, setParametros] = useSearchParams();
    const rango = { desde: parametros.get('desde') ?? '', hasta: parametros.get('hasta') ?? '' };
    const [descargando, setDescargando] = useState(false);
    const [errorPdf, setErrorPdf] = useState<string | null>(null);

    const consulta = useQuery({
        queryKey: ['estadisticas', rango.desde, rango.hasta],
        queryFn: async () =>
            (await api.get<Datos>('/estadisticas', { params: Object.fromEntries(Object.entries(rango).filter(([, valor]) => valor)) })).data,
        placeholderData: keepPreviousData,
        retry: false,
    });

    function aplicar(evento: FormEvent<HTMLFormElement>) {
        evento.preventDefault();
        const datos = new FormData(evento.currentTarget);
        const nuevos = new URLSearchParams();
        ['desde', 'hasta'].forEach((campo) => datos.get(campo) && nuevos.set(campo, String(datos.get(campo))));
        setParametros(nuevos);
    }

    async function reportePdf() {
        setDescargando(true);
        setErrorPdf(null);
        try {
            await descargar('/contactos/exportar', { formato: 'pdf', ...rango }, 'reporte.pdf');
        } catch (error) {
            const { errores, mensaje } = await errorDeDescarga(error);
            setErrorPdf(mensaje ?? Object.values(errores)[0]?.[0] ?? null);
        } finally {
            setDescargando(false);
        }
    }

    const errorRango = consulta.isError ? (Object.values(erroresDeValidacion(consulta.error))[0]?.[0] ?? mensajeDeError(consulta.error)) : null;
    const datos = consulta.data;

    return (
        <>
            <header className="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
                <div className="flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <p className="text-[0.75rem] font-medium uppercase tracking-[0.28em] text-slate-500">
                            Actividad · <span className="font-mono text-acento-300">{usuario?.indicativo}</span>
                        </p>
                        <h1 className="mt-2 font-display text-3xl font-semibold text-white sm:text-4xl">{t('Estadísticas')}</h1>
                    </div>
                    <form key={`${rango.desde}-${rango.hasta}`} onSubmit={aplicar} className="flex flex-wrap items-end gap-3">
                        <Campo id="desde" etiqueta={t('Desde')} type="date" defaultValue={rango.desde} className="!py-2" />
                        <Campo id="hasta" etiqueta={t('Hasta')} type="date" defaultValue={rango.hasta} className="!py-2" />
                        <BotonPrimario className="!py-2.5">{t('Aplicar')}</BotonPrimario>
                        {(rango.desde || rango.hasta) && (
                            <button type="button" onClick={() => setParametros({})} className="pb-2.5 text-sm text-slate-400 hover:text-white">
                                {t('Todo')}
                            </button>
                        )}
                    </form>
                    {datos?.hay_contactos && (
                        <BotonSecundario className="!py-2.5" onClick={reportePdf} disabled={descargando}>
                            <svg className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fillRule="evenodd" clipRule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 11.378 2H4.5Zm4.75 6.75a.75.75 0 0 0-1.5 0v2.546l-.943-1.048a.75.75 0 1 0-1.114 1.004l2.25 2.5a.75.75 0 0 0 1.114 0l2.25-2.5a.75.75 0 1 0-1.114-1.004l-.943 1.048V8.75Z" />
                            </svg>
                            {descargando ? t('Generando…') : t('Reporte PDF')}
                        </BotonSecundario>
                    )}
                </div>
                {(errorRango || errorPdf) && (
                    <p role="alert" className="mt-3 flex items-center gap-2 text-sm text-rose-400">
                        <span className="punto bg-rose-400" />
                        {errorRango ?? errorPdf}
                    </p>
                )}
            </header>

            <div className="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
                {!datos ? null : !datos.hay_contactos ? (
                    <div className="vidrio mx-auto max-w-xl px-6 py-14 text-center" role="status">
                        <h2 className="font-display text-xl font-semibold text-white">{t('No hay contactos en este rango de fechas.')}</h2>
                        <p className="mt-2 text-slate-400">{t('Prueba con otro rango o registra tu primer contacto.')}</p>
                    </div>
                ) : (
                    <Contenido datos={datos} />
                )}
            </div>
        </>
    );
}

function Contenido({ datos }: { datos: Datos }) {
    const { t } = useTranslation();
    const resumen: [string, number][] = [
        [t('Contactos'), datos.totales.contactos],
        [t('Países'), datos.totales.paises],
        [t('Confirmados'), datos.totales.confirmados],
        [t('Bandas'), datos.totales.bandas],
    ];
    const porcentajeDxcc = Math.min(100, Math.round((datos.dxcc.confirmados / datos.dxcc.meta) * 100));
    const zonasTrabajadas = datos.zonas.filter((zona) => zona.contactos > 0).length;

    return (
        <>
            {/* Resumen */}
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                {resumen.map(([titulo, valor], indice) => (
                    <div key={titulo} className="vidrio p-5">
                        <Ceja className="mb-4">{titulo}</Ceja>
                        <div className="flex items-end justify-between">
                            <p className="font-display text-3xl font-semibold tabular-nums text-white sm:text-4xl">{valor.toLocaleString(configuracionRegional())}</p>
                            <p className="pb-1 font-mono text-[0.75rem] text-slate-600">{String(indice + 1).padStart(2, '0')} / 04</p>
                        </div>
                    </div>
                ))}
            </div>

            {/* Mapa (innovación 2) */}
            <section className="vidrio overflow-hidden !p-0">
                <div className="px-6 pb-4 pt-6">
                    <Ceja>{t('Mapa de contactos')}</Ceja>
                </div>
                <MapaContactos origen={datos.origen} puntos={datos.puntos} />
                <div className="flex flex-wrap items-center gap-x-6 gap-y-2 px-6 py-4 text-xs text-slate-400">
                    <span className="inline-flex items-center gap-2">
                        <span className="h-3 w-3 rounded-full bg-dial-400 ring-2 ring-noche-950" />
                        {t('Mi estación')}
                    </span>
                    <span className="inline-flex items-center gap-2">
                        <span className="h-3 w-3 rounded-full bg-acento-400 ring-2 ring-noche-950" />
                        Estaciones contactadas (más grande = más contactos)
                    </span>
                    {datos.mas_lejano && (
                        <span className="ms-auto">
                            {t('Contacto más lejano:')} <span className="font-mono font-semibold text-white">{datos.mas_lejano.indicativo}</span> · {datos.mas_lejano.lugar} ·{' '}
                            <span className="font-mono text-acento-300">{datos.mas_lejano.km.toLocaleString(configuracionRegional())} km</span>
                        </span>
                    )}
                </div>
                {!datos.origen && (
                    <p className="border-t border-white/5 px-6 py-3 text-xs text-slate-500">
                        <Trans
                            i18nKey="Agrega tu grid locator en <enlace>tu perfil</enlace> para calcular distancias exactas."
                            components={{ enlace: <Link to="/perfil#estacion" className="text-acento-300" /> }}
                        />
                    </p>
                )}
            </section>

            {/* Diplomas (innovación 4) */}
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Panel ceja={t('Diploma DXCC')}>
                    <div className="flex items-end justify-between">
                        <p>
                            <span className="font-display text-4xl font-semibold tabular-nums text-white">{datos.dxcc.confirmados}</span>{' '}
                            <span className="text-slate-500">/ {datos.dxcc.meta}</span>
                        </p>
                        <p className="pb-1 font-mono text-sm text-acento-300">{porcentajeDxcc} %</p>
                    </div>
                    <div
                        className="mt-4 h-3 rounded-full bg-acento-400/15"
                        role="progressbar"
                        aria-valuemin={0}
                        aria-valuemax={datos.dxcc.meta}
                        aria-valuenow={datos.dxcc.confirmados}
                        aria-label={t('Países confirmados para el DXCC')}
                    >
                        <div className="h-3 rounded-full bg-gradient-to-r from-acento-500 to-acento-300" style={{ width: `${Math.max(1, porcentajeDxcc)}%` }} />
                    </div>
                    <p className="mt-3 text-sm text-slate-400">{t('Países con QSL confirmada. El diploma DXCC de la ARRL se otorga al confirmar 100.')}</p>
                </Panel>

                <Panel ceja={t('Diploma WAC · todos los continentes')}>
                    <ul className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        {datos.continentes.map((continente) => (
                            <li
                                key={continente.codigo}
                                className={`flex items-center gap-2.5 rounded-xl px-3 py-2.5 ring-1 ${
                                    continente.trabajado ? 'bg-emerald-400/10 text-emerald-200 ring-emerald-400/20' : 'bg-white/[0.02] text-slate-500 ring-white/5'
                                }`}
                            >
                                <span className={`punto h-2 w-2 ${continente.trabajado ? 'bg-emerald-400 shadow-[0_0_8px_rgb(52_211_153)]' : 'bg-slate-600'}`} />
                                <span className="text-sm">{continente.nombre}</span>
                                <span className="sr-only">{continente.trabajado ? 'trabajado' : 'pendiente'}</span>
                            </li>
                        ))}
                    </ul>
                    <p className="mt-3 text-sm text-slate-400">{datos.continentes.filter((c) => c.trabajado).length} de 6 continentes contactados.</p>
                </Panel>
            </div>

            {/* Zonas de Ecuador (HC1 a HC9) */}
            <Panel ceja={t('Zonas de Ecuador')}>
                <div className="mb-5 flex flex-wrap items-end justify-between gap-3">
                    <p>
                        <span className="font-display text-4xl font-semibold tabular-nums text-white">{zonasTrabajadas}</span>{' '}
                        <span className="text-slate-500">{t('/ 9 zonas trabajadas')}</span>
                    </p>
                    <p className="text-sm text-slate-400">
                        El número de la zona es el dígito del indicativo: HC<span className="text-amber-300">5</span>EV está en la zona 5.
                    </p>
                </div>
                <ul className="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                    {datos.zonas.map((zona) => {
                        const trabajada = zona.contactos > 0;

                        return (
                            <li key={zona.numero} className={`flex items-start gap-3 rounded-2xl p-3.5 ring-1 ${trabajada ? 'bg-amber-400/10 ring-amber-400/25' : 'bg-white/[0.02] ring-white/5'}`}>
                                <span
                                    className={`grid h-10 w-10 shrink-0 place-items-center rounded-xl font-display text-lg font-semibold ${
                                        trabajada ? 'bg-gradient-to-b from-amber-300 to-amber-500 text-noche-950' : 'bg-white/5 text-slate-500'
                                    }`}
                                >
                                    {zona.numero}
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className={`flex flex-wrap items-center gap-x-2 text-base font-medium ${trabajada ? 'text-slate-100' : 'text-slate-400'}`}>
                                        HC{zona.numero} · HD{zona.numero}
                                        {zona.propia && (
                                            <span className="rounded-full bg-acento-400/15 px-2 py-0.5 text-[0.75rem] font-semibold uppercase tracking-wider text-acento-300">{t('Tu zona')}</span>
                                        )}
                                    </span>
                                    <span className="mt-1 block text-[0.8125rem] leading-relaxed text-slate-400">{zona.provincias.join(', ')}</span>
                                </span>
                                <span className="shrink-0 text-right">
                                    <span className={`block font-mono text-base tabular-nums ${trabajada ? 'text-amber-200' : 'text-slate-600'}`}>{zona.contactos}</span>
                                    <span className="sr-only">{t('contactos')}</span>
                                </span>
                            </li>
                        );
                    })}
                </ul>
            </Panel>

            {/* Distribución (US-16) */}
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <Panel ceja={t('Por banda')}>
                    <Barras datos={datos.por_banda} mono />
                </Panel>
                <Panel ceja={t('Por modo')}>
                    <Barras datos={datos.por_modo} />
                </Panel>
                <Panel ceja={t('Top países')}>
                    <Barras datos={datos.por_pais} />
                </Panel>
            </div>
        </>
    );
}
