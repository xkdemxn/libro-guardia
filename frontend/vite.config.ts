import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { fileURLToPath } from 'node:url';

/**
 * En desarrollo la SPA corre en http://localhost:5173 y Laravel en http://localhost:8000.
 * El proxy hace que el navegador vea un solo origen: la cookie de sesión y la de CSRF
 * de Laravel funcionan sin configurar CORS.
 */
const laravel = 'http://localhost:8000';

export default defineConfig(({ command }) => ({
    plugins: [react()],
    // En producción la SPA se compila dentro de backend/public/spa y la sirve Laravel (SpaController).
    base: command === 'build' ? '/spa/' : '/',
    build: {
        outDir: '../backend/public/spa',
        emptyOutDir: true,
    },
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
}));
