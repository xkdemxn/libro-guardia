/** Indicador mientras la SPA averigua si hay sesión o carga una página. */
export function Cargando() {
    return (
        <div className="grid min-h-screen place-items-center" role="status" aria-live="polite">
            <span className="flex items-center gap-3 text-sm text-slate-400">
                <span className="h-5 w-5 animate-spin rounded-full border-2 border-acento-400/30 border-t-acento-400" />
                Cargando…
            </span>
        </div>
    );
}
