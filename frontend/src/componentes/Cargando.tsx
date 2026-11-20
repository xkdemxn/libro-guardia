import { AntenaAnimada } from '@/componentes/AntenaAnimada';

/** Mientras la SPA averigua si hay sesión o descarga una página: la antena buscando señal. */
export function Cargando() {
    return (
        <div className="flex min-h-[60vh] w-full flex-col items-center justify-center gap-3" role="status" aria-live="polite">
            <AntenaAnimada className="h-24 w-24" />
            <span className="text-sm tracking-wide text-slate-400">Buscando señal…</span>
        </div>
    );
}
