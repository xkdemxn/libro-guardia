/**
 * Torre de antena que transmite: ondas que salen del dipolo en pulsos y una luz de baliza que parpadea.
 * Decorativa. Con «reducir movimiento» activado en el sistema, se queda quieta (ver estilos.css).
 */
export function AntenaAnimada({ className = 'h-28 w-28' }: { className?: string }) {
    return (
        <svg viewBox="0 0 120 120" className={`antena-animada ${className}`} aria-hidden="true" fill="none">
            {/* Ondas a cada lado del dipolo */}
            {[0, 1, 2].map((indice) => (
                <g key={indice} className="onda" style={{ animationDelay: `${indice * 0.8}s` }}>
                    <path d="M44 22a20 20 0 0 0 0 28" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" className="text-acento-300" />
                    <path d="M76 22a20 20 0 0 1 0 28" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" className="text-acento-300" />
                </g>
            ))}

            {/* Dipolo y mástil de celosía */}
            <path d="M40 36h40" stroke="currentColor" strokeWidth="3" strokeLinecap="round" className="text-slate-200" />
            <path d="M60 36 48 108M60 36l12 72M50 96h20M52 82h16M54 68h12M56 54h8" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-slate-400" />
            <path d="M48 108 72 82M72 108 48 82M52 82l14-14M68 82 54 68M54 68l10-14M66 68 56 54" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" className="text-slate-500" />
            <path d="M40 108h40" stroke="currentColor" strokeWidth="3" strokeLinecap="round" className="text-slate-300" />

            {/* Baliza en la punta */}
            <circle cx="60" cy="30" r="4" className="baliza fill-dial-400" />
        </svg>
    );
}
