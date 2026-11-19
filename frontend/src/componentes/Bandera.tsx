/** Bandera del país (public/banderas de Laravel, de flag-icons). Sin código no muestra nada. */
export function Bandera({ codigo, pais, className = 'h-3.5 w-[1.15rem]' }: { codigo: string | null | undefined; pais?: string | null; className?: string }) {
    if (!codigo) {
        return null;
    }

    return (
        <img
            src={`/banderas/${codigo}.svg`}
            alt={`Bandera de ${pais ?? codigo.toUpperCase()}`}
            title={pais ?? undefined}
            loading="lazy"
            width={20}
            height={15}
            onError={(evento) => (evento.currentTarget.style.display = 'none')}
            className={`inline-block shrink-0 rounded-[3px] object-cover align-[-2px] ring-1 ring-white/15 ${className}`}
        />
    );
}
