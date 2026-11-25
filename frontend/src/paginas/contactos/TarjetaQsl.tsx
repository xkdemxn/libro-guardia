import { useEffect, useMemo, useRef, useState, type FormEvent } from 'react';
import { Link, useParams } from 'react-router';
import { keepPreviousData, useQuery, useQueryClient } from '@tanstack/react-query';
import { Cargando } from '@/componentes/Cargando';
import { Aviso, BotonPrimario, Campo, ErrorCampo } from '@/componentes/Formulario';
import { Icono } from '@/componentes/Icono';
import { Panel } from '@/componentes/Panel';
import { SelectorFondo, type EstadoFondo } from '@/componentes/SelectorFondo';
import { useUsuario } from '@/hooks/useUsuario';
import { api, erroresDeValidacion, mensajeDeError, type ErroresValidacion } from '@/lib/api';
import { guardarBlob } from '@/lib/descargas';
import type { DisenoTarjeta, PersonalizacionTarjeta, Tarjeta } from '@/lib/tipos';
import { ContactoNoEncontrado } from '@/paginas/contactos/DetalleContacto';
import { Trans, useTranslation } from 'react-i18next';
import { tx } from '@/i18n';

/**
 * Distribuciones de la tarjeta. La miniatura dibuja dónde va cada bloque: indicativo (blanco),
 * recuadro del QSO (azul), mensaje (gris) y sello con la bandera (ámbar).
 */
const DISENOS: { valor: DisenoTarjeta; titulo: string; detalle: string; bloques: [number, number, number, number, string][] }[] = [
    {
        valor: 'clasico',
        titulo: tx('Clásico'),
        detalle: tx('Indicativo arriba a la izquierda y el QSO a la derecha.'),
        bloques: [[4, 5, 30, 9, 'bg-white'], [62, 5, 34, 22, 'bg-acento-400'], [4, 70, 34, 6, 'bg-slate-400'], [78, 60, 18, 18, 'bg-amber-400']],
    },
    {
        valor: 'invertido',
        titulo: tx('Invertido'),
        detalle: tx('El QSO a la izquierda y tu indicativo a la derecha.'),
        bloques: [[66, 5, 30, 9, 'bg-white'], [4, 5, 34, 22, 'bg-acento-400'], [62, 70, 34, 6, 'bg-slate-400'], [4, 60, 18, 18, 'bg-amber-400']],
    },
    {
        valor: 'inferior',
        titulo: tx('Foto arriba'),
        detalle: tx('Deja libre la parte de arriba para lucir la foto.'),
        bloques: [[4, 44, 30, 9, 'bg-white'], [62, 33, 34, 22, 'bg-acento-400'], [4, 70, 34, 6, 'bg-slate-400'], [78, 60, 18, 18, 'bg-amber-400']],
    },
    {
        valor: 'centrado',
        titulo: tx('Centrado'),
        detalle: tx('Indicativo grande al centro y el QSO debajo.'),
        bloques: [[30, 5, 40, 11, 'bg-white'], [32, 33, 36, 22, 'bg-acento-400'], [4, 70, 34, 6, 'bg-slate-400'], [78, 60, 18, 18, 'bg-amber-400']],
    },
];

const SUGERENCIAS = [tx('¡Gracias por el QSO y 73!'), tx('Los Andes, serranía ecuatoriana.\nTNX QSO 73'), tx('Un placer el contacto. ¡Buen DX!')];

/**
 * Parte el mensaje de la tarjeta en líneas de hasta 64 caracteres (máximo 3), respetando los saltos
 * de línea. Es la misma regla que User::lineasMensajeQsl() en el servidor.
 */
export function partirMensajeQsl(texto: string, ancho = 64, maximo = 3): string[] {
    const lineas: string[] = [];

    for (const parrafo of texto.split(/\r?\n/)) {
        let actual = '';
        for (const palabra of parrafo.trim().split(/\s+/).filter(Boolean)) {
            if (actual !== '' && `${actual} ${palabra}`.length > ancho) {
                lineas.push(actual);
                actual = palabra;
            } else {
                actual = actual === '' ? palabra : `${actual} ${palabra}`;
            }
        }
        if (actual !== '') {
            lineas.push(actual);
        }
    }

    return lineas.slice(0, maximo);
}

const fondoDe = (personalizacion: PersonalizacionTarjeta): EstadoFondo => ({
    previa: personalizacion.foto,
    archivo: null,
    quitar: false,
    estilo: personalizacion.estilo,
    opacidad: personalizacion.opacidad,
});

/** Tarjeta QSL: vista previa en vivo, personalización (foto y mensaje), descarga en PNG o SVG y envío por correo. */
export function TarjetaQsl() {
    const { id } = useParams();
    const consulta = useQuery({
        queryKey: ['tarjeta', id],
        queryFn: async () => (await api.get<Tarjeta>(`/contactos/${id}/tarjeta`)).data,
        retry: false,
    });

    if (consulta.isError) {
        return <ContactoNoEncontrado />;
    }

    if (!consulta.data) {
        return <Cargando />;
    }

    return <PaginaTarjeta key={consulta.dataUpdatedAt} id={id!} tarjeta={consulta.data} />;
}

function PaginaTarjeta({ id, tarjeta }: { id: string; tarjeta: Tarjeta }) {
    const { t } = useTranslation();
    const cliente = useQueryClient();
    const { data: usuario } = useUsuario();
    const contenedor = useRef<HTMLDivElement>(null);
    const original = tarjeta.personalizacion;

    const [fondo, setFondo] = useState(() => fondoDe(original));
    const [mensaje, setMensaje] = useState(original.mensaje ?? '');
    const [diseno, setDiseno] = useState<DisenoTarjeta>(original.diseno);

    // Otro diseño necesita que el servidor vuelva a dibujar la tarjeta (sin guardarlo todavía).
    const vistaDiseno = useQuery({
        queryKey: ['tarjeta-diseno', id, diseno],
        queryFn: async () => (await api.get<Tarjeta>(`/contactos/${id}/tarjeta`, { params: { diseno } })).data.svg,
        enabled: diseno !== original.diseno,
        placeholderData: keepPreviousData,
        staleTime: Infinity,
    });
    const svg = diseno === original.diseno ? tarjeta.svg : (vistaDiseno.data ?? tarjeta.svg);
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [aviso, setAviso] = useState<{ tipo: 'exito' | 'error'; texto: string } | null>(null);
    const [guardando, setGuardando] = useState(false);
    const [generando, setGenerando] = useState(false);
    const [errorPng, setErrorPng] = useState<string | null>(null);

    const lineas = useMemo(() => partirMensajeQsl(mensaje.trim() || original.mensaje_predeterminado), [mensaje, original.mensaje_predeterminado]);
    const hayCambios =
        diseno !== original.diseno ||
        fondo.archivo !== null || fondo.quitar || fondo.estilo !== original.estilo || fondo.opacidad !== original.opacidad || mensaje.trim() !== (original.mensaje ?? '');

    // Vista previa en vivo: la foto y el mensaje se cambian directamente en el SVG de la tarjeta.
    useEffect(() => {
        const svg = contenedor.current?.querySelector('svg');
        if (!svg) {
            return;
        }

        const grupo = svg.querySelector<SVGGElement>('#qsl-foto');
        const fondoNegro = svg.querySelector<SVGRectElement>('#qsl-foto-fondo');
        const imagen = svg.querySelector<SVGImageElement>('#qsl-foto-imagen');
        const visible = Boolean(fondo.previa) && !fondo.quitar;

        if (grupo && fondoNegro && imagen) {
            grupo.style.display = visible ? '' : 'none';
            fondoNegro.style.display = fondo.estilo === 'completa' ? '' : 'none';
            if (fondo.previa) {
                imagen.setAttribute('href', fondo.previa);
            }
            if (fondo.estilo === 'degradado') {
                imagen.setAttribute('mask', 'url(#mascaraFoto)');
                imagen.removeAttribute('opacity');
            } else {
                imagen.removeAttribute('mask');
                imagen.setAttribute('opacity', String(fondo.opacidad / 100));
            }
        }

        svg.querySelectorAll<SVGTextElement>('text[data-linea]').forEach((texto) => {
            const indice = Number(texto.dataset.linea);
            texto.textContent = lineas[indice] ?? '';
            texto.setAttribute('y', String(788 - (lineas.length - 1 - indice) * 34));
        });
    }, [fondo, lineas, svg]);

    async function descargarPng() {
        setGenerando(true);
        setErrorPng(null);

        try {
            const svg = contenedor.current!.querySelector('svg')!.cloneNode(true) as SVGSVGElement;
            svg.setAttribute('width', '1400');
            svg.setAttribute('height', '900');

            const url = URL.createObjectURL(new Blob([new XMLSerializer().serializeToString(svg)], { type: 'image/svg+xml;charset=utf-8' }));
            const imagen = new Image();
            await new Promise((resolver, rechazar) => {
                imagen.onload = resolver;
                imagen.onerror = rechazar;
                imagen.src = url;
            });

            const lienzo = document.createElement('canvas');
            lienzo.width = 2800;
            lienzo.height = 1800;
            const contexto = lienzo.getContext('2d')!;
            contexto.scale(2, 2);
            contexto.drawImage(imagen, 0, 0, 1400, 900);
            URL.revokeObjectURL(url);

            const png = await new Promise<Blob | null>((resolver) => lienzo.toBlob(resolver, 'image/png'));
            if (!png) {
                throw new Error(t('Sin imagen'));
            }
            guardarBlob(png, `${tarjeta.nombre_archivo}.png`);
        } catch {
            setErrorPng(t('No se pudo generar la imagen. Prueba con «Descargar SVG».'));
        } finally {
            setGenerando(false);
        }
    }

    async function guardar(evento: FormEvent) {
        evento.preventDefault();
        setGuardando(true);
        setErrores({});
        setAviso(null);

        const datos = new FormData();
        datos.append('_method', 'PATCH');
        if (fondo.archivo) {
            datos.append('qsl_foto', fondo.archivo);
        }
        datos.append('quitar_qsl_foto', fondo.quitar ? '1' : '0');
        datos.append('qsl_foto_estilo', fondo.estilo);
        datos.append('qsl_foto_opacidad', String(fondo.opacidad));
        datos.append('qsl_mensaje', mensaje);
        datos.append('qsl_diseno', diseno);

        try {
            const { data } = await api.post<{ mensaje: string }>('/tarjeta-qsl', datos);
            // La tarjeta se vuelve a pedir: así el SVG descargable ya lleva los cambios.
            await cliente.invalidateQueries({ queryKey: ['tarjeta'] });
            setAviso({ tipo: 'exito', texto: data.mensaje });
        } catch (error) {
            const validacion = erroresDeValidacion(error);
            setErrores(validacion);
            if (Object.keys(validacion).length === 0) {
                setAviso({ tipo: 'error', texto: mensajeDeError(error) });
            }
        } finally {
            setGuardando(false);
        }
    }

    return (
        <>
            <header className="mx-auto max-w-5xl px-4 pt-10 sm:px-6 lg:px-8">
                <Link to={`/contactos/${id}`} className="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">
                    &lsaquo; Volver al contacto
                </Link>
                <h1 className="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">{t('Tarjeta QSL')}</h1>
                <p className="mt-1 text-slate-400">{t('Envíasela a la otra estación para confirmar el contacto, como las tarjetas postales de antes.')}</p>
            </header>

            <div className="mx-auto box-content max-w-5xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
                <div className="space-y-4">
                    <div
                        ref={contenedor}
                        className="overflow-hidden rounded-[1.6rem] shadow-vidrio-lg ring-1 ring-white/10 [&>svg]:h-auto [&>svg]:w-full"
                        dangerouslySetInnerHTML={{ __html: svg }}
                    />

                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <p className="text-sm text-slate-500">
                            {hayCambios ? (
                                <span className="text-amber-300/90">{t('Tienes cambios sin guardar: el PNG ya los incluye; guarda para que también salgan en el SVG.')}</span>
                            ) : (
                                <Trans
                                    i18nKey="Tus datos salen de <enlace>tu perfil</enlace>: ciudad, grid locator, equipo y antena."
                                    components={{ enlace: <Link to="/perfil#estacion" className="text-acento-300 hover:text-acento-200" /> }}
                                />
                            )}
                        </p>
                        <div className="flex flex-wrap gap-3">
                            <a
                                href={`/api/contactos/${id}/tarjeta.svg`}
                                download
                                className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-semibold text-slate-100 transition hover:bg-white/10"
                            >
                                {t('Descargar SVG')}
                            </a>
                            <BotonPrimario type="button" onClick={descargarPng} disabled={generando}>
                                <Icono nombre="importar" className="h-4 w-4" />
                                {generando ? t('Generando…') : t('Descargar PNG')}
                            </BotonPrimario>
                        </div>
                    </div>
                    {errorPng && (
                        <p role="alert" className="text-right text-sm text-rose-400">
                            {errorPng}
                        </p>
                    )}
                </div>

                <EnviarPorCorreo id={id} tarjeta={tarjeta} correoVerificado={usuario?.correo_verificado ?? false} hayCambios={hayCambios} />

                {/* Personalización: se aplica a todas las tarjetas del operador */}
                <form onSubmit={guardar} noValidate>
                    <Panel ceja={t('Personalizar tarjeta')} className="sm:!p-8">
                        <p className="-mt-2 text-center text-sm text-slate-400">
                            Los cambios se ven en vivo arriba y se aplican a <strong className="font-semibold text-slate-200">{t('todas')}</strong> tus tarjetas.
                        </p>
                        {aviso && (
                            <Aviso tipo={aviso.tipo} className="mt-6">
                                {aviso.texto}
                            </Aviso>
                        )}

                        <fieldset className="mt-6">
                            <legend className="font-display text-base font-semibold text-white">{t('Diseño')}</legend>
                            <p className="mt-1 text-sm text-slate-400">{t('Dónde va cada parte. Elige según tu foto, para no tapar lo más bonito.')}</p>
                            <div className="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                                {DISENOS.map((opcion) => (
                                    <label
                                        key={opcion.valor}
                                        className={`cursor-pointer rounded-2xl p-3 ring-1 transition ${
                                            diseno === opcion.valor ? 'bg-acento-400/10 ring-acento-400/50' : 'bg-white/[0.02] ring-white/10 hover:bg-white/[0.05]'
                                        }`}
                                    >
                                        <input type="radio" name="qsl_diseno" value={opcion.valor} checked={diseno === opcion.valor} onChange={() => setDiseno(opcion.valor)} className="sr-only" />
                                        <span className="relative block aspect-[14/9] overflow-hidden rounded-lg bg-gradient-to-b from-noche-700 via-rose-900/60 to-amber-700/60 ring-1 ring-white/10" aria-hidden="true">
                                            {opcion.bloques.map(([x, y, ancho, alto, color], indice) => (
                                                <span
                                                    key={indice}
                                                    className={`absolute rounded-[3px] ${color} ${color === 'bg-acento-400' ? 'opacity-80' : 'opacity-90'}`}
                                                    style={{ left: `${x}%`, top: `${y}%`, width: `${ancho}%`, height: `${alto}%` }}
                                                />
                                            ))}
                                            <span className="absolute inset-x-0 bottom-0 h-[7%] bg-amber-400" />
                                        </span>
                                        <span className="mt-2.5 block text-sm font-medium text-slate-100">{t(opcion.titulo)}</span>
                                        <span className="mt-0.5 block text-xs text-slate-500">{t(opcion.detalle)}</span>
                                    </label>
                                ))}
                            </div>
                            <ErrorCampo mensajes={errores.qsl_diseno} />
                        </fieldset>

                        <div className="mt-8 grid gap-8 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                            <div>
                                <h2 className="font-display text-base font-semibold text-white">{t('Foto')}</h2>
                                <p className="mt-1 text-sm text-slate-400">{t('De ti, de tu estación o de tu ciudad, como las tarjetas impresas de siempre.')}</p>
                                <SelectorFondo
                                    campo="qsl_foto"
                                    fondo={fondo}
                                    onCambiar={setFondo}
                                    guardada={original.foto !== null}
                                    quitarTexto={t('Quitar foto')}
                                    lado={1400}
                                    estilos={{
                                        completa: [t('Foto completa'), t('Cubre toda la tarjeta, como las QSL impresas.')],
                                        degradado: [t('Foto a la derecha'), t('Se desvanece hacia la izquierda sobre el atardecer.')],
                                    }}
                                    opacidadMaxima={100}
                                    textoOpacidad={t('Bájala si quieres que se vea el atardecer de fondo.')}
                                    errores={errores}
                                />
                            </div>

                            <div>
                                <h2 className="font-display text-base font-semibold text-white">{t('Mensaje')}</h2>
                                <p className="mt-1 text-sm text-slate-400">{t('Abajo a la izquierda, hasta 3 líneas: cuenta qué se ve en la foto o deja un saludo.')}</p>
                                <div className="mt-6">
                                    <label htmlFor="qsl_mensaje" className="sr-only">
                                        {t('Mensaje de la tarjeta')}
                                    </label>
                                    <textarea
                                        id="qsl_mensaje"
                                        rows={4}
                                        maxLength={original.mensaje_maximo}
                                        value={mensaje}
                                        onChange={(evento) => setMensaje(evento.target.value)}
                                        placeholder={original.mensaje_predeterminado}
                                        className="campo resize-none"
                                    />
                                    <div className="mt-1.5 flex items-center justify-between text-xs text-slate-500">
                                        <span>{t('Enter para cambiar de línea · vacío usa el mensaje por defecto.')}</span>
                                        <span className="tabular-nums">
                                            {mensaje.length} / {original.mensaje_maximo}
                                        </span>
                                    </div>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {SUGERENCIAS.map((sugerencia) => (
                                            <button
                                                key={sugerencia}
                                                type="button"
                                                onClick={() => setMensaje(t(sugerencia))}
                                                className="rounded-full bg-white/5 px-3 py-1 text-xs text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10"
                                            >
                                                {t(sugerencia).replace('\n', ' · ')}
                                            </button>
                                        ))}
                                    </div>
                                    <ErrorCampo mensajes={errores.qsl_mensaje} />
                                </div>
                            </div>
                        </div>

                        <div className="mt-8 flex justify-end">
                            <BotonPrimario className="!px-7" disabled={!hayCambios || guardando}>
                                {guardando ? t('Guardando…') : t('Guardar cambios')}
                            </BotonPrimario>
                        </div>
                    </Panel>
                </form>
            </div>
        </>
    );
}

function EnviarPorCorreo({ id, tarjeta, correoVerificado, hayCambios }: { id: string; tarjeta: Tarjeta; correoVerificado: boolean; hayCambios: boolean }) {
    const { t } = useTranslation();
    const cliente = useQueryClient();
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [aviso, setAviso] = useState<{ tipo: 'exito' | 'error'; texto: string } | null>(null);
    const [enviando, setEnviando] = useState(false);

    async function enviar(evento: FormEvent<HTMLFormElement>) {
        evento.preventDefault();
        setEnviando(true);
        setErrores({});
        setAviso(null);

        try {
            const { data } = await api.post<{ mensaje: string }>(`/contactos/${id}/tarjeta/enviar`, { destinatario: new FormData(evento.currentTarget).get('destinatario') });
            setAviso({ tipo: 'exito', texto: data.mensaje });
            void cliente.invalidateQueries({ queryKey: ['contacto', id] });
            void cliente.invalidateQueries({ queryKey: ['contactos'] });
        } catch (error) {
            const validacion = erroresDeValidacion(error);
            setErrores(validacion);
            if (Object.keys(validacion).length === 0) {
                setAviso({ tipo: 'error', texto: mensajeDeError(error) });
            }
        } finally {
            setEnviando(false);
        }
    }

    return (
        <Panel className="sm:!p-8">
            <h2 className="font-display text-base font-semibold text-white">{t('Enviar tarjeta por correo')}</h2>
            {!tarjeta.envio_disponible ? (
                <p className="mt-2 text-sm text-slate-400">{t('Configura el servicio de correo para enviar la tarjeta desde Libro de Guardia.')}</p>
            ) : !correoVerificado ? (
                <p className="mt-2 text-sm text-slate-400">
                    <Trans
                        i18nKey="<enlace>Verifica tu correo</enlace> para enviar tarjetas."
                        components={{ enlace: <Link to="/perfil#datos" className="text-acento-300 underline" /> }}
                    />
                </p>
            ) : (
                <form onSubmit={enviar} className="mt-4 space-y-3" noValidate>
                    {aviso && <Aviso tipo={aviso.tipo}>{aviso.texto}</Aviso>}
                    <div className="sm:max-w-sm">
                        <Campo id="destinatario" etiqueta={t('Correo del destinatario')} type="email" maxLength={254} required errores={errores.destinatario} />
                    </div>
                    {hayCambios && <p className="text-sm text-amber-300">{t('Guarda el diseño antes de enviarlo.')}</p>}
                    <BotonPrimario disabled={hayCambios || enviando}>{enviando ? t('Enviando…') : t('Enviar PNG')}</BotonPrimario>
                </form>
            )}
        </Panel>
    );
}
