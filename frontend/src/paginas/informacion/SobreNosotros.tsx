import { Emblema } from '@/componentes/Emblema';
import { Lista, PaginaInformativa, Seccion } from '@/paginas/informacion/PaginaInformativa';

/** Integrantes del proyecto (usuarios de GitHub). */
const EQUIPO = ['xkdemxn', 'juanpcoba-wq', 'emilyvelasco-art', 'Dorian1741'];

export function SobreNosotros() {
    return (
        <PaginaInformativa
            ceja="Libro de Guardia"
            titulo="Sobre nosotros"
            introduccion="Un libro de guardia digital para radioaficionados, pensado para la comunidad DX de Ecuador."
        >
            <Seccion numero={1} titulo="Qué es">
                <div className="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <Emblema className="h-24 w-24" alt="Emblema de Radioaficionados DX Ecuador" />
                    <p>
                        <strong>Libro de Guardia</strong> reemplaza el cuaderno donde los radioaficionados anotan sus contactos. Registra cada QSO en hora UTC, calcula solo la banda y
                        el país, y ayuda a confirmar los contactos con otras estaciones.
                    </p>
                </div>
            </Seccion>

            <Seccion numero={2} titulo="Por qué lo hicimos">
                <p>
                    Muchos operadores todavía llevan su log en papel o en hojas de cálculo, donde es fácil equivocarse con la hora UTC, repetir contactos o perder la cuenta de qué QSL
                    llegaron. Quisimos una herramienta en español, sencilla y que funcione desde el celular.
                </p>
                <Lista
                    elementos={[
                        <>Registro rápido con ficha del indicativo, aviso de contactos repetidos y validación del reporte según el modo.</>,
                        <>Confirmación automática entre usuarios del sistema y tarjetas QSL digitales.</>,
                        <>Importación y exportación en ADIF, el estándar de la radioafición, para no quedar atado a ningún programa.</>,
                        <>Estadísticas, mapa de contactos y avance hacia diplomas como el DXCC y el WAC.</>,
                    ]}
                />
            </Seccion>

            <Seccion numero={3} titulo="El equipo">
                <ul className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {EQUIPO.map((usuario) => (
                        <li key={usuario}>
                            <a
                                href={`https://github.com/${usuario}`}
                                target="_blank"
                                rel="noreferrer"
                                className="flex flex-col items-center gap-3 rounded-2xl bg-white/[0.03] p-4 text-center ring-1 ring-white/10 transition hover:bg-white/[0.07] hover:ring-acento-400/30"
                            >
                                <img
                                    src={`https://github.com/${usuario}.png?size=160`}
                                    alt=""
                                    loading="lazy"
                                    width={80}
                                    height={80}
                                    className="h-20 w-20 rounded-full bg-noche-800 object-cover ring-2 ring-white/10"
                                />
                                <span className="break-all font-mono text-sm font-semibold text-slate-100">@{usuario}</span>
                                <span className="text-xs text-slate-500">Ver en GitHub ›</span>
                            </a>
                        </li>
                    ))}
                </ul>
            </Seccion>

            <Seccion numero={4} titulo="El proyecto">
                <p>
                    Libro de Guardia nació como proyecto académico de la Universidad Estatal de BOlivar Relizada por estudiantes de la carrera de Ingenieria de software, a partir de historias de usuario y diagramas del sistema, y se amplió con innovaciones propias
                    como la confirmación automática y el mapa de contactos.
                </p>
                <p className="text-sm text-slate-400">Es software en desarrollo: si encuentras un error o tienes una idea, cuéntanos.</p>
            </Seccion>
        </PaginaInformativa>
    );
}
