import type { Usuario } from '@/lib/tipos';

/** Foto del operador o, si no tiene, sus iniciales sobre el degradado azul de la marca. */
export function Avatar({ usuario, className = 'h-7 w-7 rounded-full text-[0.75rem]' }: { usuario: Usuario; className?: string }) {
    const iniciales = `${usuario.nombres.charAt(0)}${usuario.apellidos.charAt(0)}`.toUpperCase();

    return (
        <span className={`grid shrink-0 place-items-center overflow-hidden bg-gradient-to-br from-marca-400 to-marca-600 font-semibold text-blanco ${className}`}>
            {usuario.foto_url ? <img src={usuario.foto_url} alt="" className="h-full w-full object-cover" /> : iniciales}
        </span>
    );
}
