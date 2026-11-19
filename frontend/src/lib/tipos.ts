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
