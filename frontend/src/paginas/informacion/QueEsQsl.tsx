import { useMemo, useState, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { useQuery } from '@tanstack/react-query';
import { Bandera } from '@/componentes/Bandera';
import { Icono } from '@/componentes/Icono';
import type { Idioma } from '@/i18n';
import { api } from '@/lib/api';
import { Lista, PaginaInformativa, Seccion } from '@/paginas/informacion/PaginaInformativa';

interface PaisConPrefijos {
    pais: string;
    codigo_bandera: string | null;
    continente: string | null;
    prefijos: string[];
}

interface Contenido {
    ceja: string;
    titulo: string;
    introduccion: string;
    secciones: [string, ReactNode][];
    partes: [string, string, string][];
}

const Amb = ({ children }: { children: ReactNode }) => <span className="font-mono text-amber-200">{children}</span>;

/**
 * El texto completo en cada idioma: es una página de lectura, así cada versión se lee natural
 * en lugar de traducirse frase por frase.
 */
const CONTENIDO: Record<Idioma, Contenido> = {
    es: {
        ceja: 'Radioafición para empezar',
        titulo: '¿Qué es una QSL?',
        introduccion: 'Una QSL es la confirmación de que dos estaciones de radio hablaron entre sí. Aquí te explicamos las palabras que verás en Libro de Guardia.',
        partes: [
            ['HC', 'Prefijo', 'Dice el país. HC y HD son Ecuador; LU, Argentina; EA, España.'],
            ['5', 'Número', 'En Ecuador indica la zona: 1 Pichincha y el norte, 2 Guayas… 5 Azuay, Cañar y Chimborazo.'],
            ['EV', 'Sufijo', 'Las letras que distinguen a cada operador dentro de su zona.'],
        ],
        secciones: [
            ['El indicativo', <>
                <p>El <strong>indicativo</strong> es el «nombre» de una estación de radioaficionado. Lo asigna el ente regulador de cada país junto con la licencia; en Ecuador, ARCOTEL. Es único: no hay dos estaciones con el mismo.</p>
                <p>Se lee en tres partes. Tomemos <Amb>HC5EV</Amb>:</p>
            </>],
            ['Nota indicativo', <>Por eso, al escribir un indicativo, Libro de Guardia reconoce el país y la zona por su prefijo. Revisa que el formato sea válido, pero no consulta si el indicativo está asignado de verdad: eso solo lo sabe el ente regulador.</>],
            ['Prefijos por país', <>Estos son los prefijos que reconoce Libro de Guardia. Si un indicativo empieza con varios prefijos posibles, gana el más largo: <Amb>HC8</Amb> es Galápagos aunque <Amb>HC</Amb> sea Ecuador.</>],
            ['El QSO', <>
                <p>Un <strong>QSO</strong> es un contacto: una conversación por radio entre dos estaciones. Cada operador lo anota en su <strong>libro de guardia</strong> (el log) con:</p>
                <Lista elementos={[
                    <>La <strong>fecha y la hora en UTC</strong>, la hora universal, para que estaciones de distintos países anoten la misma hora.</>,
                    <>La <strong>frecuencia</strong> y la <strong>banda</strong> (20 m, 40 m…) en la que se habló.</>,
                    <>El <strong>modo</strong>: voz (SSB, FM), telegrafía (CW) o digital (FT8).</>,
                    <>El <strong>reporte de señal</strong>: en voz es RS (59 = te escucho perfecto y fuerte), en telegrafía RST (599) y en digitales se mide en dB (por ejemplo, -12).</>,
                ]} />
            </>],
            ['La QSL: confirmar el contacto', <>
                <p>Que tú anotes un QSO no prueba que ocurrió: la otra estación tiene que <strong>confirmarlo</strong>. Esa confirmación es la QSL, y es la que cuenta para los diplomas como el DXCC (contactar 100 países) o el WAC (los 6 continentes).</p>
                <Lista elementos={[
                    <><strong>Tarjeta QSL</strong>: la forma clásica, una tarjeta postal con los datos del contacto. Libro de Guardia la diseña por ti y la puedes descargar o enviar por correo.</>,
                    <><strong>eQSL y LoTW</strong>: servicios en línea donde las dos estaciones suben su log y se confirman los contactos que coinciden. Exporta tu libro en ADIF para subirlo.</>,
                    <><strong>Confirmación automática</strong>: si la otra estación también usa Libro de Guardia y anotó el mismo contacto (misma banda, modo y una hora parecida), el sistema lo confirma solo para los dos.</>,
                ]} />
                <p className="text-sm text-slate-400">Libro de Guardia registra en cada contacto si la QSL se envió o se recibió por cada medio, con su fecha. Las confirmaciones de eQSL y LoTW se marcan a mano.</p>
            </>],
        ],
    },
    en: {
        ceja: 'Amateur radio basics',
        titulo: 'What is a QSL?',
        introduccion: 'A QSL is the confirmation that two radio stations talked to each other. Here we explain the words you’ll see in Libro de Guardia.',
        partes: [
            ['HC', 'Prefix', 'Tells the country. HC and HD are Ecuador; LU, Argentina; EA, Spain.'],
            ['5', 'Number', 'In Ecuador it’s the zone: 1 Pichincha and the north, 2 Guayas… 5 Azuay, Cañar and Chimborazo.'],
            ['EV', 'Suffix', 'The letters that tell each operator apart within their zone.'],
        ],
        secciones: [
            ['The callsign', <>
                <p>The <strong>callsign</strong> is the “name” of an amateur radio station. Each country’s regulator assigns it with the license; in Ecuador, ARCOTEL. It’s unique: no two stations share one.</p>
                <p>It reads in three parts. Take <Amb>HC5EV</Amb>:</p>
            </>],
            ['Nota indicativo', <>That’s why, when you type a callsign, Libro de Guardia recognizes the country and zone from its prefix. It checks that the format is valid, but it doesn’t look up whether the callsign is actually assigned: only the regulator knows that.</>],
            ['Prefixes by country', <>These are the prefixes Libro de Guardia recognizes. If a callsign starts with several possible prefixes, the longest wins: <Amb>HC8</Amb> is Galápagos even though <Amb>HC</Amb> is Ecuador.</>],
            ['The QSO', <>
                <p>A <strong>QSO</strong> is a contact: a radio conversation between two stations. Each operator writes it down in their <strong>logbook</strong> with:</p>
                <Lista elementos={[
                    <>The <strong>date and time in UTC</strong>, universal time, so stations in different countries log the same time.</>,
                    <>The <strong>frequency</strong> and <strong>band</strong> (20 m, 40 m…) where you talked.</>,
                    <>The <strong>mode</strong>: voice (SSB, FM), telegraphy (CW) or digital (FT8).</>,
                    <>The <strong>signal report</strong>: in voice it’s RS (59 = I hear you perfectly and strong), in telegraphy RST (599), and digital modes measure it in dB (for example, -12).</>,
                ]} />
            </>],
            ['The QSL: confirming the contact', <>
                <p>Logging a QSO doesn’t prove it happened: the other station has to <strong>confirm it</strong>. That confirmation is the QSL, and it’s what counts toward awards like DXCC (contact 100 countries) or WAC (all 6 continents).</p>
                <Lista elementos={[
                    <><strong>QSL card</strong>: the classic way, a postcard with the contact details. Libro de Guardia designs it for you, and you can download it or send it by email.</>,
                    <><strong>eQSL and LoTW</strong>: online services where both stations upload their logs and matching contacts get confirmed. Export your log as ADIF to upload it.</>,
                    <><strong>Automatic confirmation</strong>: if the other station also uses Libro de Guardia and logged the same contact (same band and mode, and a similar time), the system confirms it for both of you.</>,
                ]} />
                <p className="text-sm text-slate-400">Libro de Guardia records on each contact whether the QSL was sent or received by each method, with its date. eQSL and LoTW confirmations are marked by hand.</p>
            </>],
        ],
    },
    pt: {
        ceja: 'Radioamadorismo para começar',
        titulo: 'O que é um QSL?',
        introduccion: 'Um QSL é a confirmação de que duas estações de rádio conversaram entre si. Aqui explicamos as palavras que você verá no Libro de Guardia.',
        partes: [
            ['HC', 'Prefixo', 'Indica o país. HC e HD são Equador; LU, Argentina; EA, Espanha.'],
            ['5', 'Número', 'No Equador indica a zona: 1 Pichincha e o norte, 2 Guayas… 5 Azuay, Cañar e Chimborazo.'],
            ['EV', 'Sufixo', 'As letras que distinguem cada operador dentro da sua zona.'],
        ],
        secciones: [
            ['O indicativo', <>
                <p>O <strong>indicativo</strong> é o “nome” de uma estação de radioamador. É atribuído pelo órgão regulador de cada país junto com a licença; no Equador, a ARCOTEL. É único: não há duas estações com o mesmo.</p>
                <p>Ele se lê em três partes. Vejamos <Amb>HC5EV</Amb>:</p>
            </>],
            ['Nota indicativo', <>Por isso, ao digitar um indicativo, o Libro de Guardia reconhece o país e a zona pelo prefixo. Ele confere se o formato é válido, mas não consulta se o indicativo está de fato atribuído: só o órgão regulador sabe disso.</>],
            ['Prefixos por país', <>Estes são os prefixos que o Libro de Guardia reconhece. Se um indicativo começa com vários prefixos possíveis, vale o mais longo: <Amb>HC8</Amb> é Galápagos, embora <Amb>HC</Amb> seja Equador.</>],
            ['O QSO', <>
                <p>Um <strong>QSO</strong> é um contato: uma conversa por rádio entre duas estações. Cada operador o anota no seu <strong>log</strong> com:</p>
                <Lista elementos={[
                    <>A <strong>data e a hora em UTC</strong>, a hora universal, para que estações de países diferentes anotem a mesma hora.</>,
                    <>A <strong>frequência</strong> e a <strong>banda</strong> (20 m, 40 m…) em que se falou.</>,
                    <>O <strong>modo</strong>: voz (SSB, FM), telegrafia (CW) ou digital (FT8).</>,
                    <>O <strong>relatório de sinal</strong>: em voz é RS (59 = ouço você perfeito e forte), em telegrafia RST (599) e nos modos digitais é medido em dB (por exemplo, -12).</>,
                ]} />
            </>],
            ['O QSL: confirmar o contato', <>
                <p>Anotar um QSO não prova que ele aconteceu: a outra estação precisa <strong>confirmá-lo</strong>. Essa confirmação é o QSL, e é ela que conta para diplomas como o DXCC (contatar 100 países) ou o WAC (os 6 continentes).</p>
                <Lista elementos={[
                    <><strong>Cartão QSL</strong>: a forma clássica, um cartão-postal com os dados do contato. O Libro de Guardia o desenha para você, e você pode baixá-lo ou enviá-lo por e-mail.</>,
                    <><strong>eQSL e LoTW</strong>: serviços on-line em que as duas estações enviam seus logs e os contatos que coincidem são confirmados. Exporte seu log em ADIF para enviá-lo.</>,
                    <><strong>Confirmação automática</strong>: se a outra estação também usa o Libro de Guardia e registrou o mesmo contato (mesma banda e modo, e horário parecido), o sistema o confirma para os dois.</>,
                ]} />
                <p className="text-sm text-slate-400">O Libro de Guardia registra em cada contato se o QSL foi enviado ou recebido por cada meio, com a data. As confirmações de eQSL e LoTW são marcadas manualmente.</p>
            </>],
        ],
    },
};

export function QueEsQsl() {
    const { i18n } = useTranslation();
    const c = CONTENIDO[i18n.language as Idioma] ?? CONTENIDO.es;
    const [indicativo, nota, prefijos, qso, qsl] = c.secciones;

    return (
        <PaginaInformativa ceja={c.ceja} titulo={c.titulo} introduccion={c.introduccion}>
            <Seccion numero={1} titulo={indicativo[0]}>
                {indicativo[1]}
                <div className="grid gap-3 sm:grid-cols-3">
                    {c.partes.map(([parte, nombre, detalle]) => (
                        <div key={nombre} className="rounded-2xl bg-white/[0.03] p-4 ring-1 ring-white/10">
                            <p className="font-mono text-2xl font-semibold text-amber-200">{parte}</p>
                            <p className="mt-1 text-sm font-semibold text-white">{nombre}</p>
                            <p className="mt-1 text-sm text-slate-400">{detalle}</p>
                        </div>
                    ))}
                </div>
                <p className="text-sm text-slate-400">{nota[1]}</p>
            </Seccion>

            <Seccion numero={2} titulo={prefijos[0]}>
                <p>{prefijos[1]}</p>
                <ListaPrefijos />
            </Seccion>

            <Seccion numero={3} titulo={qso[0]}>
                {qso[1]}
            </Seccion>

            <Seccion numero={4} titulo={qsl[0]}>
                {qsl[1]}
            </Seccion>
        </PaginaInformativa>
    );
}

/** Prefijos de la base de datos, agrupados por continente y con buscador por país o prefijo. */
function ListaPrefijos() {
    const { t } = useTranslation();
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
            const continente = pais.continente ?? t('Otros');
            grupos.set(continente, [...(grupos.get(continente) ?? []), pais]);
        }

        return [...grupos.entries()];
    }, [consulta.data, busqueda]);

    return (
        <div>
            <label htmlFor="buscar-prefijo" className="sr-only">
                {t('Buscar país o prefijo')}
            </label>
            <div className="relative">
                <Icono nombre="lupa" className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <input
                    id="buscar-prefijo"
                    type="search"
                    value={busqueda}
                    onChange={(evento) => setBusqueda(evento.target.value)}
                    placeholder={t('Busca un país o escribe un indicativo: Argentina, HC5EV…')}
                    className="campo !pl-10"
                />
            </div>

            {consulta.isPending && <p className="mt-4 text-sm text-slate-500">{t('Cargando prefijos…')}</p>}
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
