import { Ceja } from '@/componentes/Ceja';

/**
 * Secciones que todavía no se pasan a React. Mientras dura la migración siguen funcionando
 * en la versión Blade (Laravel en el puerto 8000), con la misma sesión.
 */
export function EnConstruccion({ titulo, rutaBlade }: { titulo: string; rutaBlade: string }) {
    const urlBlade = `${import.meta.env.VITE_URL_LARAVEL ?? 'http://localhost:8000'}${rutaBlade}`;

    return (
        <div className="mx-auto max-w-3xl px-4 pt-16 sm:px-6 lg:px-8">
            <div className="vidrio p-8 text-center">
                <Ceja className="mb-6">En migración a React</Ceja>
                <h1 className="font-display text-2xl font-semibold text-white">{titulo}</h1>
                <p className="mx-auto mt-3 max-w-md text-sm text-slate-400">
                    Esta sección todavía no está en la nueva interfaz. Mientras tanto puedes usarla en la versión anterior, con tu misma sesión.
                </p>
                <a
                    href={urlBlade}
                    className="mt-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-semibold text-slate-100 transition hover:bg-white/10"
                >
                    Abrir en la versión anterior &rsaquo;
                </a>
            </div>
        </div>
    );
}
