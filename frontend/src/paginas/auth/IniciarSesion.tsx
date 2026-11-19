import { useState, type FormEvent } from 'react';
import { Link, useLocation, useNavigate } from 'react-router';
import { Ceja } from '@/componentes/Ceja';
import { CampoContrasena } from '@/componentes/CampoContrasena';
import { Aviso, BotonPrimario, Campo } from '@/componentes/Formulario';
import { useFijarUsuario } from '@/hooks/useUsuario';
import { LayoutInvitado } from '@/layouts/LayoutInvitado';
import { api, erroresDeValidacion, mensajeDeError, type ErroresValidacion } from '@/lib/api';
import type { RespuestaInicioSesion } from '@/lib/tipos';

export interface EstadoNavegacion {
    /** Página privada que el operador quería ver antes de que le pidieran iniciar sesión. */
    desde?: string;
    /** Mensaje verde que muestra la página de destino (p. ej. «Tu contraseña se restableció»). */
    estado?: string;
}

export function IniciarSesion() {
    const navegar = useNavigate();
    const { state } = useLocation() as { state: EstadoNavegacion | null };
    const fijarUsuario = useFijarUsuario();

    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [errorGeneral, setErrorGeneral] = useState<string | null>(null);
    const [enviando, setEnviando] = useState(false);

    async function enviar(evento: FormEvent<HTMLFormElement>) {
        evento.preventDefault();
        const datos = new FormData(evento.currentTarget);
        setEnviando(true);
        setErrores({});
        setErrorGeneral(null);

        try {
            const { data } = await api.post<RespuestaInicioSesion>('/sesion', {
                login: datos.get('login'),
                password: datos.get('password'),
                remember: datos.get('remember') === 'on',
            });

            if (data.segundo_factor) {
                navegar('/segundo-factor', { state: { desde: state?.desde, metodos: data.metodos } });
            } else {
                fijarUsuario(data.usuario);
                navegar(state?.desde ?? '/contactos', { replace: true });
            }
        } catch (error) {
            const validacion = erroresDeValidacion(error);
            setErrores(validacion);
            if (Object.keys(validacion).length === 0) {
                setErrorGeneral(mensajeDeError(error));
            }
        } finally {
            setEnviando(false);
        }
    }

    return (
        <LayoutInvitado>
            <Ceja className="mb-6">Acceso de operadores</Ceja>
            <h2 className="font-display text-2xl font-semibold text-white">Iniciar sesión</h2>
            <p className="mt-1.5 text-sm text-slate-400">Entra a tu libro de guardia.</p>

            {state?.estado && <Aviso className="mt-6">{state.estado}</Aviso>}
            {errorGeneral && (
                <Aviso tipo="error" className="mt-6">
                    {errorGeneral}
                </Aviso>
            )}

            <form onSubmit={enviar} className="mt-6 space-y-5" noValidate>
                <Campo id="login" etiqueta="Indicativo o correo" type="text" required autoFocus autoComplete="username" placeholder="HC1QRC" errores={errores.login} />
                <CampoContrasena
                    id="password"
                    etiqueta="Contraseña"
                    required
                    autoComplete="current-password"
                    errores={errores.password}
                    extra={
                        <Link className="text-[0.8125rem] text-acento-300 transition hover:text-acento-200" to="/olvide-contrasena">
                            ¿La olvidaste?
                        </Link>
                    }
                />

                <label htmlFor="remember" className="flex items-center gap-2.5 text-sm text-slate-400">
                    <input
                        id="remember"
                        type="checkbox"
                        name="remember"
                        className="h-4 w-4 rounded border-white/20 bg-white/5 text-acento-500 focus:ring-acento-400/30 focus:ring-offset-0"
                    />
                    Recordarme en este equipo
                </label>

                <BotonPrimario className="w-full !py-3" disabled={enviando}>
                    {enviando ? 'Entrando…' : 'Entrar'}
                </BotonPrimario>
            </form>

            <p className="mt-6 text-center text-sm text-slate-400">
                ¿No tienes cuenta?{' '}
                <Link className="font-medium text-acento-300 transition hover:text-acento-200" to="/registro">
                    Regístrate con tu indicativo
                </Link>
            </p>
            <p className="mt-2 text-center text-sm">
                <Link className="text-slate-400 transition hover:text-white" to="/indicativos">
                    Buscar un indicativo sin iniciar sesión &rsaquo;
                </Link>
            </p>
        </LayoutInvitado>
    );
}
