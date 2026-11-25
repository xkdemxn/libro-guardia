import { useId, useMemo, useState, type InputHTMLAttributes, type ReactNode } from 'react';
import { ErrorCampo, Etiqueta } from '@/componentes/Formulario';
import { evaluarContrasena } from '@/lib/seguridadContrasena';
import { useTranslation } from 'react-i18next';

interface Props extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
    id: string;
    etiqueta: ReactNode;
    errores?: string[];
    extra?: ReactNode;
    /** Muestra la barra de seguridad debajo (para contraseñas nuevas). */
    medirSeguridad?: boolean;
    /** Indicativo, nombre, correo…: si la contraseña los contiene, baja su seguridad. */
    datosPersonales?: string[];
}

/**
 * Campo de contraseña con botón para mostrarla u ocultarla y, si se pide, una barra que dice
 * qué tan segura es mientras se escribe.
 */
export function CampoContrasena({ id, etiqueta, errores, extra, medirSeguridad = false, datosPersonales, className = '', onChange, ...props }: Props) {
    const { t } = useTranslation();
    const [visible, setVisible] = useState(false);
    const [valor, setValor] = useState('');

    return (
        <div>
            {extra ? (
                <div className="flex items-baseline justify-between">
                    <Etiqueta htmlFor={id}>{etiqueta}</Etiqueta>
                    {extra}
                </div>
            ) : (
                <Etiqueta htmlFor={id}>{etiqueta}</Etiqueta>
            )}
            <div className="relative">
                <input
                    id={id}
                    name={id}
                    type={visible ? 'text' : 'password'}
                    aria-invalid={errores?.length ? true : undefined}
                    aria-describedby={medirSeguridad ? `${id}-seguridad` : undefined}
                    autoCapitalize="none"
                    autoCorrect="off"
                    spellCheck={false}
                    className={`campo pe-12 ${className}`}
                    onChange={(evento) => {
                        setValor(evento.target.value);
                        onChange?.(evento);
                    }}
                    {...props}
                />
                <button
                    type="button"
                    onClick={() => setVisible((actual) => !actual)}
                    aria-label={visible ? t('Ocultar contraseña') : t('Mostrar contraseña')}
                    aria-pressed={visible}
                    aria-controls={id}
                    title={visible ? t('Ocultar contraseña') : t('Mostrar contraseña')}
                    className="absolute inset-y-0 right-1.5 my-auto grid h-9 w-9 place-items-center rounded-lg text-slate-400 transition hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-acento-400/50"
                >
                    {visible ? <OjoTachado /> : <Ojo />}
                </button>
            </div>
            {medirSeguridad && <BarraSeguridad id={`${id}-seguridad`} contrasena={valor} datosPersonales={datosPersonales} />}
            <ErrorCampo mensajes={errores} />
        </div>
    );
}

const COLORES = ['', 'bg-rose-500', 'bg-orange-500', 'bg-amber-400', 'bg-lime-400', 'bg-emerald-400'];
const TEXTOS = ['', 'text-rose-400', 'text-orange-400', 'text-amber-300', 'text-lime-300', 'text-emerald-300'];

/** Barra de 5 segmentos que se llena y cambia de color según la seguridad, y lo que falta por cumplir. */
export function BarraSeguridad({ id, contrasena, datosPersonales }: { id?: string; contrasena: string; datosPersonales?: string[] }) {
    const { t } = useTranslation();
    const idInterno = useId();
    const seguridad = useMemo(() => evaluarContrasena(contrasena, datosPersonales), [contrasena, datosPersonales]);

    return (
        <div id={id ?? idInterno} className="mt-2.5" aria-live="polite">
            <div
                className="flex gap-1.5"
                role="meter"
                aria-label={t('Seguridad de la contraseña')}
                aria-valuemin={0}
                aria-valuemax={5}
                aria-valuenow={seguridad.nivel}
                aria-valuetext={seguridad.etiqueta ? t(seguridad.etiqueta) : t('Sin escribir')}
            >
                {[1, 2, 3, 4, 5].map((segmento) => (
                    <span
                        key={segmento}
                        className={`h-1.5 flex-1 rounded-full transition-colors duration-300 ${segmento <= seguridad.nivel ? COLORES[seguridad.nivel] : 'bg-white/10'}`}
                    />
                ))}
            </div>
            <div className="mt-2 flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                <ul className="flex flex-wrap gap-x-3 gap-y-1 text-xs">
                    {seguridad.requisitos.map((requisito) => (
                        <li key={requisito.texto} className={`inline-flex items-center gap-1 transition-colors ${requisito.cumplido ? 'text-emerald-300' : 'text-slate-500'}`}>
                            {requisito.cumplido ? (
                                <svg className="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fillRule="evenodd" clipRule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" />
                                </svg>
                            ) : (
                                <span className="punto mx-1 bg-slate-600" aria-hidden="true" />
                            )}
                            {t(requisito.texto)}
                            <span className="sr-only">{requisito.cumplido ? t(': cumplido') : t(': falta')}</span>
                        </li>
                    ))}
                </ul>
                {seguridad.etiqueta && <span className={`text-xs font-semibold ${TEXTOS[seguridad.nivel]}`}>{t(seguridad.etiqueta)}</span>}
            </div>
        </div>
    );
}

function Ojo() {
    return (
        <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
            <path fillRule="evenodd" clipRule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" />
        </svg>
    );
}

function OjoTachado() {
    return (
        <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fillRule="evenodd" clipRule="evenodd" d="M3.28 2.22a.75.75 0 0 0-1.06 1.06l14.5 14.5a.75.75 0 1 0 1.06-1.06l-1.745-1.745a10.029 10.029 0 0 0 3.3-4.38 1.651 1.651 0 0 0 0-1.185A10.004 10.004 0 0 0 9.999 3a9.956 9.956 0 0 0-4.744 1.194L3.28 2.22ZM7.752 6.69l1.092 1.092a2.5 2.5 0 0 1 3.374 3.373l1.091 1.092a4 4 0 0 0-5.557-5.557Z" />
            <path d="m10.748 13.93 2.523 2.523a9.987 9.987 0 0 1-3.27.547c-4.258 0-7.894-2.66-9.337-6.41a1.651 1.651 0 0 1 0-1.186A10.007 10.007 0 0 1 2.839 6.02L6.07 9.252a4 4 0 0 0 4.678 4.678Z" />
        </svg>
    );
}
