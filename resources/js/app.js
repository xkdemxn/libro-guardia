import Alpine from 'alpinejs';

window.Alpine = Alpine;

const dosDigitos = (numero) => String(numero).padStart(2, '0');

const fechaUtc = (fecha) => `${fecha.getUTCFullYear()}-${dosDigitos(fecha.getUTCMonth() + 1)}-${dosDigitos(fecha.getUTCDate())}`;

const horaUtc = (fecha, conSegundos = false) => {
    const hora = `${dosDigitos(fecha.getUTCHours())}:${dosDigitos(fecha.getUTCMinutes())}`;

    return conSegundos ? `${hora}:${dosDigitos(fecha.getUTCSeconds())}` : hora;
};

/**
 * Reloj UTC en vivo. En radioafición todos los contactos se anotan en UTC.
 */
Alpine.data('relojUtc', () => ({
    hora: horaUtc(new Date(), true),
    fecha: fechaUtc(new Date()),
    init() {
        this.intervalo = setInterval(() => {
            const ahora = new Date();
            this.hora = horaUtc(ahora, true);
            this.fecha = fechaUtc(ahora);
        }, 1000);
    },
    destroy() {
        clearInterval(this.intervalo);
    },
}));

/**
 * Formulario de contacto: botón «Ahora», ejemplo de reporte según el modo y banda calculada en vivo.
 *
 * @param {Object<string, {tipo: string, ejemplo: string}>} tiposPorModo
 * @param {string} modoInicial
 * @param {Array<{nombre: string, min: number, max: number}>} bandas
 * @param {string} frecuenciaInicial
 */
Alpine.data('formularioContacto', (tiposPorModo, modoInicial, bandas, frecuenciaInicial) => ({
    modoId: modoInicial,
    frecuencia: frecuenciaInicial ?? '',
    get tipo() {
        return tiposPorModo[this.modoId]?.tipo ?? '';
    },
    get ejemplo() {
        return tiposPorModo[this.modoId]?.ejemplo ?? '';
    },
    get banda() {
        const mhz = parseFloat(String(this.frecuencia).replace(',', '.'));

        if (Number.isNaN(mhz)) {
            return null;
        }

        return bandas.find((banda) => mhz >= banda.min && mhz <= banda.max)?.nombre ?? false;
    },
    ponerAhora(cual) {
        const ahora = new Date();

        if (cual === 'inicio' || !this.$refs.fecha.value) {
            this.$refs.fecha.value = fechaUtc(ahora);
        }

        (cual === 'inicio' ? this.$refs.horaInicio : this.$refs.horaFin).value = horaUtc(ahora);
    },
}));

Alpine.start();
