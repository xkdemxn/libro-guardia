/** El operador con sesión iniciada (App\Http\Resources\UsuarioResource). */
export interface Usuario {
    id: number;
    indicativo: string;
    nombres: string;
    apellidos: string;
    nombre_completo: string;
    email: string;
    correo_verificado: boolean;
    pais: string | null;
    codigo_bandera: string | null;
    foto_url: string | null;
    segundo_factor: { app: boolean; correo: boolean };
}

/** Métodos disponibles en el segundo paso del inicio de sesión. */
export interface MetodosSegundoFactor {
    app: boolean;
    correo: boolean;
    correo_enviado: boolean;
    envio_correo_disponible: boolean;
}

export type RespuestaInicioSesion =
    | { segundo_factor: false; usuario: Usuario }
    | { segundo_factor: true; metodos: MetodosSegundoFactor };

export interface Visibilidad {
    mostrar_nombre: boolean;
    mostrar_pais_ciudad: boolean;
    mostrar_equipo: boolean;
    mostrar_biografia_foto: boolean;
}

export type EstiloFondo = 'degradado' | 'completa';

/** «Mi perfil» (App\Http\Resources\PerfilResource). */
export interface Perfil extends Usuario {
    provincia: string | null;
    ciudad: string | null;
    pais_prefijo: string | null;
    pais_publico: string | null;
    grid_locator: string | null;
    equipo_radio: string | null;
    antena: string | null;
    biografia: string | null;
    tiene_foto: boolean;
    portada: { url: string | null; estilo: EstiloFondo; opacidad: number };
    visibilidad: Visibilidad;
    envio_correo_disponible: boolean;
}

/** Listas de los desplegables de ubicación. */
export interface Ubicaciones {
    paises: string[];
    provincias: string[];
    /** Cantones de Ecuador por provincia. */
    ecuador: Record<string, string[]>;
    /** Ciudades por país (fuera de Ecuador). */
    ciudades: Record<string, string[]>;
}

/** Perfil público por indicativo (User::datosPublicos). */
export interface PerfilPublico {
    indicativo: string;
    pais: string | null;
    bandera: string | null;
    zona: string | null;
    nombre: string | null;
    ciudad: string | null;
    equipo_radio: string | null;
    antena: string | null;
    biografia: string | null;
    foto_url: string | null;
    portada: { url: string; estilo: EstiloFondo; opacidad: number } | null;
    es_propio: boolean;
}

/** Respuesta de la configuración de la verificación en dos pasos. */
export interface EstadoSegundoFactor {
    mensaje: string;
    app: boolean;
    correo: boolean;
    qr_svg: string | null;
    clave_manual: string | null;
    codigos_recuperacion: string[] | null;
}

export interface Conteo {
    nombre: string;
    cantidad: number;
}

export interface Estadisticas {
    hay_contactos: boolean;
    totales: { contactos: number; paises: number; confirmados: number; bandas: number };
    por_banda: Conteo[];
    por_modo: Conteo[];
    por_pais: Conteo[];
    dxcc: { confirmados: number; meta: number };
    continentes: { codigo: string; nombre: string; trabajado: boolean }[];
    zonas: { numero: number; provincias: string[]; contactos: number; propia: boolean }[];
    origen: { lat: number; lon: number } | null;
    puntos: { lat: number; lon: number; lugar: string; cantidad: number; indicativos: string[] }[];
    mas_lejano: { indicativo: string; lugar: string; km: number } | null;
}

export interface BandaCatalogo {
    id: number;
    nombre: string;
    min: number;
    max: number;
    rango: 'MF' | 'HF' | 'VHF' | 'UHF';
}

export interface ModoCatalogo {
    id: number;
    nombre: string;
    etiqueta: string;
    tipo: 'RS' | 'RST' | 'dB';
    ejemplo: string;
}

export interface Catalogos {
    bandas: BandaCatalogo[];
    modos: ModoCatalogo[];
    hoy_utc: string;
}

export type EstadoQsl = 'confirmado' | 'enviada' | 'pendiente';

/** Un contacto del libro (App\Http\Resources\ContactoResource). */
export interface Contacto {
    id: number;
    estacion_llamada: string;
    fecha: string;
    hora_inicio: string;
    hora_fin: string | null;
    frecuencia_mhz: number;
    banda: { id: number; nombre: string };
    modo: { id: number; nombre: string; etiqueta: string };
    pais: { nombre: string; codigo_bandera: string | null } | null;
    zona: { nombre: string; descripcion: string } | null;
    reporte_enviado: string;
    reporte_recibido: string;
    estado_qsl: EstadoQsl;
}

export type TipoQslManual = 'tarjeta' | 'eqsl' | 'lotw';

export interface ConfirmacionQsl {
    etiqueta: string;
    enviada: boolean;
    fecha_envio: string | null;
    recibida: boolean;
    fecha_recepcion: string | null;
}

/** El contacto con todo lo del detalle y el formulario de edición. */
export interface ContactoDetalle extends Contacto {
    potencia_w: number | null;
    grid_locator: string | null;
    nombre_operador: string | null;
    equipo: string | null;
    activacion: string | null;
    observaciones: string | null;
    distancia_km: number | null;
    es_usuario: boolean;
    rechazado_por_contraparte: string | null;
    confirmado_internamente: boolean;
    confirmaciones: Record<TipoQslManual, ConfirmacionQsl>;
    fecha_minima: string;
}

export interface ResumenLibro {
    contactos: number;
    paises: number;
    confirmados: number;
    bandas: number;
    solicitudes_pendientes: number;
    /** Letras con las que empieza algún indicativo del libro. */
    iniciales: string[];
}

/** Página de resultados de Laravel (ResourceCollection paginada). */
export interface Paginado<T> {
    data: T[];
    meta: { current_page: number; last_page: number; total: number; from: number | null; to: number | null };
}

export interface Solicitud {
    id: number;
    indicativo: string;
    fecha: string;
    hora_inicio: string;
    hora_fin: string | null;
    frecuencia_mhz: number;
    banda: string;
    modo: string;
    reporte_enviado: string;
    reporte_recibido: string;
}

/** Ficha rápida del indicativo (innovación 3). */
export type Ficha =
    | { valido: false }
    | {
          valido: true;
          indicativo: string;
          pais: string | null;
          zona: string | null;
          codigo_bandera: string | null;
          contactos_previos: number;
          ultimo: { fecha: string; hace: string; banda: string; modo: string } | null;
          registrado: boolean;
          perfil: { nombre: string | null; ciudad: string | null; foto_url: string | null } | null;
      };

export interface FilaImportacion {
    numero: number;
    estado: 'nuevo' | 'duplicado' | 'error';
    indicativo: string | null;
    fecha: string | null;
    banda: string | null;
    modo: string | null;
    errores: string[];
}

export interface VistaPreviaImportacion {
    nombre_archivo: string;
    total_filas: number;
    nuevos: number;
    duplicados: number;
    errores: number;
    filas: FilaImportacion[];
}

export interface ResultadoImportacion {
    importados: number;
    omitidos: number;
    fallidos: number;
    errores: { numero: number; indicativo: string | null; errores: string[] }[];
}

export type DisenoTarjeta = 'clasico' | 'invertido' | 'inferior' | 'centrado';

export interface PersonalizacionTarjeta {
    /** Foto como data URI (la tarjeta se exporta a PNG en el navegador y no puede cargar imágenes externas). */
    foto: string | null;
    estilo: EstiloFondo;
    opacidad: number;
    mensaje: string | null;
    diseno: DisenoTarjeta;
    mensaje_predeterminado: string;
    mensaje_maximo: number;
}

export interface Tarjeta {
    svg: string;
    nombre_archivo: string;
    envio_disponible: boolean;
    personalizacion: PersonalizacionTarjeta;
}

/** Resultado de «Consulta una QSL». */
export interface QslVerificada {
    qso: number;
    de: string;
    para: string;
    fecha: string;
    hora_utc: string;
    frecuencia_mhz: number;
    banda: string;
    modo: string;
    reporte: string;
    estado_qsl: EstadoQsl;
    verificado: boolean;
    svg: string;
}
