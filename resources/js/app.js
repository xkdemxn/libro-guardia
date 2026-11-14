import Alpine from 'alpinejs';

window.Alpine = Alpine;

const dosDigitos = (numero) => String(numero).padStart(2, '0');

const fechaUtc = (fecha) => `${fecha.getUTCFullYear()}-${dosDigitos(fecha.getUTCMonth() + 1)}-${dosDigitos(fecha.getUTCDate())}`;

const horaUtc = (fecha, conSegundos = false) => {
    const hora = `${dosDigitos(fecha.getUTCHours())}:${dosDigitos(fecha.getUTCMinutes())}`;

    return conSegundos ? `${hora}:${dosDigitos(fecha.getUTCSeconds())}` : hora;
};

/**
 * Modo claro / oscuro. El tema se guarda en el navegador; sin elección se usa el del sistema
 * (ver el script en layouts/partials/head.blade.php, que lo aplica antes de pintar).
 */
const aplicarTema = (claro) => {
    document.documentElement.classList.toggle('claro', claro);
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', claro ? '#e8eef8' : '#070e1c');

    try {
        localStorage.setItem('tema', claro ? 'claro' : 'oscuro');
    } catch {
        // Sin almacenamiento (modo privado): el tema solo dura esta visita.
    }

    window.dispatchEvent(new CustomEvent('tema-cambiado', { detail: { claro } }));
};

Alpine.data('selectorTema', () => ({
    claro: document.documentElement.classList.contains('claro'),
    alternar() {
        this.claro = ! this.claro;
        aplicarTema(this.claro);
    },
}));

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
 * Formulario de contacto.
 *
 * - Banda: se elige por rango (HF/VHF/UHF) y la frecuencia con un deslizador dentro de la banda;
 *   o al revés, escribiendo la frecuencia la banda se marca sola.
 * - Modo: botones. SSB tiene dos bandas laterales: inferior (LSB) y superior (USB); se elige sola
 *   por convención (LSB por debajo de 10 MHz, USB por encima) y el operador puede cambiarla.
 * - Reportes: botón rápido 59 (voz) o 599 (CW); «Otro» permite armarlo con R, S y T si no se recibió bien.
 *
 * @param {Array<{id: number, nombre: string, tipo: string, ejemplo: string}>} modos
 * @param {string} modoInicial  id del modo
 * @param {Array<{nombre: string, min: number, max: number, rango: string}>} bandas
 * @param {string} frecuenciaInicial
 * @param {string} urlFicha
 * @param {string} indicativoInicial
 * @param {{enviado: string, recibido: string}} reportesIniciales
 */
Alpine.data('formularioContacto', (modos, modoInicial, bandas, frecuenciaInicial, urlFicha, indicativoInicial, reportesIniciales) => ({
    modos,
    bandas,
    modoId: modoInicial,
    frecuencia: frecuenciaInicial ?? '',
    rangoVisible: null,
    lateralManual: false,
    indicativo: indicativoInicial ?? '',
    ficha: null,
    buscandoFicha: false,
    reporte: { enviado: reportesIniciales?.enviado ?? '', recibido: reportesIniciales?.recibido ?? '' },
    manual: { enviado: false, recibido: false },
    rst: { enviado: { r: 5, s: 9, t: 9 }, recibido: { r: 5, s: 9, t: 9 } },

    init() {
        this.rangoVisible = this.bandaActual?.rango ?? 'HF';

        for (const lado of ['enviado', 'recibido']) {
            // Un reporte distinto de 59 / 599 se abre directamente en modo «Otro».
            if (this.reporte[lado] && this.reporte[lado] !== this.reporteRapido) {
                this.abrirManual(lado);
            }
        }

        this.$watch('frecuencia', () => {
            if (this.bandaActual) {
                this.rangoVisible = this.bandaActual.rango;
            }
            this.ajustarBandaLateral();
        });

        if (this.indicativo) {
            this.buscarFicha();
        }
    },

    /* ---------- Banda y frecuencia ---------- */

    get mhz() {
        const valor = parseFloat(String(this.frecuencia).replace(',', '.'));

        return Number.isNaN(valor) ? null : valor;
    },
    get bandaActual() {
        return this.mhz === null ? null : this.bandas.find((banda) => this.mhz >= banda.min && this.mhz <= banda.max) ?? null;
    },
    /** null = sin frecuencia, false = fuera de banda, texto = nombre de la banda. */
    get banda() {
        if (this.mhz === null) {
            return null;
        }

        return this.bandaActual?.nombre ?? false;
    },
    bandasDelRango(rango) {
        return this.bandas.filter((banda) => (rango === 'HF' ? ['MF', 'HF'].includes(banda.rango) : banda.rango === rango));
    },
    elegirBanda(banda) {
        if (this.bandaActual?.nombre !== banda.nombre) {
            this.frecuencia = banda.min.toFixed(3);
        }
    },
    pasoDeslizador(banda) {
        return banda.max - banda.min > 20 ? 0.025 : 0.001;
    },

    /* ---------- Modo ---------- */

    get modoActual() {
        return this.modos.find((modo) => String(modo.id) === String(this.modoId)) ?? null;
    },
    get esSsb() {
        return ['USB', 'LSB'].includes(this.modoActual?.nombre);
    },
    get tipo() {
        return this.modoActual?.tipo ?? '';
    },
    get ejemplo() {
        return this.modoActual?.ejemplo ?? '';
    },
    modoPorNombre(nombre) {
        return this.modos.find((modo) => modo.nombre === nombre);
    },
    lateralSugerida() {
        return this.mhz !== null && this.mhz < 10 ? 'LSB' : 'USB';
    },
    elegirModo(nombre) {
        const tipoAnterior = this.tipo;

        if (nombre === 'SSB') {
            this.lateralManual = false;
            this.modoId = String(this.modoPorNombre(this.lateralSugerida()).id);
        } else {
            this.modoId = String(this.modoPorNombre(nombre).id);
        }

        if (this.tipo !== tipoAnterior) {
            this.reiniciarReportes();
        }
    },
    elegirLateral(nombre) {
        this.lateralManual = true;
        this.modoId = String(this.modoPorNombre(nombre).id);
    },
    ajustarBandaLateral() {
        if (this.esSsb && ! this.lateralManual) {
            this.modoId = String(this.modoPorNombre(this.lateralSugerida()).id);
        }
    },
    botonActivo(nombre) {
        return nombre === 'SSB' ? this.esSsb : this.modoActual?.nombre === nombre;
    },

    /* ---------- Reportes ---------- */

    get reporteRapido() {
        return { RS: '59', RST: '599' }[this.tipo] ?? '';
    },
    reiniciarReportes() {
        for (const lado of ['enviado', 'recibido']) {
            this.manual[lado] = false;
            this.reporte[lado] = this.reporteRapido;
        }
    },
    usarRapido(lado) {
        this.manual[lado] = false;
        this.reporte[lado] = this.reporteRapido;
    },
    abrirManual(lado) {
        const actual = String(this.reporte[lado] ?? '');

        if (/^[1-5][1-9][1-9]?$/.test(actual)) {
            this.rst[lado] = { r: Number(actual[0]), s: Number(actual[1]), t: Number(actual[2] ?? 9) };
        }

        this.manual[lado] = true;
        this.componer(lado);
    },
    componer(lado) {
        const { r, s, t } = this.rst[lado];
        this.reporte[lado] = this.tipo === 'RST' ? `${r}${s}${t}` : `${r}${s}`;
    },

    /* ---------- Ficha rápida del indicativo (innovación 3) ---------- */

    async buscarFicha() {
        const indicativo = this.indicativo.replace(/\s+/g, '').toUpperCase();

        if (indicativo.length < 3) {
            this.ficha = null;

            return;
        }

        this.buscandoFicha = true;

        try {
            const respuesta = await fetch(`${urlFicha}?indicativo=${encodeURIComponent(indicativo)}`, {
                headers: { Accept: 'application/json' },
            });
            const ficha = await respuesta.json();

            // Si el usuario siguió escribiendo, descartamos la respuesta vieja.
            if (ficha.indicativo === this.indicativo.replace(/\s+/g, '').toUpperCase()) {
                this.ficha = ficha.valido ? ficha : null;
            }
        } catch {
            this.ficha = null;
        } finally {
            this.buscandoFicha = false;
        }
    },

    /* ---------- Fecha y hora ---------- */

    ponerAhora(cual) {
        const ahora = new Date();

        if (cual === 'inicio' || !this.$refs.fecha.value) {
            this.$refs.fecha.value = fechaUtc(ahora);
        }

        (cual === 'inicio' ? this.$refs.horaInicio : this.$refs.horaFin).value = horaUtc(ahora);
    },
}));

/**
 * Descarga la tarjeta QSL (SVG en la página) como imagen PNG al doble de resolución.
 *
 * @param {string} nombreArchivo  sin extensión
 */
Alpine.data('tarjetaQsl', (nombreArchivo) => ({
    generando: false,
    error: '',
    async descargarPng() {
        this.generando = true;
        this.error = '';

        try {
            const svg = this.$refs.tarjeta.querySelector('svg').cloneNode(true);
            svg.setAttribute('width', '1400');
            svg.setAttribute('height', '900');

            const url = URL.createObjectURL(new Blob([new XMLSerializer().serializeToString(svg)], { type: 'image/svg+xml;charset=utf-8' }));
            const imagen = new Image();
            await new Promise((resolver, rechazar) => {
                imagen.onload = resolver;
                imagen.onerror = rechazar;
                imagen.src = url;
            });

            const lienzo = document.createElement('canvas');
            lienzo.width = 2800;
            lienzo.height = 1800;
            const contexto = lienzo.getContext('2d');
            contexto.scale(2, 2);
            contexto.drawImage(imagen, 0, 0, 1400, 900);
            URL.revokeObjectURL(url);

            const png = await new Promise((resolver) => lienzo.toBlob(resolver, 'image/png'));
            const enlace = document.createElement('a');
            enlace.href = URL.createObjectURL(png);
            enlace.download = `${nombreArchivo}.png`;
            enlace.click();
            URL.revokeObjectURL(enlace.href);
        } catch {
            this.error = 'No se pudo generar la imagen. Prueba con «Descargar SVG».';
        } finally {
            this.generando = false;
        }
    },
}));

Alpine.start();
