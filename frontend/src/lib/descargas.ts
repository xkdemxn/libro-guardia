import { AxiosError } from 'axios';
import { api, mensajeDeError, type ErroresValidacion } from '@/lib/api';

/**
 * Descarga un archivo que genera la API (PDF, ADIF, CSV…). Pide el archivo como blob y lo
 * guarda con el nombre que manda Laravel en Content-Disposition.
 */
export async function descargar(ruta: string, datos: Record<string, unknown>, nombrePorDefecto: string): Promise<void> {
    const respuesta = await api.post<Blob>(ruta, datos, { responseType: 'blob' });
    const disposicion = String(respuesta.headers['content-disposition'] ?? '');
    const nombre = /filename\*=UTF-8''([^;]+)/i.exec(disposicion)?.[1] ?? /filename="?([^";]+)"?/i.exec(disposicion)?.[1] ?? nombrePorDefecto;

    guardarBlob(respuesta.data, decodeURIComponent(nombre));
}

export function guardarBlob(datos: Blob, nombre: string): void {
    const url = URL.createObjectURL(datos);
    const enlace = document.createElement('a');
    enlace.href = url;
    enlace.download = nombre;
    document.body.append(enlace);
    enlace.click();
    enlace.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

/**
 * Cuando una descarga falla, el JSON de error de Laravel llega como blob: se lee para mostrar
 * los errores de validación o el mensaje.
 */
export async function errorDeDescarga(error: unknown): Promise<{ errores: ErroresValidacion; mensaje: string | null }> {
    if (error instanceof AxiosError && error.response?.data instanceof Blob) {
        try {
            const cuerpo = JSON.parse(await error.response.data.text()) as { message?: string; errors?: ErroresValidacion };

            if (error.response.status === 422 && cuerpo.errors) {
                return { errores: cuerpo.errors, mensaje: null };
            }

            return { errores: {}, mensaje: cuerpo.message ?? mensajeDeError(error) };
        } catch {
            // No era JSON: se usa el mensaje genérico.
        }
    }

    return { errores: {}, mensaje: mensajeDeError(error) };
}
