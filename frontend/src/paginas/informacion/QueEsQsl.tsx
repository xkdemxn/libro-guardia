import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Bandera } from '@/componentes/Bandera';
import { Icono } from '@/componentes/Icono';
import { api } from '@/lib/api';
import { Lista, PaginaInformativa, Seccion } from '@/paginas/informacion/PaginaInformativa';

interface PaisConPrefijos {
    pais: string;
    codigo_bandera: string | null;
    continente: string | null;
    prefijos: string[];
}

export function QueEsQsl() {
    return (
        <PaginaInformativa
            ceja="Radioafición para empezar"
            titulo="¿Qué es una QSL?"
            introduccion="Una QSL es la confirmación de que dos estaciones de radio hablaron entre sí. Aquí te explicamos las palabras que verás en Libro de Guardia."
        >
            <Seccion numero={1} titulo="El indicativo">
                <p>
                    El <strong>indicativo</strong> es el «nombre» de una estación de radioaficionado. Lo asigna el ente regulador de cada país junto con la licencia; en Ecuador,
                    ARCOTEL. Es único: no hay dos estaciones con el mismo.
                </p>
                <p>Se lee en tres partes. Tomemos <span className="font-mono text-amber-200">HC5EV</span>:</p>
                <div className="grid gap-3 sm:grid-cols-3">
                    {(
                        [
                            ['HC', 'Prefijo', 'Dice el país. HC y HD son Ecuador; LU, Argentina; EA, España.'],
                            ['5', 'Número', 'En Ecuador indica la zona: 1 Pichincha y el norte, 2 Guayas… 5 Azuay, Cañar y Chimborazo.'],
                            ['EV', 'Sufijo', 'Las letras que distinguen a cada operador dentro de su zona.'],
                        ] as const
                    ).map(([parte, nombre, detalle]) => (
                        <div key={nombre} className="rounded-2xl bg-white/[0.03] p-4 ring-1 ring-white/10">
                            <p className="font-mono text-2xl font-semibold text-amber-200">{parte}</p>
                            <p className="mt-1 text-sm font-semibold text-white">{nombre}</p>
                            <p className="mt-1 text-sm text-slate-400">{detalle}</p>
                        </div>
                    ))}
                </div>
                <p className="text-sm text-slate-400">
                    Por eso, al escribir un indicativo, Libro de Guardia reconoce el país y la zona por su prefijo. Revisa que el formato sea válido, pero no consulta si el indicativo está
                    asignado de verdad: eso solo lo sabe el ente regulador.
                </p>
            </Seccion>

            <Seccion numero={2} titulo="Prefijos por país">
                <p>
                    Estos son los prefijos que reconoce Libro de Guardia. Si un indicativo empieza con varios prefijos posibles, gana el más largo: <span className="font-mono text-amber-200">HC8</span> es
                    Galápagos aunque <span className="font-mono text-amber-200">HC</span> sea Ecuador.
                </p>
                <ListaPrefijos />
            </Seccion>

            <Seccion numero={3} titulo="El QSO">
                <p>
                    Un <strong>QSO</strong> es un contacto: una conversación por radio entre dos estaciones. Cada operador lo anota en su <strong>libro de guardia</strong> (el log) con:
                </p>
                <Lista
                    elementos={[
                        <>La <strong>fecha y la hora en UTC</strong>, la hora universal, para que estaciones de distintos países anoten la misma hora.</>,
                        <>La <strong>frecuencia</strong> y la <strong>banda</strong> (20 m, 40 m…) en la que se habló.</>,
                        <>El <strong>modo</strong>: voz (SSB, FM), telegrafía (CW) o digital (FT8).</>,
                        <>
                            El <strong>reporte de señal</strong>: en voz es RS (59 = te escucho perfecto y fuerte), en telegrafía RST (599) y en digitales se mide en dB (por ejemplo, -12).
                        </>,
                    ]}
                />
            </Seccion>

            <Seccion numero={4} titulo="La QSL: confirmar el contacto">
                <p>
                    Que tú anotes un QSO no prueba que ocurrió: la otra estación tiene que <strong>confirmarlo</strong>. Esa confirmación es la QSL, y es la que cuenta para los diplomas
                    como el DXCC (contactar 100 países) o el WAC (los 6 continentes).
                </p>
                <Lista
                    elementos={[
                        <>
                            <strong>Tarjeta QSL</strong>: la forma clásica, una tarjeta postal con los datos del contacto. Libro de Guardia la diseña por ti y la puedes descargar o enviar por correo.
                        </>,
                        <>
                            <strong>eQSL y LoTW</strong>: servicios en línea donde las dos estaciones suben su log y se confirman los contactos que coinciden. Exporta tu libro en ADIF para subirlo.
                        </>,
                        <>
                            <strong>Confirmación automática</strong>: si la otra estación también usa Libro de Guardia y anotó el mismo contacto (misma banda, modo y una hora parecida), el
                            sistema lo confirma solo para los dos.
                        </>,
                    ]}
                />
                <p className="text-sm text-slate-400">
                    Libro de Guardia registra en cada contacto si la QSL se envió o se recibió por cada medio, con su fecha. Las confirmaciones de eQSL y LoTW se marcan a mano.
                </p>
            </Seccion>
        </PaginaInformativa>
    );
}

/** Prefijos de la base de datos, agrupados por continente y con buscador por país o prefijo. */
function ListaPrefijos() {
    const [busqueda, setBusqueda] = useState('');
    const consulta = useQuery({
        queryKey: ['prefijos'],
        queryFn: async () => (await api.get<{ data: PaisConPrefijos[] }>('/prefijos')).data.data,
        staleTime: Infinity,
    });

    const continentes = useMemo(() => {
        const texto = busqueda.trim().toLowerCase();
        const coincide = (pais: PaisConPrefijos) =>
            !texto || pais.pais.toLowerCase().includes(texto) || pais.prefijos.some((prefijo) => prefijo.toLowerCase().startsWith(texto) || texto.startsWith(prefijo.toLowerCase()));
        const grupos = new Map<string, PaisConPrefijos[]>();

        for (const pais of (consulta.data ?? []).filter(coincide)) {
            const continente = pais.continente ?? 'Otros';
            grupos.set(continente, [...(grupos.get(continente) ?? []), pais]);
        }

        return [...grupos.entries()];
    }, [consulta.data, busqueda]);

    return (
        <div>
            <label htmlFor="buscar-prefijo" className="sr-only">
                Buscar país o prefijo
            </label>
            <div className="relative">
                <Icono nombre="lupa" className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <input
                    id="buscar-prefijo"
                    type="search"
                    value={busqueda}
                    onChange={(evento) => setBusqueda(evento.target.value)}
                    placeholder="Busca un país o escribe un indicativo: Argentina, HC5EV…"
                    className="campo !pl-10"
                />
            </div>

            {consulta.isPending && <p className="mt-4 text-sm text-slate-500">Cargando prefijos…</p>}
            {consulta.data && continentes.length === 0 && <p className="mt-4 text-sm text-slate-500">Ningún país coincide con «{busqueda}».</p>}

            <div className="mt-5 max-h-[32rem] space-y-6 overflow-y-auto pe-1">
                {continentes.map(([continente, paises]) => (
                    <div key={continente}>
                        <p className="mb-2 text-[0.75rem] font-medium uppercase tracking-[0.2em] text-slate-500">{continente}</p>
                        <ul className="divide-y divide-white/5 rounded-2xl bg-white/[0.02] ring-1 ring-white/10">
                            {paises.map((pais) => (
                                <li key={pais.pais} className="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-start sm:gap-4">
                                    <span className="flex w-48 shrink-0 items-center gap-2 text-sm font-medium text-slate-100">
                                        <Bandera codigo={pais.codigo_bandera} pais={pais.pais} />
                                        {pais.pais}
                                    </span>
                                    <span className="flex flex-wrap gap-1.5">
                                        {pais.prefijos.map((prefijo) => (
                                            <span key={prefijo} className="rounded-md bg-amber-400/10 px-2 py-0.5 font-mono text-xs text-amber-200 ring-1 ring-amber-400/20">
                                                {prefijo}
                                            </span>
                                        ))}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </div>
        </div>
    );
}
