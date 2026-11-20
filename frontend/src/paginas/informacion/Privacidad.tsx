import { Link } from 'react-router';
import { Lista, PaginaInformativa, Seccion } from '@/paginas/informacion/PaginaInformativa';

export function Privacidad() {
    return (
        <PaginaInformativa
            ceja="Tus datos"
            titulo="Política de privacidad"
            introduccion="Qué datos guarda Libro de Guardia, para qué los usa y qué puedes hacer con ellos. Última actualización: noviembre de 2026."
        >
            <Seccion numero={1} titulo="Qué datos guardamos">
                <Lista
                    elementos={[
                        <><strong>Cuenta</strong>: indicativo, nombres, apellidos, correo y contraseña (se guarda de forma irreversible: nadie puede leerla, ni siquiera nosotros).</>,
                        <><strong>Perfil</strong>, si los completas: país, provincia, ciudad, grid locator, equipo, antena, biografía, foto y portada.</>,
                        <><strong>Tu libro de guardia</strong>: los contactos que registras o importas y el estado de sus QSL.</>,
                        <><strong>Seguridad</strong>: si activas la verificación en dos pasos, el secreto de la app autenticadora y tus códigos de recuperación, cifrados.</>,
                    ]}
                />
            </Seccion>

            <Seccion numero={2} titulo="Para qué los usamos">
                <p>Solo para que el servicio funcione: llevar tu libro, calcular estadísticas y distancias, confirmar contactos con otras estaciones y enviarte los correos que pidas (verificación, recuperar contraseña, códigos de acceso o tarjetas QSL). No vendemos tus datos ni los usamos para publicidad.</p>
            </Seccion>

            <Seccion numero={3} titulo="Qué ven los demás">
                <Lista
                    elementos={[
                        <>Tu <strong>indicativo y tu país</strong> siempre son públicos: así te encuentran otros operadores.</>,
                        <>
                            Nombre, ciudad, equipo, biografía y foto se muestran <strong>solo si tú lo permites</strong> en{' '}
                            <Link to="/perfil#privacidad" className="text-acento-300 hover:text-acento-200">
                                Mi perfil → Privacidad
                            </Link>
                            .
                        </>,
                        <>Tu <strong>correo y tu libro nunca</strong> se muestran a otros usuarios.</>,
                        <>
                            Quien recibe una de tus tarjetas puede consultar <strong>ese único contacto</strong> en «Consulta una QSL», con el número de QSO impreso en la tarjeta y su
                            propio indicativo.
                        </>,
                        <>Si otra estación registra un contacto contigo, puede ver si lo confirmaste o lo rechazaste, pero no el resto de tu libro.</>,
                    ]}
                />
            </Seccion>

            <Seccion numero={4} titulo="Servicios de terceros">
                <Lista
                    elementos={[
                        <><strong>Brevo</strong> envía los correos: recibe tu dirección y el contenido de cada mensaje.</>,
                        <>El <strong>mapa</strong> de estadísticas carga sus imágenes desde los servidores de Esri, y las <strong>tipografías</strong> desde Bunny Fonts; tu navegador se conecta a ellos al abrir esas páginas.</>,
                    ]}
                />
            </Seccion>

            <Seccion numero={5} titulo="Cookies">
                <p>
                    Usamos solo las cookies necesarias para mantener tu sesión iniciada y proteger los formularios. El modo claro u oscuro se recuerda en tu propio navegador. No usamos cookies de
                    análisis ni de publicidad.
                </p>
            </Seccion>

            <Seccion numero={6} titulo="Tus derechos">
                <p>De acuerdo con la Ley Orgánica de Protección de Datos Personales de Ecuador, puedes:</p>
                <Lista
                    elementos={[
                        <><strong>Acceder y corregir</strong> tus datos en cualquier momento desde Mi perfil.</>,
                        <><strong>Llevártelos</strong>: exporta tu libro completo en ADIF o CSV.</>,
                        <><strong>Eliminarlos</strong>: al borrar tu cuenta se eliminan tus datos, fotos y todos tus contactos.</>,
                        <><strong>Decidir qué es público</strong> desde las opciones de privacidad.</>,
                    ]}
                />
            </Seccion>
        </PaginaInformativa>
    );
}
