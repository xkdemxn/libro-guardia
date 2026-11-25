const dosDigitos = (numero: number): string => String(numero).padStart(2, '0');

export const fechaUtc = (fecha: Date): string =>
    `${fecha.getUTCFullYear()}-${dosDigitos(fecha.getUTCMonth() + 1)}-${dosDigitos(fecha.getUTCDate())}`;

export const horaUtc = (fecha: Date, conSegundos = false): string => {
    const hora = `${dosDigitos(fecha.getUTCHours())}:${dosDigitos(fecha.getUTCMinutes())}`;

    return conSegundos ? `${hora}:${dosDigitos(fecha.getUTCSeconds())}` : hora;
};
