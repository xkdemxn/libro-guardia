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
