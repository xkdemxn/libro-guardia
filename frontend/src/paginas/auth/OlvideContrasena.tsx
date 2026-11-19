import { useState, type FormEvent } from 'react';
import { Link } from 'react-router';
import { Ceja } from '@/componentes/Ceja';
import { Aviso, BotonPrimario, Campo } from '@/componentes/Formulario';
import { LayoutInvitado } from '@/layouts/LayoutInvitado';
import { api, erroresDeValidacion, mensajeDeError, type ErroresValidacion } from '@/lib/api';

/** Pide el enlace para restablecer la contraseña (US-18). */
export function OlvideContrasena() {
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [estado, setEstado] = useState<string | null>(null);
    const [errorGeneral, setErrorGeneral] = useState<string | null>(null);
    const [enviando, setEnviando] = useState(false);

    async function enviar(evento: FormEvent<HTMLFormElement>) {
        evento.preventDefault();
        setEnviando(true);
        setErrores({});
        setEstado(null);
        setErrorGeneral(null);

        try {
            const { data } = await api.post<{ mensaje: string }>('/contrasena/enlace', {
                email: new FormData(evento.currentTarget).get('email'),
            });
            setEstado(data.mensaje);
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
            <Ceja className="mb-6">Recuperar acceso</Ceja>
            <h2 className="font-display text-2xl font-semibold text-white">¿Olvidaste tu contraseña?</h2>
            <p className="mt-1.5 text-sm text-slate-400">Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva.</p>

            {estado && <Aviso className="mt-6">{estado}</Aviso>}
            {errorGeneral && (
                <Aviso tipo="error" className="mt-6">
                    {errorGeneral}
                </Aviso>
            )}

            <form onSubmit={enviar} className="mt-6 space-y-5" noValidate>
                <Campo id="email" etiqueta="Correo electrónico" type="email" required autoFocus autoComplete="email" errores={errores.email} />
                <BotonPrimario className="w-full !py-3" disabled={enviando}>
                    {enviando ? 'Enviando…' : 'Enviar enlace'}
                </BotonPrimario>
            </form>

            <p className="mt-6 text-center text-sm">
                <Link to="/iniciar-sesion" className="text-acento-300 transition hover:text-acento-200">
                    Volver al inicio de sesión
                </Link>
            </p>
        </LayoutInvitado>
    );
}
