import axios, { AxiosError } from 'axios';

/**
 * Cliente HTTP de la API de Laravel. La sesión viaja en la cookie de Laravel y
 * axios copia la cookie XSRF-TOKEN en la cabecera X-XSRF-TOKEN de cada petición.
 */
export const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    withXSRFToken: true,
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
});

let cookieCsrfPedida = false;

/** Pide a Sanctum la cookie CSRF. Hace falta una vez antes del primer POST/PUT/DELETE. */
export async function asegurarCsrf(): Promise<void> {
    if (cookieCsrfPedida && document.cookie.includes('XSRF-TOKEN=')) {
        return;
    }

    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
    cookieCsrfPedida = true;
}

api.interceptors.request.use(async (config) => {
    if (config.method && config.method.toLowerCase() !== 'get') {
        await asegurarCsrf();
    }

    return config;
});

/** Errores de validación de Laravel (422): { campo: ['mensaje', ...] }. */
export type ErroresValidacion = Record<string, string[]>;

export function erroresDeValidacion(error: unknown): ErroresValidacion {
    if (error instanceof AxiosError && error.response?.status === 422) {
        return (error.response.data as { errors?: ErroresValidacion }).errors ?? {};
    }

    return {};
}

/** Mensaje para mostrar al operador cuando una petición falla por algo que no es validación. */
export function mensajeDeError(error: unknown): string {
    if (error instanceof AxiosError) {
        if (error.response?.status === 419) {
            cookieCsrfPedida = false;

            return 'La página expiró. Vuelve a intentarlo.';
        }

        if (error.response?.status === 429) {
            return 'Demasiados intentos. Espera un momento antes de volver a intentarlo.';
        }

        const mensaje = (error.response?.data as { message?: string } | undefined)?.message;

        if (mensaje) {
            return mensaje;
        }
    }

    return 'Algo salió mal. Revisa tu conexión e inténtalo de nuevo.';
}

export function esEstado(error: unknown, estado: number): boolean {
    return error instanceof AxiosError && error.response?.status === estado;
}
