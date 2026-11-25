import { ErrorCampo } from '@/componentes/Formulario';
import { Icono } from '@/componentes/Icono';
import { reducirImagen } from '@/lib/imagenes';
import type { EstiloFondo } from '@/lib/tipos';
import { useTranslation } from 'react-i18next';

export interface EstadoFondo {
    previa: string | null;
    archivo: File | null;
    quitar: boolean;
    estilo: EstiloFondo;
    opacidad: number;
}

interface Props {
    /** Nombre del campo en el servidor (portada, qsl_foto): sus errores vienen como {campo}, {campo}_estilo… */
    campo: string;
    fondo: EstadoFondo;
    onCambiar: (fondo: EstadoFondo) => void;
    guardada: boolean;
    quitarTexto: string;
    estilos: Record<EstiloFondo, [string, string]>;
    /** Lado mayor máximo en px al reducir la imagen en el navegador. */
    lado: number;
    opacidadMinima?: number;
    opacidadMaxima?: number;
    textoOpacidad?: string;
    errores: Record<string, string[] | undefined>;
}

/** Imagen de fondo (portada del perfil o foto de la tarjeta QSL): subir/quitar, estilo y opacidad. */
export function SelectorFondo({
    campo,
    fondo,
    onCambiar,
    guardada,
    quitarTexto,
    estilos,
    lado,
    opacidadMinima = 10,
    opacidadMaxima = 50,
    textoOpacidad,
    errores,
}: Props) {
    const { t } = useTranslation();
    const sinImagen = !fondo.previa || fondo.quitar;

    async function elegir(archivo: File | undefined) {
        if (!archivo || !archivo.type.startsWith('image/')) {
            return;
        }

        const { archivo: reducido, previa } = await reducirImagen(archivo, lado, `${campo}.jpg`);
        onCambiar({ ...fondo, archivo: reducido, previa, quitar: false });
    }

    return (
        <>
            <div className="mt-6 flex flex-wrap items-center gap-3">
                <label className="inline-flex cursor-pointer items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-medium text-slate-100 transition hover:bg-white/10">
                    <Icono nombre="imagen" className="h-4 w-4" />
                    {fondo.previa ? t('Cambiar imagen') : t('Subir imagen')}
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        className="sr-only"
                        onChange={(evento) => {
                            void elegir(evento.target.files?.[0]);
                            evento.target.value = '';
                        }}
                    />
                </label>
                {guardada && (
                    <label className="inline-flex items-center gap-2 text-sm text-slate-400">
                        <input
                            type="checkbox"
                            checked={fondo.quitar}
                            onChange={(evento) => onCambiar({ ...fondo, quitar: evento.target.checked })}
                            className="h-4 w-4 rounded border-white/20 bg-white/5 text-rose-500 focus:ring-rose-400/30 focus:ring-offset-0"
                        />
                        {quitarTexto}
                    </label>
                )}
                <span className="text-xs text-slate-500">{t('JPG, PNG o WEBP · se ajusta sola al tamaño de la tarjeta')}</span>
            </div>
            <ErrorCampo mensajes={errores[campo]} />

            <fieldset className={`mt-7 ${sinImagen ? 'opacity-50' : ''}`} disabled={sinImagen}>
                <legend className="mb-3 text-sm font-medium text-slate-300">{t('Estilo')}</legend>
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    {(Object.entries(estilos) as [EstiloFondo, [string, string]][]).map(([estilo, [titulo, detalle]]) => (
                        <label
                            key={estilo}
                            className={`flex cursor-pointer items-start gap-3 rounded-2xl p-4 ring-1 transition ${
                                fondo.estilo === estilo ? 'bg-acento-400/10 ring-acento-400/40' : 'bg-white/[0.02] ring-white/10 hover:bg-white/[0.05]'
                            }`}
                        >
                            <input
                                type="radio"
                                name={`${campo}_estilo`}
                                value={estilo}
                                checked={fondo.estilo === estilo}
                                onChange={() => onCambiar({ ...fondo, estilo })}
                                className="mt-0.5 h-4 w-4 border-white/20 bg-white/5 text-marca-500 focus:ring-acento-400/30 focus:ring-offset-0"
                            />
                            <span className="min-w-0 flex-1">
                                <span className="block text-sm font-medium text-slate-100">{titulo}</span>
                                <span className="mt-0.5 block text-xs text-slate-500">{detalle}</span>
                            </span>
                            <span className="relative h-10 w-16 shrink-0 overflow-hidden rounded-lg bg-noche-800 ring-1 ring-white/10" aria-hidden="true">
                                <span
                                    className={`absolute inset-0 bg-gradient-to-br from-amber-400 via-rose-500 to-marca-600 ${estilo === 'degradado' ? 'portada-degradado' : 'opacity-40'}`}
                                />
                            </span>
                        </label>
                    ))}
                </div>
                <ErrorCampo mensajes={errores[`${campo}_estilo`]} />

                {fondo.estilo === 'completa' && (
                    <div className="mt-5">
                        <div className="flex items-center justify-between">
                            <label htmlFor={`${campo}_opacidad`} className="text-sm font-medium text-slate-300">
                                {t('Opacidad')}
                            </label>
                            <span className="font-mono text-sm text-acento-300">{fondo.opacidad} %</span>
                        </div>
                        <input
                            id={`${campo}_opacidad`}
                            type="range"
                            min={opacidadMinima}
                            max={opacidadMaxima}
                            step={5}
                            value={fondo.opacidad}
                            onChange={(evento) => onCambiar({ ...fondo, opacidad: Number(evento.target.value) })}
                            className="mt-2 h-1.5 w-full cursor-pointer accent-marca-500"
                        />
                        <p className="mt-1 text-xs text-slate-500">{textoOpacidad ?? t('Máximo 50 %, para que tu indicativo y tus datos se sigan leyendo bien.')}</p>
                        <ErrorCampo mensajes={errores[`${campo}_opacidad`]} />
                    </div>
                )}
            </fieldset>
        </>
    );
}
