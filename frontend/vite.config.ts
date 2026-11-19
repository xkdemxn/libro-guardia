import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { fileURLToPath } from 'node:url';

/**
 * La SPA corre en http://localhost:5173 y Laravel en http://localhost:8000.
 * El proxy hace que el navegador vea un solo origen: la cookie de sesión y la de CSRF
 * de Laravel funcionan sin configurar CORS.
 */
const laravel = 'http://localhost:8000';

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
    },
    server: {
        port: 5173,
        strictPort: true,
        proxy: {
            '/api': laravel,
            '/sanctum': laravel,
            '/storage': laravel,
            '/banderas': laravel,
        },
    },
});
