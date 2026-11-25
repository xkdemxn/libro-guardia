import { AxiosError } from 'axios';
import { Link, useParams } from 'react-router';
import { useQuery } from '@tanstack/react-query';
import { Bandera } from '@/componentes/Bandera';
import { Cargando } from '@/componentes/Cargando';
import { Ceja } from '@/componentes/Ceja';
import { Emblema } from '@/componentes/Emblema';
import { Portada } from '@/componentes/Portada';
import { api, mensajeDeError } from '@/lib/api';
import type { PerfilPublico as Datos } from '@/lib/tipos';
import { BuscarIndicativo, type NoRegistrado } from '@/paginas/publico/BuscarIndicativo';
import { useTranslation } from 'react-i18next';

/** Perfil público de un operador (US-15). Solo muestra lo que el operador decidió hacer público. */
export function PerfilPublico() {
    const { t } = useTranslation();
    const { indicativo = '' } = useParams();
    const consulta = useQuery({
        queryKey: ['perfil-publico', indicativo],
        queryFn: async () => (await api.get<{ data: Datos }>(`/indicativos/${encodeURIComponent(indicativo)}`)).data.data,
        retry: false,
    });

    if (consulta.isPending) {
        return <Cargando />;
    }

    if (consulta.isError) {
        const error = consulta.error;
        if (error instanceof AxiosError && error.response?.status === 404) {
            return <BuscarIndicativo key={indicativo} noRegistrado={error.response.data as NoRegistrado} />;
        }

        return <p className="mx-auto max-w-3xl px-4 pt-10 text-rose-300">{mensajeDeError(error)}</p>;
    }

    const perfil = consulta.data;
    const lugar = [perfil.ciudad, perfil.pais].filter(Boolean).join(', ');

    return (
        <div className="mx-auto box-content max-w-3xl px-4 pt-10 sm:px-6 lg:px-8">
            <Link to="/indicativos" className="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">
                &lsaquo; Buscar otro indicativo
            </Link>

            <section className="vidrio mt-4 overflow-hidden !p-0">
                <div className="relative isolate">
                    {perfil.portada && <Portada {...perfil.portada} className="-z-10" />}
                    <div className="flex flex-col items-center gap-6 bg-gradient-to-b from-acento-500/10 to-transparent px-6 pb-8 pt-10 text-center sm:flex-row sm:text-left">
                        <div className="h-28 w-28 shrink-0 overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-600 to-slate-800 ring-1 ring-white/15">
                            {perfil.foto_url ? (
                                <img src={perfil.foto_url} alt={`Foto de ${perfil.indicativo}`} className="h-full w-full object-cover" />
                            ) : (
                                <span className="grid h-full w-full place-items-center">
                                    <Emblema className="h-16 w-16 opacity-60" />
                                </span>
                            )}
                        </div>
                        <div>
                            <h1 className="font-mono text-4xl font-semibold tracking-wide text-white">{perfil.indicativo}</h1>
                            {perfil.nombre && <p className="mt-1 text-lg text-slate-200">{perfil.nombre}</p>}
                            <p className="mt-1 inline-flex items-center gap-2 text-slate-400">
                                <Bandera codigo={perfil.bandera} pais={perfil.pais} className="h-4 w-[1.35rem]" />
                                {lugar || t('País desconocido')}
                            </p>
                            {perfil.zona && (
                                <p className="mt-2">
                                    <span className="chip border-amber-400/25 bg-amber-400/10 text-amber-200">
                                        <span className="punto bg-amber-400" />
                                        {perfil.zona}
                                    </span>
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                {(perfil.equipo_radio || perfil.antena || perfil.biografia) && (
                    <div className="space-y-6 border-t border-white/5 p-6">
                        {(perfil.equipo_radio || perfil.antena) && (
                            <div>
                                <Ceja className="mb-4">{t('Estación')}</Ceja>
                                <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    {perfil.equipo_radio && (
                                        <div>
                                            <dt className="text-xs text-slate-500">{t('Equipo')}</dt>
                                            <dd className="mt-1 text-slate-100">{perfil.equipo_radio}</dd>
                                        </div>
                                    )}
                                    {perfil.antena && (
                                        <div>
                                            <dt className="text-xs text-slate-500">{t('Antena')}</dt>
                                            <dd className="mt-1 text-slate-100">{perfil.antena}</dd>
                                        </div>
                                    )}
                                </dl>
                            </div>
                        )}
                        {perfil.biografia && (
                            <div>
                                <Ceja className="mb-4">{t('Sobre mí')}</Ceja>
                                <p className="whitespace-pre-line leading-relaxed text-slate-300">{perfil.biografia}</p>
                            </div>
                        )}
                    </div>
                )}
            </section>

            {perfil.es_propio && (
                <p className="mt-4 text-center text-sm text-slate-500">
                    {t('Así ven tu perfil los demás.')}{' '}
                    <Link to="/perfil#privacidad" className="text-acento-300 hover:text-acento-200">
                        {t('Cambiar qué se muestra')}
                    </Link>
                </p>
            )}
        </div>
    );
}
