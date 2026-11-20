import { useEffect, useMemo, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { Link, useNavigate } from 'react-router';
import { useQueryClient } from '@tanstack/react-query';
import { Bandera } from '@/componentes/Bandera';
import { Cargando } from '@/componentes/Cargando';
import { Aviso, BotonPrimario, Campo, ErrorCampo, Etiqueta } from '@/componentes/Formulario';
import { Panel } from '@/componentes/Panel';
import { RelojUtc } from '@/componentes/RelojUtc';
import { useCatalogos } from '@/hooks/useCatalogos';
import { api, erroresDeValidacion, mensajeDeError, type ErroresValidacion } from '@/lib/api';
import type { BandaCatalogo, ContactoDetalle, Ficha, ModoCatalogo } from '@/lib/tipos';
import { fechaUtc, horaUtc } from '@/lib/utc';
import { useTranslation } from 'react-i18next';
import { tx } from '@/i18n';

export interface ValoresContacto {
    estacion_llamada: string;
    fecha: string;
    hora_inicio: string;
    hora_fin: string;
    frecuencia_mhz: string;
    modo_id: string;
    reporte_enviado: string;
    reporte_recibido: string;
    potencia_w: string;
    grid_locator: string;
    nombre_operador: string;
    equipo: string;
    activacion: string;
    observaciones: string;
}

type Lado = 'enviado' | 'recibido';
type Rango = 'HF' | 'VHF' | 'UHF';

const RANGOS: [Rango, string][] = [
    ['HF', tx('HF · onda corta')],
    ['VHF', 'VHF'],
    ['UHF', 'UHF'],
];

/** Valores iniciales: un contacto existente o, si es nuevo, la fecha y hora UTC de este momento. */
export function valoresIniciales(contacto?: ContactoDetalle): ValoresContacto {
    const ahora = new Date();

    return {
        estacion_llamada: contacto?.estacion_llamada ?? '',
        fecha: contacto?.fecha ?? fechaUtc(ahora),
        hora_inicio: contacto?.hora_inicio ?? horaUtc(ahora),
        hora_fin: contacto?.hora_fin ?? '',
        frecuencia_mhz: contacto ? contacto.frecuencia_mhz.toFixed(3) : '',
        modo_id: contacto ? String(contacto.modo.id) : '',
        reporte_enviado: contacto?.reporte_enviado ?? '',
        reporte_recibido: contacto?.reporte_recibido ?? '',
        potencia_w: contacto?.potencia_w != null ? String(contacto.potencia_w) : '',
        grid_locator: contacto?.grid_locator ?? '',
        nombre_operador: contacto?.nombre_operador ?? '',
        equipo: contacto?.equipo ?? '',
        activacion: contacto?.activacion ?? '',
        observaciones: contacto?.observaciones ?? '',
    };
}

interface Props {
    inicial: ValoresContacto;
    fechaMinima?: string;
    /** PUT del contacto que se edita; sin él se crea uno nuevo. */
    idContacto?: number;
    textoBoton: string;
}

/**
 * Formulario de contacto compartido por crear y editar (US-03, US-04, US-05, US-07).
 *
 * - Banda: se elige por rango (HF/VHF/UHF) y la frecuencia con un deslizador dentro de la banda;
 *   o al revés, escribiendo la frecuencia la banda se marca sola.
 * - Modo: botones. SSB es un solo modo con dos variantes dentro del mismo botón: USB y LSB.
 * - Reportes: botón rápido 59 (voz) o 599 (CW); «Otro» permite armarlo con R, S y T.
 */
export function FormularioContacto({ inicial, fechaMinima, idContacto, textoBoton }: Props) {
    const catalogos = useCatalogos();

    if (!catalogos.data) {
        return <Cargando />;
    }

    return (
        <Formulario
            inicial={inicial}
            fechaMinima={fechaMinima ?? catalogos.data.hoy_utc}
            hoy={catalogos.data.hoy_utc}
            idContacto={idContacto}
            textoBoton={textoBoton}
            bandas={catalogos.data.bandas}
            modos={catalogos.data.modos}
        />
    );
}

function Formulario({
    inicial,
    fechaMinima,
    hoy,
    idContacto,
    textoBoton,
    bandas,
    modos,
}: Props & { fechaMinima: string; hoy: string; bandas: BandaCatalogo[]; modos: ModoCatalogo[] }) {
    const { t } = useTranslation();
    const navegar = useNavigate();
    const cliente = useQueryClient();
    const [valores, setValores] = useState(inicial);
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [errorGeneral, setErrorGeneral] = useState<string | null>(null);
    const [confirmarDuplicado, setConfirmarDuplicado] = useState(false);
    const [enviando, setEnviando] = useState(false);

    const cambiar = <K extends keyof ValoresContacto>(campo: K, valor: ValoresContacto[K]) => setValores((actuales) => ({ ...actuales, [campo]: valor }));

    /* ---------- Banda y frecuencia ---------- */

    const mhz = useMemo(() => {
        const valor = parseFloat(valores.frecuencia_mhz.replace(',', '.'));

        return Number.isNaN(valor) ? null : valor;
    }, [valores.frecuencia_mhz]);
    const bandaActual = mhz === null ? null : (bandas.find((banda) => mhz >= banda.min && mhz <= banda.max) ?? null);
    const rangoDeBanda = (banda: BandaCatalogo): Rango => (banda.rango === 'MF' ? 'HF' : banda.rango);
    const [rangoVisible, setRangoVisible] = useState<Rango>(() => (bandaActual ? rangoDeBanda(bandaActual) : 'HF'));

    useEffect(() => {
        if (bandaActual) {
            setRangoVisible(rangoDeBanda(bandaActual));
        }
    }, [bandaActual?.nombre]);

    const bandasDelRango = bandas.filter((banda) => rangoDeBanda(banda) === rangoVisible);
    const pasoDeslizador = (banda: BandaCatalogo) => (banda.max - banda.min > 20 ? 0.025 : 0.001);

    /* ---------- Modo ---------- */

    const modoActual = modos.find((modo) => String(modo.id) === valores.modo_id) ?? null;
    const esSsb = ['USB', 'LSB'].includes(modoActual?.nombre ?? '');
    const tipo = modoActual?.tipo ?? '';
    const reporteRapido = tipo === 'RS' ? '59' : tipo === 'RST' ? '599' : '';
    const modoPorNombre = (nombre: string) => modos.find((modo) => modo.nombre === nombre);
    // Botones de modo: SSB agrupa USB y LSB (sus dos bandas laterales).
    const botonesModo = ['SSB', ...modos.map((modo) => modo.nombre).filter((nombre) => !['USB', 'LSB'].includes(nombre))];
    // Bajo 10 MHz (160, 80 y 40 m) la convención es LSB: se sugiere, pero no se cambia solo.
    const sugiereLsb = modoActual?.nombre === 'USB' && mhz !== null && mhz < 10;

    /* ---------- Reportes ---------- */

    const [manual, setManual] = useState<Record<Lado, boolean>>(() => ({
        enviado: Boolean(inicial.reporte_enviado) && !['59', '599'].includes(inicial.reporte_enviado),
        recibido: Boolean(inicial.reporte_recibido) && !['59', '599'].includes(inicial.reporte_recibido),
    }));

    function elegirModo(nombre: string) {
        const nuevo = nombre === 'SSB' ? (esSsb ? modoActual : modoPorNombre('USB')) : modoPorNombre(nombre);
        if (!nuevo) {
            return;
        }

        const rapido = nuevo.tipo === 'RS' ? '59' : nuevo.tipo === 'RST' ? '599' : '';
        setValores((actuales) => ({
            ...actuales,
            modo_id: String(nuevo.id),
            // Si cambia el tipo de reporte, los reportes vuelven al valor rápido del nuevo modo.
            ...(nuevo.tipo !== tipo ? { reporte_enviado: rapido, reporte_recibido: rapido } : {}),
        }));
        if (nuevo.tipo !== tipo) {
            setManual({ enviado: false, recibido: false });
        }
    }

    function ponerAhora(cual: 'inicio' | 'fin') {
        const ahora = new Date();
        setValores((actuales) => ({
            ...actuales,
            fecha: cual === 'inicio' || !actuales.fecha ? fechaUtc(ahora) : actuales.fecha,
            [cual === 'inicio' ? 'hora_inicio' : 'hora_fin']: horaUtc(ahora),
        }));
    }

    async function guardar(evento: FormEvent) {
        evento.preventDefault();
        setEnviando(true);
        setErrores({});
        setErrorGeneral(null);

        const datos = { ...valores, confirmar_duplicado: confirmarDuplicado };

        try {
            const respuesta = idContacto
                ? await api.put<{ data: ContactoDetalle; mensaje: string }>(`/contactos/${idContacto}`, datos)
                : await api.post<{ data: ContactoDetalle; mensaje: string }>('/contactos', datos);

            void cliente.invalidateQueries({ queryKey: ['contactos'] });
            void cliente.invalidateQueries({ queryKey: ['estadisticas'] });
            cliente.setQueryData(['contacto', String(respuesta.data.data.id)], respuesta.data.data);
            navegar('/contactos', { state: { estado: respuesta.data.mensaje } });
        } catch (error) {
            const validacion = erroresDeValidacion(error);
            setErrores(validacion);
            if (Object.keys(validacion).length === 0) {
                setErrorGeneral(mensajeDeError(error));
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        } finally {
            setEnviando(false);
        }
    }

    const opcionales = ['nombre_operador', 'equipo', 'grid_locator', 'activacion', 'observaciones'] as const;

    return (
        <form onSubmit={guardar} noValidate>
            <div className="space-y-6">
                {errorGeneral && <Aviso tipo="error">{errorGeneral}</Aviso>}

                {errores.duplicado && (
                    <div role="alert" className="vidrio !rounded-2xl border-amber-400/25 !bg-amber-400/[0.07] p-5">
                        <div className="flex gap-3">
                            <span className="punto mt-1.5 h-2 w-2 bg-amber-400 shadow-[0_0_10px_rgb(251_191_36)]" />
                            <div>
                                <p className="font-semibold text-amber-200">{t('Posible contacto repetido')}</p>
                                <p className="mt-1 text-sm text-amber-100/80">{errores.duplicado[0]}</p>
                                <label className="mt-4 flex items-center gap-2.5 text-sm text-amber-100">
                                    <input
                                        type="checkbox"
                                        checked={confirmarDuplicado}
                                        onChange={(evento) => setConfirmarDuplicado(evento.target.checked)}
                                        className="h-4 w-4 rounded border-amber-300/40 bg-white/5 text-amber-500 focus:ring-amber-400/30 focus:ring-offset-0"
                                    />
                                    {t('Sí, no es un duplicado: guardarlo de todos modos')}
                                </label>
                            </div>
                        </div>
                    </div>
                )}

                <Panel ceja={t('01 · Estación y horario')}>
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-6">
                        <div className="flex flex-col gap-5 sm:col-span-6 md:flex-row md:items-end">
                            <div className="min-w-0 flex-1">
                                <label htmlFor="estacion_llamada" className="mb-1.5 block text-base font-semibold text-slate-200">
                                    {t('Indicativo')}
                                </label>
                                <input
                                    id="estacion_llamada"
                                    className="campo !py-3 font-mono !text-2xl font-semibold uppercase tracking-wider"
                                    required
                                    autoFocus={!idContacto}
                                    maxLength={20}
                                    placeholder="LU4BB"
                                    autoComplete="off"
                                    aria-describedby="ficha-indicativo"
                                    value={valores.estacion_llamada}
                                    onChange={(evento) => cambiar('estacion_llamada', evento.target.value)}
                                />
                                <ErrorCampo mensajes={errores.estacion_llamada} />
                            </div>
                            <div className="flex shrink-0 items-end">
                                <RelojUtc grande className="w-full justify-center !py-3 md:w-auto" />
                            </div>
                        </div>

                        <FichaIndicativo indicativo={valores.estacion_llamada} />

                        <div className="sm:col-span-6 lg:col-span-2">
                            <Campo id="fecha" etiqueta={t('Fecha (UTC)')} type="date" required min={fechaMinima} max={hoy} value={valores.fecha} onChange={(e) => cambiar('fecha', e.target.value)} errores={errores.fecha} />
                        </div>
                        <div className="sm:col-span-3 lg:col-span-2">
                            <Etiqueta htmlFor="hora_inicio">{t('Hora de inicio (UTC)')}</Etiqueta>
                            <div className="flex gap-2">
                                <input id="hora_inicio" type="time" required className="campo" value={valores.hora_inicio} onChange={(e) => cambiar('hora_inicio', e.target.value)} />
                                <BotonAhora onClick={() => ponerAhora('inicio')} />
                            </div>
                            <ErrorCampo mensajes={errores.hora_inicio} />
                        </div>
                        <div className="sm:col-span-3 lg:col-span-2">
                            <Etiqueta htmlFor="hora_fin">
                                Hora de fin (UTC) <span className="font-normal text-slate-500">{t('· opcional')}</span>
                            </Etiqueta>
                            <div className="flex gap-2">
                                <input id="hora_fin" type="time" className="campo" value={valores.hora_fin} onChange={(e) => cambiar('hora_fin', e.target.value)} />
                                <BotonAhora onClick={() => ponerAhora('fin')} />
                            </div>
                            <ErrorCampo mensajes={errores.hora_fin} />
                        </div>
                    </div>
                </Panel>

                <Panel ceja={t('02 · Banda y frecuencia')}>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-1 rounded-full bg-black/25 p-1 ring-1 ring-white/5" role="tablist" aria-label={t('Rango de frecuencias')}>
                            {RANGOS.map(([rango, etiqueta]) => (
                                <button
                                    key={rango}
                                    type="button"
                                    role="tab"
                                    aria-selected={rangoVisible === rango}
                                    onClick={() => setRangoVisible(rango)}
                                    className={`rounded-full px-4 py-1.5 text-sm transition ${rangoVisible === rango ? 'bg-white/10 font-semibold text-white ring-1 ring-white/10' : 'text-slate-400 hover:text-white'}`}
                                >
                                    {t(etiqueta)}
                                </button>
                            ))}
                        </div>
                        <p className="text-xs text-slate-500">HF: 1.8–30 MHz · VHF: 30–300 MHz · UHF: 300–3000 MHz</p>
                    </div>

                    <div className="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-5" role="radiogroup" aria-label={t('Banda')}>
                        {bandasDelRango.map((opcion) => {
                            const elegida = bandaActual?.nombre === opcion.nombre;

                            return (
                                <button
                                    key={opcion.id}
                                    type="button"
                                    role="radio"
                                    aria-checked={elegida}
                                    onClick={() => !elegida && cambiar('frecuencia_mhz', opcion.min.toFixed(3))}
                                    className={`rounded-2xl border px-3 py-2.5 text-left transition ${
                                        elegida ? 'border-acento-400/60 bg-acento-400/10 shadow-[0_0_0_3px_rgb(82_173_255/0.12)]' : 'border-white/10 bg-white/[0.02] hover:bg-white/[0.05]'
                                    }`}
                                >
                                    <span className="block font-mono text-base font-semibold text-white">{opcion.nombre}</span>
                                    <span className="mt-0.5 block font-mono text-[0.8125rem] text-slate-400">
                                        {opcion.min.toFixed(3)}–{opcion.max.toFixed(3)}
                                    </span>
                                </button>
                            );
                        })}
                    </div>

                    <div className="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-6">
                        <div className="sm:col-span-2">
                            <Etiqueta htmlFor="frecuencia_mhz">{t('Frecuencia (MHz)')}</Etiqueta>
                            <input
                                id="frecuencia_mhz"
                                inputMode="decimal"
                                required
                                placeholder="14.225"
                                className="campo font-mono tabular-nums !text-acento-200"
                                value={valores.frecuencia_mhz}
                                onChange={(e) => cambiar('frecuencia_mhz', e.target.value)}
                            />
                            <p className="mt-1.5 flex h-5 items-center gap-2 text-xs" aria-live="polite">
                                {mhz === null ? (
                                    <span className="text-slate-500">{t('Elige una banda o escribe la frecuencia.')}</span>
                                ) : bandaActual ? (
                                    <span className="inline-flex items-center gap-2 text-slate-400">
                                        Banda <span className="chip border-sky-400/25 bg-sky-400/10 font-mono text-sky-300">{bandaActual.nombre}</span>
                                        {bandaActual.rango}
                                    </span>
                                ) : (
                                    <span className="inline-flex items-center gap-2 text-rose-400">
                                        <span className="punto bg-rose-400" />
                                        {t('Fuera de las bandas de radioaficionados')}
                                    </span>
                                )}
                            </p>
                            <ErrorCampo mensajes={errores.frecuencia_mhz} />
                        </div>

                        <div className="sm:col-span-4">
                            {bandaActual && mhz !== null && (
                                <div>
                                    <div className="mb-1.5 flex items-baseline justify-between text-[0.8125rem]">
                                        <label htmlFor="deslizador-frecuencia" className="font-medium text-slate-400">
                                            Sintonía dentro de <span className="font-mono">{bandaActual.nombre}</span>
                                        </label>
                                        <span className="font-mono text-acento-300">{mhz.toFixed(3)} MHz</span>
                                    </div>
                                    <input
                                        id="deslizador-frecuencia"
                                        type="range"
                                        className="w-full accent-[#2a8fff]"
                                        min={bandaActual.min}
                                        max={bandaActual.max}
                                        step={pasoDeslizador(bandaActual)}
                                        value={mhz}
                                        onChange={(e) => cambiar('frecuencia_mhz', Number(e.target.value).toFixed(3))}
                                    />
                                    <div className="mt-1 flex justify-between font-mono text-[0.75rem] text-slate-500">
                                        <span>{bandaActual.min.toFixed(3)}</span>
                                        <span>{bandaActual.max.toFixed(3)}</span>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </Panel>

                <Panel ceja={t('03 · Modo y señal')}>
                    <Etiqueta htmlFor="modo">{t('Modo')}</Etiqueta>
                    <div id="modo" className="flex flex-wrap gap-2" role="radiogroup" aria-label={t('Modo')}>
                        {botonesModo.map((nombre) =>
                            nombre === 'SSB' ? (
                                <div
                                    key="SSB"
                                    className={`flex items-center rounded-full border transition ${esSsb ? 'border-acento-400/60 bg-acento-400/15' : 'border-white/10 bg-white/[0.02] hover:bg-white/[0.06]'}`}
                                >
                                    <button
                                        type="button"
                                        role="radio"
                                        aria-checked={esSsb}
                                        onClick={() => elegirModo('SSB')}
                                        className={`rounded-full px-4 py-2 font-mono text-sm font-semibold transition ${esSsb ? 'text-white' : 'text-slate-300'}`}
                                    >
                                        SSB
                                    </button>
                                    {esSsb && (
                                        <div className="flex items-center gap-0.5 pe-1" role="radiogroup" aria-label={t('Banda lateral de SSB')}>
                                            {(
                                                [
                                                    ['USB', t('Banda lateral superior')],
                                                    ['LSB', t('Banda lateral inferior')],
                                                ] as const
                                            ).map(([lateral, descripcion]) => (
                                                <button
                                                    key={lateral}
                                                    type="button"
                                                    role="radio"
                                                    aria-checked={modoActual?.nombre === lateral}
                                                    title={descripcion}
                                                    aria-label={`${lateral}: ${descripcion.toLowerCase()}`}
                                                    onClick={() => elegirModo(lateral)}
                                                    className={`rounded-full px-3 py-1 font-mono text-xs font-semibold transition ${
                                                        modoActual?.nombre === lateral ? 'bg-gradient-to-b from-marca-400 to-marca-600 text-blanco shadow-boton' : 'text-slate-400 hover:text-white'
                                                    }`}
                                                >
                                                    {lateral}
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <button
                                    key={nombre}
                                    type="button"
                                    role="radio"
                                    aria-checked={modoActual?.nombre === nombre}
                                    onClick={() => elegirModo(nombre)}
                                    className={`rounded-full border px-4 py-2 font-mono text-sm font-semibold transition ${
                                        modoActual?.nombre === nombre ? 'border-acento-400/60 bg-acento-400/15 text-white' : 'border-white/10 bg-white/[0.02] text-slate-300 hover:bg-white/[0.06]'
                                    }`}
                                >
                                    {nombre}
                                </button>
                            ),
                        )}
                    </div>
                    <ErrorCampo mensajes={errores.modo_id} />

                    {esSsb && (
                        <p className="mt-2.5 text-xs text-slate-500">
                            {modoActual?.nombre === 'LSB' ? t('LSB: banda lateral inferior.') : t('USB: banda lateral superior (la más usada; queda por defecto si no eliges otra).')}{' '}
                            {sugiereLsb && (
                                <span className="text-amber-300/90">
                                    {t('En 160, 80 y 40 m lo habitual es LSB.')}{' '}
                                    <button type="button" className="font-medium text-acento-300 hover:text-acento-200" onClick={() => elegirModo('LSB')}>
                                        {t('Usar LSB')}
                                    </button>
                                </span>
                            )}
                        </p>
                    )}

                    {tipo && (
                        <div className="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            {(
                                [
                                    ['enviado', t('Reporte enviado')],
                                    ['recibido', t('Reporte recibido')],
                                ] as const
                            ).map(([lado, etiqueta]) => (
                                <Reporte
                                    key={lado}
                                    etiqueta={etiqueta}
                                    tipo={tipo}
                                    rapido={reporteRapido}
                                    valor={valores[`reporte_${lado}`]}
                                    manual={manual[lado]}
                                    onManual={(activo) => setManual((actual) => ({ ...actual, [lado]: activo }))}
                                    onCambiar={(valor) => cambiar(`reporte_${lado}`, valor)}
                                    errores={errores[`reporte_${lado}`]}
                                />
                            ))}
                        </div>
                    )}

                    <div className="mt-6 sm:w-1/3">
                        <Campo id="potencia_w" etiqueta={t('Potencia (W)')} type="number" step="0.1" min="0" placeholder="100" value={valores.potencia_w} onChange={(e) => cambiar('potencia_w', e.target.value)} errores={errores.potencia_w} />
                    </div>
                </Panel>

                <details className="vidrio group p-5 sm:p-6" open={opcionales.some((campo) => errores[campo] || inicial[campo]) || undefined}>
                    <summary className="flex cursor-pointer list-none items-center gap-4 [&::-webkit-details-marker]:hidden">
                        <span className="h-px flex-1 bg-gradient-to-r from-transparent to-white/15" />
                        <span className="text-[0.75rem] font-medium uppercase tracking-[0.28em] text-slate-400">{t('04 · Datos opcionales')}</span>
                        <svg className="h-4 w-4 text-slate-500 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fillRule="evenodd" clipRule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" />
                        </svg>
                        <span className="h-px flex-1 bg-gradient-to-l from-transparent to-white/15" />
                    </summary>
                    <div className="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <Campo id="nombre_operador" etiqueta={t('Nombre del operador')} maxLength={100} value={valores.nombre_operador} onChange={(e) => cambiar('nombre_operador', e.target.value)} errores={errores.nombre_operador} />
                        <Campo id="equipo" etiqueta={t('Equipo de la otra estación')} maxLength={255} value={valores.equipo} onChange={(e) => cambiar('equipo', e.target.value)} errores={errores.equipo} />
                        <Campo id="grid_locator" etiqueta={t('Grid locator')} className="font-mono uppercase" maxLength={6} placeholder="FI09" value={valores.grid_locator} onChange={(e) => cambiar('grid_locator', e.target.value)} errores={errores.grid_locator} />
                        <Campo id="activacion" etiqueta={t('Activación (POTA / SOTA)')} className="font-mono uppercase" maxLength={30} placeholder="HC-0001" value={valores.activacion} onChange={(e) => cambiar('activacion', e.target.value)} errores={errores.activacion} />
                        <div className="sm:col-span-2">
                            <Etiqueta htmlFor="observaciones">{t('Observaciones')}</Etiqueta>
                            <textarea id="observaciones" rows={3} maxLength={2000} className="campo" value={valores.observaciones} onChange={(e) => cambiar('observaciones', e.target.value)} />
                            <ErrorCampo mensajes={errores.observaciones} />
                        </div>
                    </div>
                </details>
            </div>

            <div className="mt-8 flex items-center justify-end gap-3">
                <Link to={idContacto ? `/contactos/${idContacto}` : '/contactos'} className="rounded-full px-5 py-2.5 text-sm font-medium text-slate-400 transition hover:text-white">
                    {t('Cancelar')}
                </Link>
                <BotonPrimario className="!px-7 !py-3" disabled={enviando}>
                    {enviando ? t('Guardando…') : textoBoton}
                </BotonPrimario>
            </div>
        </form>
    );
}

function BotonAhora({ onClick }: { onClick: () => void }) {
    const { t } = useTranslation();
    return (
        <button
            type="button"
            onClick={onClick}
            className="shrink-0 rounded-xl border border-acento-400/30 bg-acento-400/10 px-3.5 text-sm font-semibold text-acento-300 transition hover:bg-acento-400/20 active:scale-95"
        >
            {t('Ahora')}
        </button>
    );
}

const LEGIBILIDAD: [number, string][] = [
    [5, tx('perfecta')],
    [4, tx('casi sin dificultad')],
    [3, tx('con dificultad')],
    [2, tx('apenas')],
    [1, tx('ilegible')],
];
const NUEVE_A_UNO = [9, 8, 7, 6, 5, 4, 3, 2, 1];

/** Un reporte de señal: 59/599 de un clic, «Otro» para armarlo con R, S y T, o dB en modos digitales. */
function Reporte({
    etiqueta,
    tipo,
    rapido,
    valor,
    manual,
    onManual,
    onCambiar,
    errores,
}: {
    etiqueta: string;
    tipo: string;
    rapido: string;
    valor: string;
    manual: boolean;
    onManual: (activo: boolean) => void;
    onCambiar: (valor: string) => void;
    errores?: string[];
}) {
    const { t } = useTranslation();
    const valido = /^[1-5][1-9][1-9]?$/.test(valor);
    const r = valido ? Number(valor[0]) : 5;
    const s = valido ? Number(valor[1]) : 9;
    const tono = valido && valor[2] ? Number(valor[2]) : 9;
    const componer = (nuevo: { r?: number; s?: number; t?: number }) => {
        const partes = { r, s, t: tono, ...nuevo };
        onCambiar(tipo === 'RST' ? `${partes.r}${partes.s}${partes.t}` : `${partes.r}${partes.s}`);
    };

    let contenido: ReactNode;

    if (tipo === 'dB') {
        contenido = (
            <div>
                <input inputMode="numeric" className="campo font-mono" maxLength={4} placeholder="-12" aria-label={`${etiqueta} en dB`} value={valor} onChange={(e) => onCambiar(e.target.value)} />
                <p className="mt-1.5 text-xs text-slate-500">En dB, de -50 a +50 (ej. -12).</p>
            </div>
        );
    } else {
        contenido = (
            <div>
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={() => {
                            onManual(false);
                            onCambiar(rapido);
                        }}
                        className={`flex-1 rounded-xl border px-4 py-2.5 font-mono text-lg font-semibold transition ${
                            !manual ? 'border-emerald-400/50 bg-emerald-400/10 text-emerald-200' : 'border-white/10 bg-white/[0.02] text-slate-400 hover:bg-white/[0.06]'
                        }`}
                    >
                        {rapido}
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            onManual(true);
                            componer({});
                        }}
                        className={`rounded-xl border px-4 py-2.5 text-sm font-semibold transition ${
                            manual ? 'border-amber-400/50 bg-amber-400/10 text-amber-200' : 'border-white/10 bg-white/[0.02] text-slate-300 hover:bg-white/[0.06]'
                        }`}
                    >
                        {t('Otro')}
                    </button>
                </div>
                {manual && (
                    <>
                        <div className={`mt-2 grid gap-2 ${tipo === 'RST' ? 'grid-cols-3' : 'grid-cols-2'}`}>
                            <label className="text-[0.75rem] text-slate-500">
                                {t('R · legibilidad')}
                                <select className="campo mt-1 !py-2 font-mono" value={r} onChange={(e) => componer({ r: Number(e.target.value) })}>
                                    {LEGIBILIDAD.map(([numero, texto]) => (
                                        <option key={numero} value={numero}>
                                            {numero} · {t(texto)}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label className="text-[0.75rem] text-slate-500">
                                {t('S · fuerza')}
                                <select className="campo mt-1 !py-2 font-mono" value={s} onChange={(e) => componer({ s: Number(e.target.value) })}>
                                    {NUEVE_A_UNO.map((numero) => (
                                        <option key={numero} value={numero}>
                                            {numero}
                                            {numero === 9 ? t(' · muy fuerte') : numero === 1 ? t(' · apenas perceptible') : ''}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            {tipo === 'RST' && (
                                <label className="text-[0.75rem] text-slate-500">
                                    {t('T · tono')}
                                    <select className="campo mt-1 !py-2 font-mono" value={tono} onChange={(e) => componer({ t: Number(e.target.value) })}>
                                        {NUEVE_A_UNO.map((numero) => (
                                            <option key={numero} value={numero}>
                                                {numero}
                                                {numero === 9 ? t(' · puro') : numero === 1 ? t(' · muy áspero') : ''}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                            )}
                        </div>
                        <p className="mt-1.5 text-xs text-slate-400">
                            Reporte: <span className="font-mono font-semibold text-slate-200">{valor}</span>
                        </p>
                    </>
                )}
            </div>
        );
    }

    return (
        <div>
            <p className="mb-1.5 block text-[0.8125rem] font-medium text-slate-400">{etiqueta}</p>
            {contenido}
            <ErrorCampo mensajes={errores} />
        </div>
    );
}

/** Ficha rápida que aparece al escribir un indicativo (innovación 3). */
function FichaIndicativo({ indicativo }: { indicativo: string }) {
    const { t } = useTranslation();
    const [ficha, setFicha] = useState<Extract<Ficha, { valido: true }> | null>(null);
    const ultimaBusqueda = useRef('');

    useEffect(() => {
        const limpio = indicativo.replace(/\s+/g, '').toUpperCase();
        ultimaBusqueda.current = limpio;

        if (limpio.length < 3) {
            setFicha(null);

            return;
        }

        const espera = setTimeout(async () => {
            try {
                const { data } = await api.get<Ficha>('/contactos/ficha', { params: { indicativo: limpio } });
                // Si el operador siguió escribiendo, se descarta la respuesta vieja.
                if (ultimaBusqueda.current === limpio) {
                    setFicha(data.valido ? data : null);
                }
            } catch {
                setFicha(null);
            }
        }, 350);

        return () => clearTimeout(espera);
    }, [indicativo]);

    return (
        <div id="ficha-indicativo" className="sm:col-span-6" aria-live="polite">
            {ficha && (
                <div className="flex flex-wrap items-center gap-x-5 gap-y-3 rounded-2xl bg-black/20 px-4 py-3 ring-1 ring-white/5">
                    {ficha.perfil?.foto_url && <img src={ficha.perfil.foto_url} alt="" className="h-10 w-10 rounded-xl object-cover ring-1 ring-white/10" />}
                    <div className="min-w-0">
                        <p className="text-sm text-white">
                            <span className="font-mono font-semibold">{ficha.indicativo}</span>
                            {ficha.perfil?.nombre && <span className="text-slate-300"> · {ficha.perfil.nombre}</span>}
                        </p>
                        <p className="mt-0.5 flex items-center gap-1.5 text-xs text-slate-400">
                            <Bandera codigo={ficha.codigo_bandera} pais={ficha.pais} className="h-3 w-4" />
                            <span>{[ficha.perfil?.ciudad, ficha.pais].filter(Boolean).join(', ') || t('País desconocido')}</span>
                            {ficha.zona && <span className="text-amber-300/90">· {ficha.zona}</span>}
                        </p>
                    </div>
                    <div className="text-xs">
                        {ficha.contactos_previos > 0 && ficha.ultimo ? (
                            <p className="inline-flex items-center gap-2 text-amber-200">
                                <span className="punto h-2 w-2 bg-amber-400" />
                                {ficha.contactos_previos === 1
                                    ? t('Ya hablaste 1 vez con esta estación · última {{hace}} en {{banda}} {{modo}}', { hace: ficha.ultimo.hace, banda: ficha.ultimo.banda, modo: ficha.ultimo.modo })
                                    : t('Ya hablaste {{n}} veces con esta estación · última {{hace}} en {{banda}} {{modo}}', { n: ficha.contactos_previos, hace: ficha.ultimo.hace, banda: ficha.ultimo.banda, modo: ficha.ultimo.modo })}
                            </p>
                        ) : (
                            <p className="inline-flex items-center gap-2 text-emerald-200">
                                <span className="punto h-2 w-2 bg-emerald-400" />
                                {t('Primer contacto con esta estación')}
                            </p>
                        )}
                    </div>
                    {ficha.registrado && (
                        <Link
                            to={`/indicativos/${ficha.indicativo}`}
                            target="_blank"
                            className="ms-auto inline-flex items-center gap-1.5 rounded-full bg-acento-400/10 px-3 py-1 text-xs font-medium text-acento-300 ring-1 ring-acento-400/25 hover:bg-acento-400/20"
                        >
                            {t('Usa Libro de Guardia · ver perfil')}
                        </Link>
                    )}
                </div>
            )}
        </div>
    );
}

/** Encabezado de las páginas de nuevo y editar contacto. */
export function EncabezadoContacto({ titulo, detalle, volverA }: { titulo: string; detalle: string; volverA: string }) {
    return (
        <header className="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
            <Link to={volverA} className="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">
                &lsaquo; Volver
            </Link>
            <h1 className="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">{titulo}</h1>
            <p className="mt-1 text-slate-400">{detalle}</p>
        </header>
    );
}
