import type { ReactNode } from 'react';
import { Ceja } from '@/componentes/Ceja';

/** Tarjeta de vidrio oscuro, con ceja opcional arriba. */
export function Panel({ ceja, children, className = '' }: { ceja?: ReactNode; children: ReactNode; className?: string }) {
    return (
        <section className={`vidrio p-5 sm:p-6 ${className}`}>
            {ceja && <Ceja className="mb-5">{ceja}</Ceja>}
            {children}
        </section>
    );
}
