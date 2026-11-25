/**
 * Reduce una imagen a JPG con el lado mayor de `lado` píxeles como máximo. Así una foto pesada
 * del celular sube rápido y entra en el límite del servidor. Devuelve el archivo y un data URL
 * para la vista previa.
 */
export function reducirImagen(archivo: File, lado: number, nombre: string): Promise<{ archivo: File; previa: string }> {
    return new Promise((resolver, rechazar) => {
        const imagen = new Image();
        imagen.onerror = () => rechazar(new Error('No se pudo leer la imagen.'));
        imagen.onload = () => {
            const factor = Math.min(1, lado / Math.max(imagen.naturalWidth, imagen.naturalHeight));
            const lienzo = document.createElement('canvas');
            lienzo.width = Math.round(imagen.naturalWidth * factor);
            lienzo.height = Math.round(imagen.naturalHeight * factor);

            const contexto = lienzo.getContext('2d')!;
            contexto.fillStyle = '#ffffff';
            contexto.fillRect(0, 0, lienzo.width, lienzo.height);
            contexto.imageSmoothingQuality = 'high';
            contexto.drawImage(imagen, 0, 0, lienzo.width, lienzo.height);
            URL.revokeObjectURL(imagen.src);

            lienzo.toBlob(
                (datos) => {
                    if (!datos) {
                        rechazar(new Error('No se pudo convertir la imagen.'));

                        return;
                    }
                    resolver({ archivo: new File([datos], nombre, { type: 'image/jpeg' }), previa: lienzo.toDataURL('image/jpeg', 0.85) });
                },
                'image/jpeg',
                0.85,
            );
        };
        imagen.src = URL.createObjectURL(archivo);
    });
}
