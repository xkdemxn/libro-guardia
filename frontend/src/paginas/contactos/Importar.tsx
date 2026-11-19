import { useState, type FormEvent } from 'react';
import { Link } from 'react-router';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Cargando } from '@/componentes/Cargando';
import { Ceja } from '@/componentes/Ceja';
import { ChipBanda } from '@/componentes/Contactos';
import { Aviso, BotonPrimario, ErrorCampo } from '@/componentes/Formulario';
import { Icono } from '@/componentes/Icono';
import { Panel } from '@/componentes/Panel';
import { api, erroresDeValidacion, esEstado, mensajeDeError, type ErroresValidacion } from '@/lib/api';
import type { FilaImportacion, ResultadoImportacion, VistaPreviaImportacion } from '@/lib/tipos';

/**
 * Importar contactos desde ADIF (US-14) en tres pasos: subir el archivo, revisar la vista previa
 * (nuevos, duplicados y con errores) y ver el resumen.
 */
export function Importar() {
    const cliente = useQueryClient();
    const [resultado, setResultado] = useState<ResultadoImportacion | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [procesando, setProcesando] = useState(false);

    // Si quedó una importación a medias (por ejemplo, al recargar la página), se retoma su vista previa.
    const pendiente = useQuery({
        queryKey: ['importacion'],
        queryFn: async () => {
            try {
                return (await api.get<VistaPreviaImportacion>('/importaciones/pendiente')).data;
            } catch (fallo) {
                if (esEstado(fallo, 404)) {
                    return null;
                }
                throw fallo;
            }
        },
        staleTime: 0,
    });

    async function confirmar() {
        setProcesando(true);
        setError(null);
        try {
            setResultado((await api.post<ResultadoImportacion>('/importaciones/pendiente/confirmar')).data);
            cliente.setQueryData(['importacion'], null);
            void cliente.invalidateQueries({ queryKey: ['contactos'] });
            void cliente.invalidateQueries({ queryKey: ['estadisticas'] });
        } catch (fallo) {
            setError(mensajeDeError(fallo));
        } finally {
            setProcesando(false);
        }
    }

    async function cancelar() {
        setProcesando(true);
        try {
            await api.delete('/importaciones/pendiente');
        } finally {
            cliente.setQueryData(['importacion'], null);
            setProcesando(false);
        }
    }

    if (pendiente.isPending) {
        return <Cargando />;
    }

    if (pendiente.data) {
        return <VistaPrevia datos={pendiente.data} procesando={procesando} error={error} onConfirmar={confirmar} onCancelar={cancelar} />;
    }

    return (
        <>
            <header className="mx-auto max-w-3xl px-4 pt-10 sm:px-6 lg:px-8">
                <Link to="/contactos" className="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">
                    &lsaquo; Volver al libro
                </Link>
                <h1 className="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">Importar contactos</h1>
                <p className="mt-1 text-slate-400">Trae tus contactos antiguos desde otro programa de log con un archivo ADIF.</p>
            </header>

            <div className="mx-auto box-content max-w-3xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
                {resultado && <Resumen resultado={resultado} />}
                <SubirArchivo onVistaPrevia={(datos) => {
                    setResultado(null);
                    cliente.setQueryData(['importacion'], datos);
                }} />
            </div>
        </>
    );
}

function SubirArchivo({ onVistaPrevia }: { onVistaPrevia: (datos: VistaPreviaImportacion) => void }) {
    const [archivo, setArchivo] = useState<File | null>(null);
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [enviando, setEnviando] = useState(false);

    async function subir(evento: FormEvent) {
        evento.preventDefault();
        if (!archivo) {
            setErrores({ archivo: ['Elige un archivo ADIF (.adi o .adif).'] });

            return;
        }

        setEnviando(true);
        setErrores({});
        const datos = new FormData();
        datos.append('archivo', archivo);

        try {
            onVistaPrevia((await api.post<VistaPreviaImportacion>('/importaciones', datos)).data);
        } catch (fallo) {
            const validacion = erroresDeValidacion(fallo);
            setErrores(Object.keys(validacion).length ? validacion : { archivo: [mensajeDeError(fallo)] });
        } finally {
            setEnviando(false);
        }
    }

    return (
        <Panel ceja="Subir archivo ADIF">
            <form onSubmit={subir} className="space-y-5" noValidate>
                <label className="flex cursor-pointer flex-col items-center gap-3 rounded-3xl border-2 border-dashed border-white/15 bg-white/[0.02] px-6 py-12 text-center transition hover:border-acento-400/50 hover:bg-acento-400/[0.04]">
                    <span className="grid h-14 w-14 place-items-center rounded-2xl bg-acento-400/10 text-acento-300 ring-1 ring-acento-400/25">
                        <Icono nombre="importar" className="h-7 w-7" />
                    </span>
                    <span className="font-medium text-white">{archivo?.name ?? 'Elige tu archivo .adi o .adif'}</span>
                    <span className="text-xs text-slate-500">Máximo 5 MB. Antes de importar verás una vista previa.</span>
                    <input type="file" accept=".adi,.adif" className="sr-only" onChange={(evento) => setArchivo(evento.target.files?.[0] ?? null)} />
                </label>
                <ErrorCampo mensajes={errores.archivo} />

                <ul className="space-y-1.5 text-xs text-slate-500">
                    {[
                        'Se aplican las mismas reglas que al registrar un contacto: frecuencia dentro de una banda y reporte según el modo.',
                        'Los contactos que ya están en tu libro se omiten como duplicados.',
                        'Las horas se conservan en UTC, tal como vienen en el archivo.',
                    ].map((regla) => (
                        <li key={regla} className="flex gap-2">
                            <span className="punto mt-1.5 bg-slate-500" />
                            {regla}
                        </li>
                    ))}
                </ul>

                <BotonPrimario disabled={enviando}>{enviando ? 'Leyendo el archivo…' : 'Ver vista previa'}</BotonPrimario>
            </form>
        </Panel>
    );
}

function Resumen({ resultado }: { resultado: ResultadoImportacion }) {
    return (
        <Panel ceja="Resumen de la importación">
            <div className="grid grid-cols-3 gap-4 text-center">
                {(
                    [
                        ['Importados', resultado.importados, 'bg-emerald-400'],
                        ['Omitidos (duplicados)', resultado.omitidos, 'bg-amber-400'],
                        ['Con errores', resultado.fallidos, 'bg-rose-400'],
                    ] as const
                ).map(([etiqueta, valor, punto]) => (
                    <div key={etiqueta}>
                        <p className="font-display text-3xl font-semibold tabular-nums text-white">{valor}</p>
                        <p className="mt-1 inline-flex items-center gap-2 text-xs text-slate-400">
                            <span className={`punto ${punto}`} />
                            {etiqueta}
                        </p>
                    </div>
                ))}
            </div>
            {resultado.errores.length > 0 && (
                <div className="mt-6">
                    <p className="mb-3 text-sm font-medium text-slate-300">Registros que no se importaron</p>
                    <ul className="space-y-2 text-sm">
                        {resultado.errores.map((fila) => (
                            <li key={fila.numero} className="rounded-xl bg-rose-500/[0.06] px-4 py-2.5 ring-1 ring-rose-400/15">
                                <span className="font-mono text-slate-400">#{fila.numero}</span> <span className="font-mono font-semibold text-white">{fila.indicativo || '—'}</span>{' '}
                                <span className="text-rose-300">· {fila.errores.join(' ')}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
            <div className="mt-6 text-right">
                <Link to="/contactos" className="text-sm font-medium text-acento-300 hover:text-acento-200">
                    Ver mi libro &rsaquo;
                </Link>
            </div>
        </Panel>
    );
}

function VistaPrevia({
    datos,
    procesando,
    error,
    onConfirmar,
    onCancelar,
}: {
    datos: VistaPreviaImportacion;
    procesando: boolean;
    error: string | null;
    onConfirmar: () => void;
    onCancelar: () => void;
}) {
    return (
        <>
            <header className="mx-auto max-w-5xl px-4 pt-10 sm:px-6 lg:px-8">
                <button type="button" onClick={onCancelar} className="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">
                    &lsaquo; Elegir otro archivo
                </button>
                <h1 className="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">Vista previa</h1>
                <p className="mt-1 font-mono text-sm text-slate-400">
                    {datos.nombre_archivo} · {datos.total_filas} {datos.total_filas === 1 ? 'registro' : 'registros'}
                </p>
            </header>

            <div className="mx-auto max-w-5xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
                {error && <Aviso tipo="error">{error}</Aviso>}

                <div className="grid grid-cols-3 gap-4">
                    {(
                        [
                            ['Se importarán', datos.nuevos, 'bg-emerald-400 shadow-[0_0_8px_rgb(52_211_153)]'],
                            ['Duplicados', datos.duplicados, 'bg-amber-400'],
                            ['Con errores', datos.errores, 'bg-rose-400'],
                        ] as const
                    ).map(([etiqueta, valor, punto]) => (
                        <div key={etiqueta} className="vidrio p-5">
                            <p className="inline-flex items-center gap-2 text-xs text-slate-400">
                                <span className={`punto h-2 w-2 ${punto}`} />
                                {etiqueta}
                            </p>
                            <p className="mt-2 font-display text-3xl font-semibold tabular-nums text-white">{valor}</p>
                        </div>
                    ))}
                </div>

                <section className="vidrio overflow-hidden !p-0">
                    <div className="px-6 pb-4 pt-6">
                        <Ceja>Registros del archivo</Ceja>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead>
                                <tr className="border-y border-white/5 text-left text-[0.75rem] font-medium uppercase tracking-[0.18em] text-slate-500">
                                    <th scope="col" className="py-3 pl-6 pr-3 font-medium">#</th>
                                    <th scope="col" className="px-3 py-3 font-medium">Estado</th>
                                    <th scope="col" className="px-3 py-3 font-medium">Estación</th>
                                    <th scope="col" className="px-3 py-3 font-medium">Fecha · UTC</th>
                                    <th scope="col" className="px-3 py-3 font-medium">Banda</th>
                                    <th scope="col" className="py-3 pl-3 pr-6 font-medium">Modo / detalle</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-white/5">
                                {datos.filas.map((fila) => (
                                    <FilaVistaPrevia key={fila.numero} fila={fila} />
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {datos.total_filas > datos.filas.length && (
                        <p className="border-t border-white/5 px-6 py-3 text-xs text-slate-500">
                            Se muestran los primeros {datos.filas.length} de {datos.total_filas} registros. Al importar se procesan todos.
                        </p>
                    )}
                </section>

                <div className="flex flex-wrap items-center justify-end gap-3">
                    <button type="button" onClick={onCancelar} disabled={procesando} className="rounded-full px-5 py-2.5 text-sm font-medium text-slate-400 transition hover:text-white">
                        Cancelar
                    </button>
                    <BotonPrimario type="button" className="!px-7 !py-3" disabled={datos.nuevos === 0 || procesando} onClick={onConfirmar}>
                        {procesando ? 'Importando…' : datos.nuevos === 0 ? 'No hay contactos nuevos' : `Importar ${datos.nuevos} ${datos.nuevos === 1 ? 'contacto' : 'contactos'}`}
                    </BotonPrimario>
                </div>
            </div>
        </>
    );
}

const ESTADOS_FILA = {
    nuevo: ['text-emerald-300', 'bg-emerald-400', 'Nuevo'],
    duplicado: ['text-amber-300', 'bg-amber-400', 'Duplicado'],
    error: ['text-rose-300', 'bg-rose-400', 'Error'],
} as const;

function FilaVistaPrevia({ fila }: { fila: FilaImportacion }) {
    const [texto, punto, etiqueta] = ESTADOS_FILA[fila.estado];

    return (
        <tr className={fila.estado === 'error' ? 'bg-rose-500/[0.04]' : ''}>
            <td className="py-3 pl-6 pr-3 font-mono text-slate-500">{fila.numero}</td>
            <td className="whitespace-nowrap px-3 py-3">
                <span className={`inline-flex items-center gap-2 text-xs font-medium ${texto}`}>
                    <span className={`punto h-2 w-2 ${punto}`} />
                    {etiqueta}
                </span>
            </td>
            <td className="whitespace-nowrap px-3 py-3 font-mono font-semibold text-white">{fila.indicativo || '—'}</td>
            <td className="whitespace-nowrap px-3 py-3 font-mono text-slate-400">{fila.fecha ?? '—'}</td>
            <td className="whitespace-nowrap px-3 py-3">{fila.banda ? <ChipBanda banda={fila.banda} /> : <span className="text-slate-600">—</span>}</td>
            <td className="py-3 pl-3 pr-6">{fila.errores.length ? <span className="text-rose-300">{fila.errores.join(' ')}</span> : <span className="text-slate-300">{fila.modo}</span>}</td>
        </tr>
    );
}
