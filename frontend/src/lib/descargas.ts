import { api } from '@/lib/api';

/**
 * Descarga un archivo que genera la API (PDF, ADIF, CSV…). Pide el archivo como blob y lo
 * guarda con el nombre que manda Laravel en Content-Disposition.
 */
export async function descargar(ruta: string, datos: Record<string, unknown>, nombrePorDefecto: string): Promise<void> {
    const respuesta = await api.post<Blob>(ruta, datos, { responseType: 'blob' });
    const disposicion = String(respuesta.headers['content-disposition'] ?? '');
    const nombre = /filename\*=UTF-8''([^;]+)/i.exec(disposicion)?.[1] ?? /filename="?([^";]+)"?/i.exec(disposicion)?.[1] ?? nombrePorDefecto;

    const url = URL.createObjectURL(respuesta.data);
    const enlace = document.createElement('a');
    enlace.href = url;
    enlace.download = decodeURIComponent(nombre);
    document.body.append(enlace);
    enlace.click();
    enlace.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}
