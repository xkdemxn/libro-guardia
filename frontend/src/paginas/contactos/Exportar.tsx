import { useState, type FormEvent } from 'react';
import { Link } from 'react-router';
import { Aviso, BotonPrimario, ErrorCampo, Etiqueta } from '@/componentes/Formulario';
import { Panel } from '@/componentes/Panel';
import { useCatalogos } from '@/hooks/useCatalogos';
import type { ErroresValidacion } from '@/lib/api';
import { descargar, errorDeDescarga } from '@/lib/descargas';
import { useTranslation } from 'react-i18next';
import { tx } from '@/i18n';

const FORMATOS = [
    ['pdf', tx('Reporte PDF'), tx('Para imprimir o entregar: resumen por rango, banda y modo, y la tabla de contactos.')],
    ['adif', 'ADIF (.adi)', tx('El estándar de la radioafición. Para LoTW, QRZ, Club Log y otros programas de log.')],
    ['csv', 'CSV (.csv)', tx('Para abrir en Excel, LibreOffice o Google Sheets.')],
] as const;

/** Exportar el libro en PDF, ADIF (US-12) o CSV (US-11), con filtros opcionales. */
export function Exportar() {
    const { t } = useTranslation();
    const catalogos = useCatalogos();
    const [formato, setFormato] = useState<(typeof FORMATOS)[number][0]>('adif');
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [error, setError] = useState<string | null>(null);
    const [descargando, setDescargando] = useState(false);

    async function exportar(evento: FormEvent<HTMLFormElement>) {
        evento.preventDefault();
        setDescargando(true);
        setErrores({});
        setError(null);

        const datos = Object.fromEntries([...new FormData(evento.currentTarget).entries()].filter(([, valor]) => valor !== ''));

        try {
            await descargar('/contactos/exportar', datos, `libro.${formato === 'adif' ? 'adi' : formato}`);
        } catch (fallo) {
            const { errores: validacion, mensaje } = await errorDeDescarga(fallo);
            setErrores(validacion);
            setError(mensaje);
        } finally {
            setDescargando(false);
        }
    }

    return (
        <>
            <header className="mx-auto max-w-3xl px-4 pt-10 sm:px-6 lg:px-8">
                <Link to="/contactos" className="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">
                    &lsaquo; Volver al libro
                </Link>
                <h1 className="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">{t('Exportar mi libro')}</h1>
                <p className="mt-1 text-slate-400">{t('Descarga un reporte en PDF, un respaldo de tus contactos o llévalos a otro programa de log.')}</p>
            </header>

            <div className="mx-auto box-content max-w-3xl px-4 pt-8 sm:px-6 lg:px-8">
                <form onSubmit={exportar} className="space-y-6" noValidate>
                    {error && <Aviso tipo="error">{error}</Aviso>}

                    <Panel ceja={t('01 · Formato')}>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            {FORMATOS.map(([valor, titulo, descripcion]) => (
                                <label
                                    key={valor}
                                    className="relative cursor-pointer rounded-2xl border border-white/10 bg-white/[0.02] p-5 transition-colors hover:bg-white/[0.05] has-[:checked]:border-acento-400/60 has-[:checked]:bg-acento-400/10 has-[:checked]:shadow-[0_0_0_4px_rgb(82_173_255/0.1)] has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-acento-400"
                                >
                                    <input type="radio" name="formato" value={valor} checked={formato === valor} onChange={() => setFormato(valor)} className="sr-only" />
                                    <span className="flex items-center justify-between">
                                        <span className="font-display font-semibold text-white">{t(titulo)}</span>
                                        <span className={`grid h-5 w-5 place-items-center rounded-full ring-1 ${formato === valor ? 'bg-acento-500 ring-acento-400' : 'ring-white/20'}`}>
                                            {formato === valor && <span className="h-2 w-2 rounded-full bg-blanco" />}
                                        </span>
                                    </span>
                                    <span className="mt-2 block text-sm text-slate-400">{t(descripcion)}</span>
                                </label>
                            ))}
                        </div>
                        <ErrorCampo mensajes={errores.formato} />
                    </Panel>

                    <Panel ceja={t('02 · Qué contactos (opcional)')}>
                        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <Etiqueta htmlFor="desde">{t('Desde')}</Etiqueta>
                                <input id="desde" name="desde" type="date" className="campo" />
                                <ErrorCampo mensajes={errores.desde} />
                            </div>
                            <div>
                                <Etiqueta htmlFor="hasta">{t('Hasta')}</Etiqueta>
                                <input id="hasta" name="hasta" type="date" className="campo" />
                                <ErrorCampo mensajes={errores.hasta} />
                            </div>
                            <div>
                                <Etiqueta htmlFor="banda_id">{t('Banda')}</Etiqueta>
                                <select id="banda_id" name="banda_id" className="campo">
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
                                <select id="modo_id" name="modo_id" className="campo">
                                    <option value="">{t('Todos')}</option>
                                    {catalogos.data?.modos.map((modo) => (
                                        <option key={modo.id} value={modo.id}>
                                            {modo.etiqueta}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="sm:col-span-2">
                                <Etiqueta htmlFor="qsl">{t('Estado de QSL')}</Etiqueta>
                                <select id="qsl" name="qsl" className="campo">
                                    <option value="">{t('Todos')}</option>
                                    <option value="confirmado">{t('Solo confirmados')}</option>
                                    <option value="enviada">{t('Solo enviadas')}</option>
                                    <option value="pendiente">{t('Solo pendientes')}</option>
                                </select>
                            </div>
                        </div>
                        <p className="mt-4 text-xs text-slate-500">{t('Si no eliges filtros se exporta el libro completo. Las horas siempre van en UTC.')}</p>
                    </Panel>

                    <div className="flex justify-end">
                        <BotonPrimario className="!px-7 !py-3" disabled={descargando}>
                            {descargando ? t('Generando…') : t('Descargar archivo')}
                        </BotonPrimario>
                    </div>
                </form>
            </div>
        </>
    );
}
