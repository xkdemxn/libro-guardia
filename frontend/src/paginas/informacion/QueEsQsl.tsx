import { Lista, PaginaInformativa, Seccion } from '@/paginas/informacion/PaginaInformativa';

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

            <Seccion numero={2} titulo="El QSO">
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

            <Seccion numero={3} titulo="La QSL: confirmar el contacto">
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
