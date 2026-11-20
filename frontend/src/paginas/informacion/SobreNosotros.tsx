import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { Emblema } from '@/componentes/Emblema';
import type { Idioma } from '@/i18n';
import { Lista, PaginaInformativa, Seccion } from '@/paginas/informacion/PaginaInformativa';

/** Integrantes del proyecto (usuarios de GitHub). */
const EQUIPO = ['xkdemxn', 'juanpcoba-wq', 'emilyvelasco-art', 'Dorian1741', 'sergio3200x'];

interface Contenido {
    titulo: string;
    introduccion: string;
    queEs: [string, ReactNode];
    porQue: [string, ReactNode, ReactNode[]];
    equipo: [string, string];
    proyecto: [string, ReactNode, string];
}

/** El texto completo en cada idioma, para que cada versión se lea natural. */
const CONTENIDO: Record<Idioma, Contenido> = {
    es: {
        titulo: 'Sobre nosotros',
        introduccion: 'Un libro de guardia digital para radioaficionados, pensado para la comunidad DX de Ecuador.',
        queEs: ['Qué es', <><strong>Libro de Guardia</strong> reemplaza el cuaderno donde los radioaficionados anotan sus contactos. Registra cada QSO en hora UTC, calcula solo la banda y el país, y ayuda a confirmar los contactos con otras estaciones.</>],
        porQue: [
            'Por qué lo hicimos',
            'Muchos operadores todavía llevan su log en papel o en hojas de cálculo, donde es fácil equivocarse con la hora UTC, repetir contactos o perder la cuenta de qué QSL llegaron. Quisimos una herramienta en español, sencilla y que funcione desde el celular.',
            [
                'Registro rápido con ficha del indicativo, aviso de contactos repetidos y validación del reporte según el modo.',
                'Confirmación automática entre usuarios del sistema y tarjetas QSL digitales.',
                'Importación y exportación en ADIF, el estándar de la radioafición, para no quedar atado a ningún programa.',
                'Estadísticas, mapa de contactos y avance hacia diplomas como el DXCC y el WAC.',
            ],
        ],
        equipo: ['El equipo', 'Ver en GitHub ›'],
        proyecto: [
            'El proyecto',
            'Libro de Guardia es un proyecto de la Universidad Estatal de Bolívar, desarrollado por estudiantes de la carrera de Software. Partió de historias de usuario y diagramas del sistema, y se amplió con innovaciones propias como la confirmación automática y el mapa de contactos.',
            'Es software en desarrollo: si encuentras un error o tienes una idea, cuéntanos.',
        ],
    },
    en: {
        titulo: 'About us',
        introduccion: 'A digital logbook for amateur radio operators, built for Ecuador’s DX community.',
        queEs: ['What it is', <><strong>Libro de Guardia</strong> replaces the notebook where hams write down their contacts. It logs every QSO in UTC, works out the band and country on its own, and helps confirm contacts with other stations.</>],
        porQue: [
            'Why we built it',
            'Many operators still keep their log on paper or in spreadsheets, where it’s easy to get the UTC time wrong, log a contact twice, or lose track of which QSLs arrived. We wanted a simple tool, in Spanish, that works from a phone.',
            [
                'Fast logging with callsign lookup, duplicate warnings and report checks by mode.',
                'Automatic confirmation between users and digital QSL cards.',
                'ADIF import and export, the amateur radio standard, so you’re never tied to one program.',
                'Statistics, a contact map and progress toward awards like DXCC and WAC.',
            ],
        ],
        equipo: ['The team', 'View on GitHub ›'],
        proyecto: [
            'The project',
            'Libro de Guardia is a project of the Universidad Estatal de Bolívar (Ecuador), built by students of the Software program. It started from user stories and system diagrams, and grew with our own additions like automatic confirmation and the contact map.',
            'It’s software in progress: if you find a bug or have an idea, let us know.',
        ],
    },
    pt: {
        titulo: 'Sobre nós',
        introduccion: 'Um log digital para radioamadores, pensado para a comunidade DX do Equador.',
        queEs: ['O que é', <>O <strong>Libro de Guardia</strong> substitui o caderno em que os radioamadores anotam seus contatos. Registra cada QSO em hora UTC, calcula sozinho a banda e o país, e ajuda a confirmar os contatos com outras estações.</>],
        porQue: [
            'Por que o fizemos',
            'Muitos operadores ainda mantêm o log em papel ou em planilhas, onde é fácil errar a hora UTC, repetir contatos ou perder a conta de quais QSL chegaram. Queríamos uma ferramenta simples, em espanhol, que funcione no celular.',
            [
                'Registro rápido com ficha do indicativo, aviso de contatos repetidos e validação do relatório conforme o modo.',
                'Confirmação automática entre usuários e cartões QSL digitais.',
                'Importação e exportação em ADIF, o padrão do radioamadorismo, para não ficar preso a nenhum programa.',
                'Estatísticas, mapa de contatos e progresso rumo a diplomas como o DXCC e o WAC.',
            ],
        ],
        equipo: ['A equipe', 'Ver no GitHub ›'],
        proyecto: [
            'O projeto',
            'O Libro de Guardia é um projeto da Universidad Estatal de Bolívar (Equador), desenvolvido por estudantes do curso de Software. Partiu de histórias de usuário e diagramas do sistema e cresceu com inovações próprias, como a confirmação automática e o mapa de contatos.',
            'É um software em desenvolvimento: se encontrar um erro ou tiver uma ideia, conte para nós.',
        ],
    },
};

export function SobreNosotros() {
    const { t, i18n } = useTranslation();
    const c = CONTENIDO[i18n.language as Idioma] ?? CONTENIDO.es;

    return (
        <PaginaInformativa ceja="Libro de Guardia" titulo={c.titulo} introduccion={c.introduccion}>
            <Seccion numero={1} titulo={c.queEs[0]}>
                <div className="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <Emblema className="h-24 w-24" alt={t('Emblema de Radioaficionados DX Ecuador')} />
                    <p>{c.queEs[1]}</p>
                </div>
            </Seccion>

            <Seccion numero={2} titulo={c.porQue[0]}>
                <p>{c.porQue[1]}</p>
                <Lista elementos={c.porQue[2]} />
            </Seccion>

            <Seccion numero={3} titulo={c.equipo[0]}>
                <ul className="flex flex-wrap justify-center gap-3">
                    {EQUIPO.map((usuario) => (
                        <li key={usuario}>
                            <a
                                href={`https://github.com/${usuario}`}
                                target="_blank"
                                rel="noreferrer"
                                title={c.equipo[1]}
                                className="flex aspect-square w-[8.5rem] flex-col items-center justify-center gap-1.5 rounded-2xl bg-white/[0.03] p-2 text-center ring-1 ring-white/10 transition hover:bg-white/[0.07] hover:ring-acento-400/30"
                            >
                                <img
                                    src={`https://github.com/${usuario}.png?size=200`}
                                    alt=""
                                    loading="lazy"
                                    width={96}
                                    height={96}
                                    className="h-24 w-24 rounded-full bg-noche-800 object-cover ring-2 ring-white/10"
                                />
                                <span className="w-full truncate font-mono text-[0.86rem] font-semibold text-slate-100">@{usuario}</span>
                            </a>
                        </li>
                    ))}
                </ul>
            </Seccion>

            <Seccion numero={4} titulo={c.proyecto[0]}>
                <p>{c.proyecto[1]}</p>
                <p className="text-sm text-slate-400">{c.proyecto[2]}</p>
            </Seccion>
        </PaginaInformativa>
    );
}
