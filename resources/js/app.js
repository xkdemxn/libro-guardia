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
 * - Modo: botones. SSB es un solo modo con dos variantes dentro del mismo botón: USB (banda lateral
 *   superior) y LSB (inferior). Si el operador no elige, queda USB, la más usada.
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
    /** Bajo 10 MHz (160, 80 y 40 m) la convención es LSB: se sugiere, pero no se cambia solo. */
    get sugiereLsb() {
        return this.modoActual?.nombre === 'USB' && this.mhz !== null && this.mhz < 10;
    },
    elegirModo(nombre) {
        const tipoAnterior = this.tipo;

        if (nombre === 'SSB') {
            if (! this.esSsb) {
                this.modoId = String(this.modoPorNombre('USB').id);
            }
        } else {
            this.modoId = String(this.modoPorNombre(nombre).id);
        }

        if (this.tipo !== tipoAnterior) {
            this.reiniciarReportes();
        }
    },
    elegirLateral(nombre) {
        this.modoId = String(this.modoPorNombre(nombre).id);
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
/**
 * Foto de perfil con editor de recorte: se arrastra para mover y se acerca con el deslizador o la rueda.
 * Al aplicar, el navegador dibuja el cuadro visible en un lienzo de 512×512 y pone ese JPG en el campo
 * «foto» del formulario: así se sube ya recortada y liviana, aunque la original pese mucho.
 */
Alpine.data('fotoPerfil', (fotoGuardada) => ({
    LADO: 288,
    SALIDA: 512,
    ZOOM_MAXIMO: 4,
    vistaPrevia: fotoGuardada,
    quitar: false,
    original: null,
    imagen: null,
    arrastre: null,
    editor: { abierto: false, src: null, ancho: 0, alto: 0, escala: 1, escalaMinima: 1, x: 0, y: 0 },

    elegir(evento) {
        const archivo = evento.target.files[0];
        evento.target.value = '';

        if (! archivo || ! archivo.type.startsWith('image/')) {
            return;
        }

        this.original = URL.createObjectURL(archivo);
        this.abrirEditor(this.original);
    },

    /** Vuelve a ajustar: la foto recién elegida (completa) o, si no hay, la que ya está guardada. */
    ajustar() {
        // La ruta relativa evita que el lienzo quede «contaminado» si APP_URL no coincide con el host actual.
        this.abrirEditor(this.original ?? new URL(fotoGuardada, window.location.href).pathname);
    },

    abrirEditor(src) {
        const imagen = new Image();
        imagen.onload = () => {
            this.imagen = imagen;
            Object.assign(this.editor, { src, ancho: imagen.naturalWidth, alto: imagen.naturalHeight });
            this.editor.escalaMinima = this.LADO / Math.min(imagen.naturalWidth, imagen.naturalHeight);
            this.centrar();
            this.editor.abierto = true;
        };
        imagen.src = src;
    },

    cerrarEditor() {
        this.editor.abierto = false;
        this.arrastre = null;
    },

    centrar() {
        const e = this.editor;
        e.escala = e.escalaMinima;
        e.x = (this.LADO - e.ancho * e.escala) / 2;
        e.y = (this.LADO - e.alto * e.escala) / 2;
    },

    /** La foto siempre cubre todo el cuadro: no se puede mover dejando bordes vacíos. */
    limitar() {
        const e = this.editor;
        e.x = Math.min(0, Math.max(this.LADO - e.ancho * e.escala, e.x));
        e.y = Math.min(0, Math.max(this.LADO - e.alto * e.escala, e.y));
    },

    /** Cambia el zoom manteniendo fijo el punto (cx, cy) del cuadro; por defecto, el centro. */
    acercarA(escala, cx = this.LADO / 2, cy = this.LADO / 2) {
        const e = this.editor;
        const nueva = Math.min(e.escalaMinima * this.ZOOM_MAXIMO, Math.max(e.escalaMinima, escala));
        e.x = cx - (cx - e.x) * (nueva / e.escala);
        e.y = cy - (cy - e.y) * (nueva / e.escala);
        e.escala = nueva;
        this.limitar();
    },

    acercar(factor) {
        this.acercarA(this.editor.escala * factor);
    },

    rueda(evento) {
        const cuadro = evento.currentTarget.getBoundingClientRect();
        this.acercarA(this.editor.escala * (evento.deltaY < 0 ? 1.08 : 1 / 1.08), evento.clientX - cuadro.left, evento.clientY - cuadro.top);
    },

    empezarArrastre(evento) {
        evento.currentTarget.setPointerCapture(evento.pointerId);
        this.arrastre = { px: evento.clientX, py: evento.clientY, x: this.editor.x, y: this.editor.y };
    },

    arrastrar(evento) {
        if (! this.arrastre) {
            return;
        }
        this.editor.x = this.arrastre.x + evento.clientX - this.arrastre.px;
        this.editor.y = this.arrastre.y + evento.clientY - this.arrastre.py;
        this.limitar();
    },

    soltar() {
        this.arrastre = null;
    },

    aplicarRecorte() {
        const e = this.editor;
        const lienzo = document.createElement('canvas');
        lienzo.width = lienzo.height = this.SALIDA;

        const contexto = lienzo.getContext('2d');
        contexto.fillStyle = '#ffffff';
        contexto.fillRect(0, 0, this.SALIDA, this.SALIDA);
        contexto.imageSmoothingQuality = 'high';
        contexto.drawImage(this.imagen, -e.x / e.escala, -e.y / e.escala, this.LADO / e.escala, this.LADO / e.escala, 0, 0, this.SALIDA, this.SALIDA);

        lienzo.toBlob((datos) => {
            const transferencia = new DataTransfer();
            transferencia.items.add(new File([datos], 'foto-perfil.jpg', { type: 'image/jpeg' }));
            this.$refs.entrada.files = transferencia.files;

            this.vistaPrevia = URL.createObjectURL(datos);
            this.quitar = false;
            this.cerrarEditor();
        }, 'image/jpeg', 0.9);
    },
}));

/**
 * Secciones de «Mi perfil»: muestra una sola a la vez y la recuerda en el #hash de la URL,
 * así el enlace «Cambiar contraseña» del menú abre directamente esa sección.
 */
Alpine.data('seccionesPerfil', (validas, inicial) => ({
    seccion: validas[0],

    init() {
        const delHash = () => window.location.hash.slice(1);
        this.seccion = [inicial, delHash()].find((s) => validas.includes(s)) ?? validas[0];
        history.replaceState(null, '', `#${this.seccion}`);

        window.addEventListener('hashchange', () => {
            if (validas.includes(delHash())) {
                this.seccion = delHash();
            }
        });
    },

    ir(seccion) {
        this.seccion = seccion;
        history.replaceState(null, '', `#${seccion}`);
    },
}));

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
