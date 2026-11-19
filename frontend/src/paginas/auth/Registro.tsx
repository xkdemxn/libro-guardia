import { useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router';
import { Ceja } from '@/componentes/Ceja';
import { CampoContrasena } from '@/componentes/CampoContrasena';
import { Aviso, BotonPrimario, Campo } from '@/componentes/Formulario';
import { useFijarUsuario } from '@/hooks/useUsuario';
import { LayoutInvitado } from '@/layouts/LayoutInvitado';
import { api, erroresDeValidacion, mensajeDeError, type ErroresValidacion } from '@/lib/api';
import type { Usuario } from '@/lib/tipos';

/** Registro de un nuevo operador con su indicativo (US-01). */
export function Registro() {
    const navegar = useNavigate();
    const fijarUsuario = useFijarUsuario();
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [errorGeneral, setErrorGeneral] = useState<string | null>(null);
    const [enviando, setEnviando] = useState(false);
    const [datosPersonales, setDatosPersonales] = useState<string[]>([]);

    async function enviar(evento: FormEvent<HTMLFormElement>) {
        evento.preventDefault();
        setEnviando(true);
        setErrores({});
        setErrorGeneral(null);

        try {
            const { data } = await api.post<{ data: Usuario }>('/registro', Object.fromEntries(new FormData(evento.currentTarget)));
            fijarUsuario(data.data);
            navegar('/contactos', { replace: true });
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
            <Ceja className="mb-6">Nuevo operador</Ceja>
            <h2 className="font-display text-2xl font-semibold text-white">Crear cuenta</h2>
            <p className="mt-1.5 text-sm text-slate-400">Regístrate con tu indicativo para tener tu propio libro de guardia.</p>

            {errorGeneral && (
                <Aviso tipo="error" className="mt-6">
                    {errorGeneral}
                </Aviso>
            )}

            <form
                onSubmit={enviar}
                className="mt-6 space-y-5"
                noValidate
                onChange={(evento) => {
                    const datos = new FormData(evento.currentTarget);
                    const email = String(datos.get('email') ?? '');
                    setDatosPersonales(
                        [datos.get('indicativo'), datos.get('nombres'), datos.get('apellidos'), email.split('@')[0]].map((dato) => String(dato ?? '').trim()),
                    );
                }}
            >
                <Campo
                    id="indicativo"
                    etiqueta="Indicativo"
                    className="font-mono text-lg uppercase tracking-wider"
                    required
                    autoFocus
                    placeholder="HC1QRC"
                    maxLength={20}
                    errores={errores.indicativo}
                />

                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <Campo id="nombres" etiqueta="Nombres" required autoComplete="given-name" errores={errores.nombres} />
                    <Campo id="apellidos" etiqueta="Apellidos" required autoComplete="family-name" errores={errores.apellidos} />
                </div>

                <Campo id="email" etiqueta="Correo electrónico" type="email" required autoComplete="username" errores={errores.email} />

                <CampoContrasena
                    id="password"
                    etiqueta="Contraseña"
                    required
                    autoComplete="new-password"
                    medirSeguridad
                    datosPersonales={datosPersonales}
                    errores={errores.password}
                />
                <CampoContrasena
                    id="password_confirmation"
                    etiqueta="Confirmar contraseña"
                    required
                    autoComplete="new-password"
                    errores={errores.password_confirmation}
                />

                <BotonPrimario className="w-full !py-3" disabled={enviando}>
                    {enviando ? 'Creando…' : 'Crear mi cuenta'}
                </BotonPrimario>
            </form>

            <p className="mt-6 text-center text-sm text-slate-400">
                ¿Ya tienes cuenta?{' '}
                <Link className="font-medium text-acento-300 transition hover:text-acento-200" to="/iniciar-sesion">
                    Inicia sesión
                </Link>
            </p>
        </LayoutInvitado>
    );
}
