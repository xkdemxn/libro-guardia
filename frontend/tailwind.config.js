import defaultTheme from 'tailwindcss/defaultTheme';
import tailwindColors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';
import plugin from 'tailwindcss/plugin';

/*
 * Copia de la configuración de Tailwind de la versión Blade (../tailwind.config.js) para la SPA.
 *
 * MODO CLARO Y MODO OSCURO
 *
 * Cada color se usa a través de una variable CSS (--c-slate-300, --c-white...).
 * En modo oscuro (por defecto) cada variable tiene su propio tono; en modo claro
 * (<html class="claro">) toma el tono "espejo" de su escala: slate-300 ↔ slate-700,
 * noche-950 ↔ noche-50, white ↔ tinta oscura. Así las vistas no necesitan clases
 * duplicadas: el texto claro sobre fondo oscuro pasa a ser texto oscuro sobre fondo claro.
 *
 * "marca" y "blanco" NO cambian con el tema: son para los botones y el logo,
 * que mantienen su azul y su texto blanco en ambos modos.
 */

const PASOS = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'];

const paletasPropias = {
    // Azul nocturno del fondo y los paneles.
    noche: {
        50: '#e8eef8', 100: '#c9d6ea', 200: '#9fb3d3', 300: '#7189b3', 400: '#4d6690', 500: '#344d75',
        600: '#243a5e', 700: '#1a2c4a', 800: '#122038', 900: '#0c172a', 950: '#070e1c',
    },
    // Azul brillante: enlaces y elementos activos.
    acento: {
        50: '#eef7ff', 100: '#d9edff', 200: '#bfe0ff', 300: '#8ccaff', 400: '#52adff', 500: '#2a8fff',
        600: '#1673f0', 700: '#105cc4', 800: '#134e9e', 900: '#16437c', 950: '#122a4b',
    },
    // Ámbar del dial de la radio: reloj UTC.
    dial: {
        50: '#fff8eb', 100: '#ffefc6', 200: '#ffdf8f', 300: '#ffd27a', 400: '#ffb84a', 500: '#f99a1c',
        600: '#dd7708', 700: '#b7550b', 800: '#94420f', 900: '#7a3710', 950: '#461b04',
    },
};

const familiasDeTailwind = [
    'slate', 'rose', 'emerald', 'amber', 'sky', 'yellow', 'violet', 'teal', 'purple',
    'orange', 'lime', 'indigo', 'fuchsia', 'cyan', 'blue', 'red', 'green',
];

const paletas = {
    ...Object.fromEntries(familiasDeTailwind.map((familia) => [familia, tailwindColors[familia]])),
    ...paletasPropias,
};

/** "#52adff" → "82 173 255" (formato que acepta rgb() con opacidad de Tailwind). */
const aRgb = (hex) => {
    const valor = hex.replace('#', '');
    return [0, 2, 4].map((i) => parseInt(valor.slice(i, i + 2), 16)).join(' ');
};

const variable = (nombre) => `rgb(var(--c-${nombre}) / <alpha-value>)`;

const colores = Object.fromEntries(
    Object.keys(paletas).map((familia) => [
        familia,
        Object.fromEntries(PASOS.map((paso) => [paso, variable(`${familia}-${paso}`)])),
    ]),
);

const variablesOscuro = {
    '--c-white': '255 255 255',
    '--c-black': '0 0 0',
};
const variablesClaro = {
    '--c-white': '15 23 42',
    '--c-black': '255 255 255',
};

for (const [familia, escala] of Object.entries(paletas)) {
    PASOS.forEach((paso, indice) => {
        variablesOscuro[`--c-${familia}-${paso}`] = aRgb(escala[paso]);
        variablesClaro[`--c-${familia}-${paso}`] = aRgb(escala[PASOS[PASOS.length - 1 - indice]]);
    });
}

/** @type {import('tailwindcss').Config} */
export default {
    content: ['./index.html', './src/**/*.{ts,tsx}'],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', ...defaultTheme.fontFamily.sans],
                // Fuente geométrica para títulos (estilo vitrina).
                display: ['Unbounded', 'Inter', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                ...colores,
                white: variable('white'),
                black: variable('black'),
                // Fijos en ambos temas: botones principales, logo e insignias.
                marca: paletasPropias.acento,
                blanco: '#ffffff',
            },
            borderRadius: {
                '4xl': '2rem',
            },
            boxShadow: {
                vidrio: 'inset 0 1px 0 0 rgb(255 255 255 / 0.06), 0 20px 50px -20px rgb(0 0 0 / 0.6)',
                'vidrio-lg': 'inset 0 1px 0 0 rgb(255 255 255 / 0.08), 0 30px 80px -20px rgb(0 0 0 / 0.75)',
                'vidrio-claro': 'inset 0 1px 0 0 rgb(255 255 255 / 0.9), 0 18px 40px -22px rgb(15 23 42 / 0.25), 0 2px 6px -2px rgb(15 23 42 / 0.06)',
                boton: 'inset 0 1px 0 0 rgb(255 255 255 / 0.25), 0 8px 24px -8px rgb(42 143 255 / 0.65)',
            },
        },
    },

    plugins: [
        forms,
        plugin(({ addBase }) => {
            addBase({
                ':root': variablesOscuro,
                'html.claro': variablesClaro,
            });
        }),
    ],
};
