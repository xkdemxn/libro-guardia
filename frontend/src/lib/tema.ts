/**
 * Modo claro / oscuro. El tema se guarda en el navegador; sin elección se usa el del sistema
 * (el script de index.html lo aplica antes de pintar).
 */
export const temaClaroActivo = (): boolean => document.documentElement.classList.contains('claro');

export function aplicarTema(claro: boolean): void {
    document.documentElement.classList.toggle('claro', claro);
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', claro ? '#e8eef8' : '#070e1c');

    try {
        localStorage.setItem('tema', claro ? 'claro' : 'oscuro');
    } catch {
        // Sin almacenamiento (modo privado): el tema solo dura esta visita.
    }

    window.dispatchEvent(new CustomEvent('tema-cambiado', { detail: { claro } }));
}
