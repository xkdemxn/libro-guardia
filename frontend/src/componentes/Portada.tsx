import type { EstiloFondo } from '@/lib/tipos';

/** Imagen de fondo de la tarjeta del perfil público. */
export function Portada({ url, estilo, opacidad, className = '' }: { url: string | null; estilo: EstiloFondo; opacidad: number; className?: string }) {
    if (!url) {
        return null;
    }

    return (
        <img
            src={url}
            alt=""
            aria-hidden="true"
            decoding="async"
            className={`portada ${estilo === 'degradado' ? 'portada-degradado' : ''} ${className}`}
            style={estilo === 'completa' ? { opacity: Math.min(50, Math.max(10, opacidad)) / 100 } : undefined}
        />
    );
}
