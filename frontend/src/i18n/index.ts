import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import en from '@/i18n/en.json';
import pt from '@/i18n/pt.json';

/**
 * Traducciones de la SPA. La clave de cada frase es el propio texto en español: así el código se lee
 * igual que antes y, si a un idioma le falta una frase, se muestra en español en vez de romperse.
 * Los archivos en.json y pt.json los revisa `npm run i18n:revisar`.
 */
export const IDIOMAS = [
    { codigo: 'es', nombre: 'Español', corto: 'ES' },
    { codigo: 'en', nombre: 'English', corto: 'EN' },
    { codigo: 'pt', nombre: 'Português', corto: 'PT' },
] as const;

export type Idioma = (typeof IDIOMAS)[number]['codigo'];

const CLAVE = 'idioma';

function idiomaInicial(): Idioma {
    let guardado: string | null = null;
    try {
        guardado = localStorage.getItem(CLAVE);
    } catch {
        // Sin almacenamiento: se usa el del navegador.
    }

    const candidato = (guardado ?? navigator.language ?? 'es').slice(0, 2);

    return IDIOMAS.some((idioma) => idioma.codigo === candidato) ? (candidato as Idioma) : 'es';
}

void i18n.use(initReactI18next).init({
    lng: idiomaInicial(),
    fallbackLng: 'es',
    resources: { en: { translation: en }, pt: { translation: pt } },
    // Las claves son frases: no se separan por «.» ni «:».
    keySeparator: false,
    nsSeparator: false,
    returnEmptyString: false,
    interpolation: { escapeValue: false },
    showSupportNotice: false,
});

document.documentElement.lang = i18n.language;

export function cambiarIdioma(idioma: Idioma): void {
    void i18n.changeLanguage(idioma);
    document.documentElement.lang = idioma;
    try {
        localStorage.setItem(CLAVE, idioma);
    } catch {
        // Sin almacenamiento: el idioma solo dura esta visita.
    }
}

/** Idioma para formatear fechas y números (toLocaleString). */
export const configuracionRegional = (): string => ({ es: 'es-EC', en: 'en-US', pt: 'pt-BR' })[i18n.language as Idioma] ?? 'es-EC';

/**
 * Marca un texto fijo (fuera de un componente) para traducirlo al mostrarlo con t(). No hace nada en
 * tiempo de ejecución: sirve para que `npm run i18n:revisar` lo encuentre.
 */
export const tx = <T extends string>(texto: T): T => texto;

export default i18n;
