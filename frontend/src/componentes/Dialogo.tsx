import { useEffect, useRef, type ReactNode } from 'react';
import { createPortal } from 'react-dom';

/**
 * Ventana modal. Va al <body> con un portal porque el desenfoque de los paneles de vidrio
 * rompe el posicionamiento fijo de lo que tienen dentro. Se cierra con Escape o al hacer clic fuera.
 */
export function Dialogo({ abierto, onCerrar, titulo, children, ancho = 'max-w-md' }: { abierto: boolean; onCerrar: () => void; titulo: string; children: ReactNode; ancho?: string }) {
    const caja = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!abierto) {
            return;
        }

        const alPulsarTecla = (evento: KeyboardEvent) => evento.key === 'Escape' && onCerrar();
        document.addEventListener('keydown', alPulsarTecla);
        const anterior = document.activeElement as HTMLElement | null;
        caja.current?.querySelector<HTMLElement>('input, button, [tabindex]')?.focus();

        return () => {
            document.removeEventListener('keydown', alPulsarTecla);
            anterior?.focus();
        };
    }, [abierto, onCerrar]);

    if (!abierto) {
        return null;
    }

    return createPortal(
        <div
            className="fixed inset-0 z-[60] grid place-items-center overflow-y-auto bg-noche-950/70 p-4 backdrop-blur-sm"
            onMouseDown={(evento) => evento.target === evento.currentTarget && onCerrar()}
        >
            <div ref={caja} role="dialog" aria-modal="true" aria-label={titulo} className={`vidrio-fuerte w-full ${ancho} rounded-3xl p-6`}>
                <h2 className="font-display text-lg font-semibold text-white">{titulo}</h2>
                {children}
            </div>
        </div>,
        document.body,
    );
}
