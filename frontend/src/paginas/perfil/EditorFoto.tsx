import { useEffect, useRef, useState, type PointerEvent, type WheelEvent } from 'react';
import { Dialogo } from '@/componentes/Dialogo';
import { BotonPrimario, BotonSecundario } from '@/componentes/Formulario';

const LADO = 288;
const SALIDA = 512;
const ZOOM_MAXIMO = 4;

interface Encuadre {
    escala: number;
    escalaMinima: number;
    x: number;
    y: number;
}

/**
 * Editor para recortar la foto de perfil: se arrastra para mover y se acerca con el deslizador
 * o la rueda del mouse. Al aplicar, dibuja el cuadro visible en un lienzo de 512×512 y entrega un
 * JPG: así la foto se sube ya recortada y liviana, aunque la original pese mucho.
 */
export function EditorFoto({ src, onCerrar, onAplicar }: { src: string | null; onCerrar: () => void; onAplicar: (foto: Blob) => void }) {
    const [imagen, setImagen] = useState<HTMLImageElement | null>(null);
    const [encuadre, setEncuadre] = useState<Encuadre>({ escala: 1, escalaMinima: 1, x: 0, y: 0 });
    const arrastre = useRef<{ px: number; py: number; x: number; y: number } | null>(null);
    const [arrastrando, setArrastrando] = useState(false);

    useEffect(() => {
        if (!src) {
            setImagen(null);

            return;
        }

        const nueva = new Image();
        nueva.onload = () => {
            setImagen(nueva);
            setEncuadre(centrado(nueva));
        };
        nueva.src = src;
    }, [src]);

    function limitar(e: Encuadre, img: HTMLImageElement): Encuadre {
        return {
            ...e,
            x: Math.min(0, Math.max(LADO - img.naturalWidth * e.escala, e.x)),
            y: Math.min(0, Math.max(LADO - img.naturalHeight * e.escala, e.y)),
        };
    }

    /** Cambia el zoom manteniendo fijo el punto (cx, cy) del cuadro; por defecto, el centro. */
    function acercarA(escala: number, cx = LADO / 2, cy = LADO / 2) {
        if (!imagen) {
            return;
        }

        setEncuadre((e) => {
            const nueva = Math.min(e.escalaMinima * ZOOM_MAXIMO, Math.max(e.escalaMinima, escala));

            return limitar({ ...e, escala: nueva, x: cx - (cx - e.x) * (nueva / e.escala), y: cy - (cy - e.y) * (nueva / e.escala) }, imagen);
        });
    }

    function empezarArrastre(evento: PointerEvent<HTMLDivElement>) {
        evento.preventDefault();
        evento.currentTarget.setPointerCapture(evento.pointerId);
        arrastre.current = { px: evento.clientX, py: evento.clientY, x: encuadre.x, y: encuadre.y };
        setArrastrando(true);
    }

    function arrastrar(evento: PointerEvent<HTMLDivElement>) {
        const inicio = arrastre.current;
        if (!inicio || !imagen) {
            return;
        }

        setEncuadre((e) => limitar({ ...e, x: inicio.x + evento.clientX - inicio.px, y: inicio.y + evento.clientY - inicio.py }, imagen));
    }

    function soltar() {
        arrastre.current = null;
        setArrastrando(false);
    }

    function rueda(evento: WheelEvent<HTMLDivElement>) {
        const cuadro = evento.currentTarget.getBoundingClientRect();
        acercarA(encuadre.escala * (evento.deltaY < 0 ? 1.08 : 1 / 1.08), evento.clientX - cuadro.left, evento.clientY - cuadro.top);
    }

    function aplicar() {
        if (!imagen) {
            return;
        }

        const lienzo = document.createElement('canvas');
        lienzo.width = lienzo.height = SALIDA;
        const contexto = lienzo.getContext('2d')!;
        contexto.fillStyle = '#ffffff';
        contexto.fillRect(0, 0, SALIDA, SALIDA);
        contexto.imageSmoothingQuality = 'high';
        const { x, y, escala } = encuadre;
        contexto.drawImage(imagen, -x / escala, -y / escala, LADO / escala, LADO / escala, 0, 0, SALIDA, SALIDA);
        lienzo.toBlob((datos) => datos && onAplicar(datos), 'image/jpeg', 0.9);
    }

    const zoom = Math.round((encuadre.escala / encuadre.escalaMinima) * 100);

    return (
        <Dialogo abierto={src !== null && imagen !== null} onCerrar={onCerrar} titulo="Ajustar foto">
            <p className="mt-1 text-sm text-slate-400">Arrastra la foto para moverla y usa el deslizador para acercarla.</p>

            {/* Área de recorte: lo que se ve dentro del cuadro es lo que se guarda */}
            <div className="mt-5 flex justify-center">
                <div
                    className="relative cursor-grab touch-none select-none overflow-hidden rounded-[2.5rem] bg-noche-900 ring-1 ring-white/15 active:cursor-grabbing"
                    style={{ width: LADO, height: LADO }}
                    onPointerDown={empezarArrastre}
                    onPointerMove={arrastrar}
                    onPointerUp={soltar}
                    onPointerCancel={soltar}
                    onWheel={rueda}
                >
                    {imagen && (
                        <img
                            src={imagen.src}
                            alt=""
                            draggable={false}
                            className="pointer-events-none absolute left-0 top-0 max-w-none origin-top-left"
                            style={{
                                width: imagen.naturalWidth,
                                height: imagen.naturalHeight,
                                transform: `translate(${encuadre.x}px, ${encuadre.y}px) scale(${encuadre.escala})`,
                            }}
                        />
                    )}

                    {/* Guías de tercios, solo mientras se arrastra */}
                    <div className={`pointer-events-none absolute inset-0 transition-opacity duration-200 ${arrastrando ? 'opacity-100' : 'opacity-0'}`}>
                        <div className="absolute inset-y-0 left-1/3 w-px bg-blanco/50" />
                        <div className="absolute inset-y-0 left-2/3 w-px bg-blanco/50" />
                        <div className="absolute inset-x-0 top-1/3 h-px bg-blanco/50" />
                        <div className="absolute inset-x-0 top-2/3 h-px bg-blanco/50" />
                    </div>
                </div>
            </div>

            {/* Zoom */}
            <div className="mt-5 flex items-center gap-3">
                <button
                    type="button"
                    onClick={() => acercarA(encuadre.escala / 1.15)}
                    className="grid h-8 w-8 shrink-0 place-items-center rounded-full text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10"
                    aria-label="Alejar"
                >
                    −
                </button>
                <label htmlFor="zoom-foto" className="sr-only">
                    Acercar
                </label>
                <input
                    id="zoom-foto"
                    type="range"
                    min={100}
                    max={ZOOM_MAXIMO * 100}
                    step={1}
                    value={zoom}
                    onChange={(evento) => acercarA((encuadre.escalaMinima * Number(evento.target.value)) / 100)}
                    className="h-1.5 flex-1 cursor-pointer accent-marca-500"
                />
                <button
                    type="button"
                    onClick={() => acercarA(encuadre.escala * 1.15)}
                    className="grid h-8 w-8 shrink-0 place-items-center rounded-full text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10"
                    aria-label="Acercar"
                >
                    +
                </button>
            </div>

            <div className="mt-6 flex items-center justify-between gap-3">
                <button type="button" onClick={() => imagen && setEncuadre(centrado(imagen))} className="text-sm text-slate-400 transition hover:text-white">
                    Restablecer
                </button>
                <div className="flex gap-2">
                    <BotonSecundario onClick={onCerrar}>Cancelar</BotonSecundario>
                    <BotonPrimario type="button" onClick={aplicar}>
                        Usar esta foto
                    </BotonPrimario>
                </div>
            </div>
        </Dialogo>
    );
}

function centrado(imagen: HTMLImageElement): Encuadre {
    const escala = LADO / Math.min(imagen.naturalWidth, imagen.naturalHeight);

    return {
        escala,
        escalaMinima: escala,
        x: (LADO - imagen.naturalWidth * escala) / 2,
        y: (LADO - imagen.naturalHeight * escala) / 2,
    };
}
