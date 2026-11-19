import { useEffect, useState } from 'react';
import { fechaUtc, horaUtc } from '@/lib/utc';

/** Reloj UTC en vivo con aspecto de display de radio. En radioafición todo se anota en UTC. */
export function RelojUtc({ grande = false, className = '' }: { grande?: boolean; className?: string }) {
    const [ahora, setAhora] = useState(() => new Date());

    useEffect(() => {
        const intervalo = setInterval(() => setAhora(new Date()), 1000);

        return () => clearInterval(intervalo);
    }, []);

    return (
        <div
            className={`inline-flex items-baseline gap-2 rounded-full border border-dial-400/20 bg-black/30 font-mono text-dial-400 ${grande ? 'px-5 py-2' : 'px-3 py-1'} ${className}`}
        >
            <span className={`${grande ? 'text-xs' : 'text-[0.6875rem]'} font-sans font-semibold uppercase tracking-[0.2em] text-dial-300/70`}>UTC</span>
            <span
                className={`${grande ? 'text-3xl' : 'text-sm'} font-semibold tabular-nums tracking-wider [text-shadow:0_0_12px_rgb(255_184_74/0.45)]`}
                role="timer"
                aria-label="Hora UTC actual"
            >
                {horaUtc(ahora, true)}
            </span>
            {grande && <span className="whitespace-nowrap text-xs tabular-nums text-slate-500">{fechaUtc(ahora)}</span>}
        </div>
    );
}
