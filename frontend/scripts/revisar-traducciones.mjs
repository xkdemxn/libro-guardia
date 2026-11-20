/**
 * Revisa que cada frase marcada con t('…') o tx('…') en src/ tenga traducción en src/i18n/en.json y pt.json.
 *
 *   npm run i18n:revisar             → lista lo que falta y termina con error si falta algo
 *   npm run i18n:revisar -- --claves → imprime todas las frases encontradas (JSON)
 */
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';

const raiz = new URL('../src/', import.meta.url).pathname;
const archivos = (carpeta) =>
    readdirSync(carpeta).flatMap((nombre) => {
        const ruta = join(carpeta, nombre);
        return statSync(ruta).isDirectory() ? archivos(ruta) : /\.(tsx?|jsx?)$/.test(nombre) ? [ruta] : [];
    });

const claves = new Set();
const patron = /\b(?:t|tx|i18n\.t)\(\s*'((?:[^'\\]|\\.)*)'|i18nKey="([^"]*)"/g;

for (const archivo of archivos(raiz)) {
    if (archivo.includes('/i18n/')) continue;
    for (const coincidencia of readFileSync(archivo, 'utf8').matchAll(patron)) {
        const clave = coincidencia[1] ?? coincidencia[2];
        claves.add(clave.replace(/\\n/g, '\n').replace(/\\'/g, "'").replace(/\\\\/g, '\\'));
    }
}

if (process.argv.includes('--claves')) {
    console.log(JSON.stringify([...claves].sort(), null, 2));
    process.exit(0);
}

let faltan = 0;
for (const idioma of ['en', 'pt']) {
    const traducciones = JSON.parse(readFileSync(join(raiz, 'i18n', `${idioma}.json`), 'utf8'));
    const sinTraducir = [...claves].filter((clave) => !(clave in traducciones));
    const sobran = Object.keys(traducciones).filter((clave) => !claves.has(clave));
    faltan += sinTraducir.length;
    console.log(`${idioma}: ${claves.size - sinTraducir.length}/${claves.size} traducidas${sobran.length ? `, ${sobran.length} sin uso` : ''}`);
    sinTraducir.forEach((clave) => console.log(`  falta: ${JSON.stringify(clave)}`));
}

process.exit(faltan ? 1 : 0);
