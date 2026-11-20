import type { ReactNode } from 'react';
import { Link } from 'react-router';
import { useTranslation } from 'react-i18next';
import type { Idioma } from '@/i18n';
import { Lista, PaginaInformativa, Seccion } from '@/paginas/informacion/PaginaInformativa';

interface Contenido {
    ceja: string;
    titulo: string;
    introduccion: string;
    secciones: [string, ReactNode][];
}

const EnlacePrivacidad = ({ children }: { children: ReactNode }) => (
    <Link to="/perfil#privacidad" className="text-acento-300 hover:text-acento-200">
        {children}
    </Link>
);

/**
 * El texto completo en cada idioma. Describe solo lo que la app hace de verdad con los datos;
 * si eso cambia, hay que actualizar los tres.
 */
const CONTENIDO: Record<Idioma, Contenido> = {
    es: {
        ceja: 'Tus datos',
        titulo: 'Política de privacidad',
        introduccion: 'Qué datos guarda Libro de Guardia, para qué los usa y qué puedes hacer con ellos. Última actualización: noviembre de 2026.',
        secciones: [
            ['Qué datos guardamos', <Lista elementos={[
                <><strong>Cuenta</strong>: indicativo, nombres, apellidos, correo y contraseña (se guarda de forma irreversible: nadie puede leerla, ni siquiera nosotros).</>,
                <><strong>Perfil</strong>, si los completas: país, provincia, ciudad, grid locator, equipo, antena, biografía, foto y portada.</>,
                <><strong>Tu libro de guardia</strong>: los contactos que registras o importas y el estado de sus QSL.</>,
                <><strong>Seguridad</strong>: si activas la verificación en dos pasos, el secreto de la app autenticadora y tus códigos de recuperación, cifrados.</>,
            ]} />],
            ['Para qué los usamos', <p>Solo para que el servicio funcione: llevar tu libro, calcular estadísticas y distancias, confirmar contactos con otras estaciones y enviarte los correos que pidas (verificación, recuperar contraseña, códigos de acceso o tarjetas QSL). No vendemos tus datos ni los usamos para publicidad.</p>],
            ['Qué ven los demás', <Lista elementos={[
                <>Tu <strong>indicativo y tu país</strong> siempre son públicos: así te encuentran otros operadores.</>,
                <>Nombre, ciudad, equipo, biografía y foto se muestran <strong>solo si tú lo permites</strong> en <EnlacePrivacidad>Mi perfil → Privacidad</EnlacePrivacidad>.</>,
                <>Tu <strong>correo y tu libro nunca</strong> se muestran a otros usuarios.</>,
                <>Quien recibe una de tus tarjetas puede consultar <strong>ese único contacto</strong> en «Consulta una QSL», con el número de QSO impreso en la tarjeta y su propio indicativo.</>,
                <>Si otra estación registra un contacto contigo, puede ver si lo confirmaste o lo rechazaste, pero no el resto de tu libro.</>,
            ]} />],
            ['Servicios de terceros', <Lista elementos={[
                <><strong>Brevo</strong> envía los correos: recibe tu dirección y el contenido de cada mensaje.</>,
                <>El <strong>mapa</strong> de estadísticas carga sus imágenes desde los servidores de Esri, y las <strong>tipografías</strong> desde Bunny Fonts; tu navegador se conecta a ellos al abrir esas páginas.</>,
                <>Las fotos del equipo en «Sobre nosotros» se cargan desde <strong>GitHub</strong>.</>,
            ]} />],
            ['Cookies', <p>Usamos solo las cookies necesarias para mantener tu sesión iniciada y proteger los formularios. El modo claro u oscuro y el idioma se recuerdan en tu propio navegador. No usamos cookies de análisis ni de publicidad.</p>],
            ['Tus derechos', <>
                <p>De acuerdo con la Ley Orgánica de Protección de Datos Personales de Ecuador, puedes:</p>
                <Lista elementos={[
                    <><strong>Acceder y corregir</strong> tus datos en cualquier momento desde Mi perfil.</>,
                    <><strong>Llevártelos</strong>: exporta tu libro completo en ADIF o CSV.</>,
                    <><strong>Eliminarlos</strong>: al borrar tu cuenta se eliminan tus datos, fotos y todos tus contactos.</>,
                    <><strong>Decidir qué es público</strong> desde las opciones de privacidad.</>,
                ]} />
            </>],
        ],
    },
    en: {
        ceja: 'Your data',
        titulo: 'Privacy policy',
        introduccion: 'What data Libro de Guardia keeps, what it uses it for, and what you can do with it. Last updated: November 2026.',
        secciones: [
            ['What data we keep', <Lista elementos={[
                <><strong>Account</strong>: callsign, first and last name, email and password (stored irreversibly: no one can read it, not even us).</>,
                <><strong>Profile</strong>, if you fill it in: country, province, city, grid locator, radio, antenna, bio, photo and cover.</>,
                <><strong>Your logbook</strong>: the contacts you log or import and their QSL status.</>,
                <><strong>Security</strong>: if you turn on two-step verification, the authenticator secret and your recovery codes, encrypted.</>,
            ]} />],
            ['What we use it for', <p>Only to make the service work: keep your log, calculate statistics and distances, confirm contacts with other stations, and send you the emails you ask for (verification, password recovery, sign-in codes or QSL cards). We don’t sell your data or use it for advertising.</p>],
            ['What others see', <Lista elementos={[
                <>Your <strong>callsign and country</strong> are always public: that’s how other operators find you.</>,
                <>Name, city, radio, bio and photo are shown <strong>only if you allow it</strong> in <EnlacePrivacidad>My profile → Privacy</EnlacePrivacidad>.</>,
                <>Your <strong>email and your log are never</strong> shown to other users.</>,
                <>Someone who receives one of your cards can look up <strong>that single contact</strong> in “Check a QSL”, using the QSO number printed on the card and their own callsign.</>,
                <>If another station logs a contact with you, they can see whether you confirmed or declined it, but not the rest of your log.</>,
            ]} />],
            ['Third-party services', <Lista elementos={[
                <><strong>Brevo</strong> sends the emails: it receives your address and the content of each message.</>,
                <>The statistics <strong>map</strong> loads its images from Esri’s servers, and the <strong>fonts</strong> come from Bunny Fonts; your browser connects to them when you open those pages.</>,
                <>The team photos in “About us” are loaded from <strong>GitHub</strong>.</>,
            ]} />],
            ['Cookies', <p>We only use the cookies needed to keep you signed in and protect forms. Light or dark mode and your language are remembered in your own browser. We don’t use analytics or advertising cookies.</p>],
            ['Your rights', <>
                <p>Under Ecuador’s Personal Data Protection Law, you can:</p>
                <Lista elementos={[
                    <><strong>Access and correct</strong> your data at any time from My profile.</>,
                    <><strong>Take it with you</strong>: export your whole log as ADIF or CSV.</>,
                    <><strong>Delete it</strong>: deleting your account removes your data, photos and all your contacts.</>,
                    <><strong>Decide what’s public</strong> in the privacy settings.</>,
                ]} />
            </>],
        ],
    },
    pt: {
        ceja: 'Seus dados',
        titulo: 'Política de privacidade',
        introduccion: 'Quais dados o Libro de Guardia guarda, para que os usa e o que você pode fazer com eles. Última atualização: novembro de 2026.',
        secciones: [
            ['Que dados guardamos', <Lista elementos={[
                <><strong>Conta</strong>: indicativo, nome, sobrenome, e-mail e senha (guardada de forma irreversível: ninguém pode lê-la, nem mesmo nós).</>,
                <><strong>Perfil</strong>, se você preencher: país, província, cidade, grid locator, rádio, antena, biografia, foto e capa.</>,
                <><strong>Seu log</strong>: os contatos que você registra ou importa e o status dos QSL.</>,
                <><strong>Segurança</strong>: se você ativar a verificação em duas etapas, o segredo do app autenticador e seus códigos de recuperação, criptografados.</>,
            ]} />],
            ['Para que os usamos', <p>Só para o serviço funcionar: manter seu log, calcular estatísticas e distâncias, confirmar contatos com outras estações e enviar os e-mails que você pedir (verificação, recuperação de senha, códigos de acesso ou cartões QSL). Não vendemos seus dados nem os usamos para publicidade.</p>],
            ['O que os outros veem', <Lista elementos={[
                <>Seu <strong>indicativo e seu país</strong> são sempre públicos: é assim que outros operadores encontram você.</>,
                <>Nome, cidade, rádio, biografia e foto aparecem <strong>só se você permitir</strong> em <EnlacePrivacidad>Meu perfil → Privacidade</EnlacePrivacidad>.</>,
                <>Seu <strong>e-mail e seu log nunca</strong> aparecem para outros usuários.</>,
                <>Quem recebe um dos seus cartões pode consultar <strong>esse único contato</strong> em “Consulte um QSL”, com o número do QSO impresso no cartão e o próprio indicativo.</>,
                <>Se outra estação registrar um contato com você, ela pode ver se você o confirmou ou recusou, mas não o resto do seu log.</>,
            ]} />],
            ['Serviços de terceiros', <Lista elementos={[
                <>O <strong>Brevo</strong> envia os e-mails: recebe seu endereço e o conteúdo de cada mensagem.</>,
                <>O <strong>mapa</strong> das estatísticas carrega suas imagens dos servidores da Esri, e as <strong>fontes</strong> vêm do Bunny Fonts; seu navegador se conecta a eles ao abrir essas páginas.</>,
                <>As fotos da equipe em “Sobre nós” são carregadas do <strong>GitHub</strong>.</>,
            ]} />],
            ['Cookies', <p>Usamos apenas os cookies necessários para manter você conectado e proteger os formulários. O modo claro ou escuro e o idioma ficam guardados no seu próprio navegador. Não usamos cookies de análise nem de publicidade.</p>],
            ['Seus direitos', <>
                <p>De acordo com a Lei Orgânica de Proteção de Dados Pessoais do Equador, você pode:</p>
                <Lista elementos={[
                    <><strong>Acessar e corrigir</strong> seus dados a qualquer momento em Meu perfil.</>,
                    <><strong>Levá-los com você</strong>: exporte seu log completo em ADIF ou CSV.</>,
                    <><strong>Excluí-los</strong>: ao excluir sua conta, seus dados, fotos e todos os seus contatos são apagados.</>,
                    <><strong>Decidir o que é público</strong> nas opções de privacidade.</>,
                ]} />
            </>],
        ],
    },
};

export function Privacidad() {
    const { i18n } = useTranslation();
    const c = CONTENIDO[i18n.language as Idioma] ?? CONTENIDO.es;

    return (
        <PaginaInformativa ceja={c.ceja} titulo={c.titulo} introduccion={c.introduccion}>
            {c.secciones.map(([titulo, contenido], indice) => (
                <Seccion key={titulo} numero={indice + 1} titulo={titulo}>
                    {contenido}
                </Seccion>
            ))}
        </PaginaInformativa>
    );
}
