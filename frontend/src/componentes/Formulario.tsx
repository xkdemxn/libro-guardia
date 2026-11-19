import type { ButtonHTMLAttributes, InputHTMLAttributes, ReactNode } from 'react';

export function Etiqueta({ htmlFor, children }: { htmlFor: string; children: ReactNode }) {
    return (
        <label htmlFor={htmlFor} className="mb-1.5 block text-[0.8125rem] font-medium text-slate-400">
            {children}
        </label>
    );
}

export function ErrorCampo({ mensajes }: { mensajes?: string[] }) {
    if (!mensajes?.length) {
        return null;
    }

    return (
        <ul className="mt-1.5 space-y-1 text-[0.8125rem] text-rose-400">
            {mensajes.map((mensaje) => (
                <li key={mensaje} className="flex items-start gap-1.5">
                    <span className="punto mt-1.5 bg-rose-400" />
                    <span>{mensaje}</span>
                </li>
            ))}
        </ul>
    );
}

interface PropsCampo extends InputHTMLAttributes<HTMLInputElement> {
    id: string;
    etiqueta: ReactNode;
    errores?: string[];
    /** Algo que va a la derecha de la etiqueta (p. ej. «¿La olvidaste?»). */
    extra?: ReactNode;
}

/** Etiqueta + campo de vidrio + errores de validación de Laravel. */
export function Campo({ id, etiqueta, errores, extra, className = '', ...props }: PropsCampo) {
    return (
        <div>
            {extra ? (
                <div className="flex items-baseline justify-between">
                    <Etiqueta htmlFor={id}>{etiqueta}</Etiqueta>
                    {extra}
                </div>
            ) : (
                <Etiqueta htmlFor={id}>{etiqueta}</Etiqueta>
            )}
            <input
                id={id}
                name={id}
                aria-invalid={errores?.length ? true : undefined}
                className={`campo ${className}`}
                {...props}
            />
            <ErrorCampo mensajes={errores} />
        </div>
    );
}

export function BotonPrimario({ className = '', type = 'submit', ...props }: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            type={type}
            className={`inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-b from-marca-400 to-marca-600 px-5 py-2.5 text-sm font-semibold text-blanco shadow-boton transition hover:brightness-110 focus:outline-none focus-visible:ring-4 focus-visible:ring-acento-400/30 active:scale-[0.97] disabled:opacity-50 ${className}`}
            {...props}
        />
    );
}

export function BotonSecundario({ className = '', type = 'button', ...props }: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            type={type}
            className={`inline-flex items-center justify-center gap-2 rounded-full border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-semibold text-slate-100 backdrop-blur-xl transition hover:bg-white/10 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/15 active:scale-[0.97] disabled:opacity-50 ${className}`}
            {...props}
        />
    );
}

/** Aviso verde (algo salió bien) o rojo (error general que no es de un campo). */
export function Aviso({ tipo = 'exito', children, className = '' }: { tipo?: 'exito' | 'error'; children: ReactNode; className?: string }) {
    const estilos =
        tipo === 'exito'
            ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-300'
            : 'border-rose-400/20 bg-rose-400/10 text-rose-300';

    return (
        <div
            role={tipo === 'exito' ? 'status' : 'alert'}
            className={`flex items-center gap-2 rounded-2xl border px-4 py-3 text-sm font-medium ${estilos} ${className}`}
        >
            <span className={`punto ${tipo === 'exito' ? 'bg-emerald-400' : 'bg-rose-400'}`} />
            {children}
        </div>
    );
}
