/**
 * Qué tan segura es una contraseña, calculado en el navegador mientras se escribe.
 *
 * Las reglas obligatorias son las mismas del servidor (Password::defaults en AppServiceProvider):
 * mínimo 8 caracteres, con letras y números. El puntaje suma además longitud, mayúsculas y
 * minúsculas, símbolos, y resta patrones fáciles de adivinar.
 */
export interface Requisito {
    texto: string;
    cumplido: boolean;
}

export interface Seguridad {
    /** 0 = vacía, 1 = muy débil … 5 = muy fuerte. */
    nivel: 0 | 1 | 2 | 3 | 4 | 5;
    etiqueta: string;
    requisitos: Requisito[];
    /** Cumple lo que pide el servidor para aceptarla. */
    valida: boolean;
}

const ETIQUETAS = ['', 'Muy débil', 'Débil', 'Aceptable', 'Fuerte', 'Muy fuerte'] as const;

const COMUNES = ['password', 'contrasena', 'contraseña', '12345678', 'qwerty', 'abc123', 'admin', 'radio', 'libro', 'guardia', '73'];

export function evaluarContrasena(contrasena: string, datosPersonales: string[] = []): Seguridad {
    const tieneLetras = /\p{L}/u.test(contrasena);
    const tieneNumeros = /\d/.test(contrasena);
    const requisitos: Requisito[] = [
        { texto: 'Al menos 8 caracteres', cumplido: contrasena.length >= 8 },
        { texto: 'Letras', cumplido: tieneLetras },
        { texto: 'Números', cumplido: tieneNumeros },
    ];
    const valida = requisitos.every((requisito) => requisito.cumplido);

    if (contrasena === '') {
        return { nivel: 0, etiqueta: '', requisitos, valida: false };
    }

    let puntos = 0;
    puntos += contrasena.length >= 8 ? 1 : 0;
    puntos += contrasena.length >= 12 ? 1 : 0;
    puntos += contrasena.length >= 16 ? 1 : 0;
    puntos += /\p{Ll}/u.test(contrasena) && /\p{Lu}/u.test(contrasena) ? 1 : 0;
    puntos += tieneLetras && tieneNumeros ? 1 : 0;
    puntos += /[^\p{L}\d]/u.test(contrasena) ? 1 : 0;

    const minuscula = contrasena.toLowerCase();
    const repetida = /(.)\1{2,}/.test(contrasena);
    const secuencia = /(0123|1234|2345|3456|4567|5678|6789|abcd|bcde|cdef|qwer|asdf)/i.test(contrasena);
    const comun = COMUNES.some((palabra) => minuscula.includes(palabra));
    const personal = datosPersonales.some((dato) => dato.length >= 3 && minuscula.includes(dato.toLowerCase()));

    puntos -= (repetida ? 1 : 0) + (secuencia ? 1 : 0) + (comun ? 2 : 0) + (personal ? 2 : 0);

    // Sin cumplir lo obligatorio nunca pasa de «Débil».
    const maximo = valida ? 5 : 2;
    const nivel = Math.max(1, Math.min(maximo, Math.round((puntos / 6) * 5))) as Seguridad['nivel'];

    return { nivel, etiqueta: ETIQUETAS[nivel], requisitos, valida };
}
