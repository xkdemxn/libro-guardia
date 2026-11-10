import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', ...defaultTheme.fontFamily.sans],
                // Fuente geométrica para títulos (estilo vitrina).
                display: ['Unbounded', 'Inter', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Azul nocturno del fondo y los paneles.
                noche: {
                    50: '#e8eef8',
                    100: '#c9d6ea',
                    200: '#9fb3d3',
                    300: '#7189b3',
                    400: '#4d6690',
                    500: '#344d75',
                    600: '#243a5e',
                    700: '#1a2c4a',
                    800: '#122038',
                    900: '#0c172a',
                    950: '#070e1c',
                },
                // Azul brillante: acciones principales y elementos activos.
                acento: {
                    200: '#bfe0ff',
                    300: '#8ccaff',
                    400: '#52adff',
                    500: '#2a8fff',
                    600: '#1673f0',
                    700: '#105cc4',
                },
                // Ámbar del dial de la radio: reloj UTC.
                dial: {
                    300: '#ffd27a',
                    400: '#ffb84a',
                    500: '#f99a1c',
                },
            },
            borderRadius: {
                '4xl': '2rem',
            },
            boxShadow: {
                vidrio: 'inset 0 1px 0 0 rgb(255 255 255 / 0.06), 0 20px 50px -20px rgb(0 0 0 / 0.6)',
                'vidrio-lg': 'inset 0 1px 0 0 rgb(255 255 255 / 0.08), 0 30px 80px -20px rgb(0 0 0 / 0.75)',
                boton: 'inset 0 1px 0 0 rgb(255 255 255 / 0.25), 0 8px 24px -8px rgb(42 143 255 / 0.65)',
            },
        },
    },

    plugins: [forms],
};
