import type { ReactNode } from 'react';

/** Título pequeño en mayúsculas, espaciado y entre líneas finas: «—— SISTEMA WEB ——». */
export function Ceja({ children, className = '' }: { children: ReactNode; className?: string }) {
    return (
        <div className={`flex items-center gap-2 sm:gap-4 ${className}`}>
            <span className="h-px flex-1 bg-gradient-to-r from-transparent to-white/15" />
            <span className="min-w-0 text-center text-[0.6875rem] font-medium uppercase tracking-[0.16em] text-slate-400 sm:text-[0.75rem] sm:tracking-[0.28em]">
                {children}
            </span>
            <span className="h-px flex-1 bg-gradient-to-l from-transparent to-white/15" />
        </div>
    );
}
