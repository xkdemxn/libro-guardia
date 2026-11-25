/** Interruptor estilo iOS. */
export function Interruptor({
    nombre,
    etiqueta,
    descripcion,
    activo,
    onCambiar,
}: {
    nombre: string;
    etiqueta: string;
    descripcion?: string;
    activo: boolean;
    onCambiar: (activo: boolean) => void;
}) {
    return (
        <label className="flex cursor-pointer items-center justify-between gap-4 py-3">
            <span>
                <span className="block text-sm font-medium text-slate-200">{etiqueta}</span>
                {descripcion && <span className="block text-xs text-slate-500">{descripcion}</span>}
            </span>
            <span className="relative inline-flex shrink-0">
                <input type="checkbox" name={nombre} className="peer sr-only" checked={activo} onChange={(evento) => onCambiar(evento.target.checked)} />
                <span className="h-7 w-12 rounded-full bg-white/10 ring-1 ring-white/10 transition peer-checked:bg-emerald-500 peer-checked:ring-emerald-400/50 peer-focus-visible:ring-2 peer-focus-visible:ring-acento-400" />
                <span className="absolute left-0.5 top-0.5 h-6 w-6 rounded-full bg-blanco shadow-md transition peer-checked:translate-x-5" />
            </span>
        </label>
    );
}
