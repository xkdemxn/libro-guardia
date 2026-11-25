import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { cambiarIdioma, IDIOMAS, type Idioma } from '@/i18n';

/** Botón con el idioma actual (ES, EN, PT) que despliega los idiomas disponibles. */
export function SelectorIdioma({ className = '' }: { className?: string }) {
    const { t, i18n } = useTranslation();
    const [abierto, setAbierto] = useState(false);
    const contenedor = useRef<HTMLDivElement>(null);
    const actual = IDIOMAS.find((idioma) => idioma.codigo === i18n.language) ?? IDIOMAS[0];

    useEffect(() => {
        if (!abierto) {
            return;
        }

        const alHacerClic = (evento: MouseEvent) => !contenedor.current?.contains(evento.target as Node) && setAbierto(false);
        const alPulsarTecla = (evento: KeyboardEvent) => evento.key === 'Escape' && setAbierto(false);
        document.addEventListener('mousedown', alHacerClic);
        document.addEventListener('keydown', alPulsarTecla);

        return () => {
            document.removeEventListener('mousedown', alHacerClic);
            document.removeEventListener('keydown', alPulsarTecla);
        };
    }, [abierto]);

    return (
        <div className="relative" ref={contenedor}>
            <button
                type="button"
                onClick={() => setAbierto((valor) => !valor)}
                aria-haspopup="menu"
                aria-expanded={abierto}
                aria-label={t('Idioma: {{idioma}}', { idioma: actual.nombre })}
                title={t('Cambiar idioma')}
                className={`grid h-9 min-w-9 place-items-center rounded-full px-2 font-mono text-xs font-semibold text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10 hover:text-white ${className}`}
            >
                {actual.corto}
            </button>
            {abierto && (
                <div role="menu" className="menu-flotante absolute right-0 z-50 mt-2 w-40 p-1.5">
                    {IDIOMAS.map((idioma) => (
                        <button
                            key={idioma.codigo}
                            type="button"
                            role="menuitemradio"
                            aria-checked={idioma.codigo === actual.codigo}
                            lang={idioma.codigo}
                            onClick={() => {
                                cambiarIdioma(idioma.codigo as Idioma);
                                setAbierto(false);
                            }}
                            className={`flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm transition ${
                                idioma.codigo === actual.codigo ? 'bg-acento-400/15 text-white' : 'text-slate-300 hover:bg-white/10'
                            }`}
                        >
                            {idioma.nombre}
                            <span className="font-mono text-xs text-slate-500">{idioma.corto}</span>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
