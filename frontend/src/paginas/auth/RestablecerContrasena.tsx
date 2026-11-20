import { useState, type FormEvent } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router';
import { Ceja } from '@/componentes/Ceja';
import { CampoContrasena } from '@/componentes/CampoContrasena';
import { Aviso, BotonPrimario, Campo } from '@/componentes/Formulario';
import { LayoutInvitado } from '@/layouts/LayoutInvitado';
import { api, erroresDeValidacion, mensajeDeError, type ErroresValidacion } from '@/lib/api';
import { useTranslation } from 'react-i18next';

/** Nueva contraseña con el enlace que llegó por correo: /restablecer-contrasena/:token?email=… */
export function RestablecerContrasena() {
    const { t } = useTranslation();
    const { token } = useParams();
    const [parametros] = useSearchParams();
    const navegar = useNavigate();
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [errorGeneral, setErrorGeneral] = useState<string | null>(null);
    const [enviando, setEnviando] = useState(false);

    async function enviar(evento: FormEvent<HTMLFormElement>) {
        evento.preventDefault();
        setEnviando(true);
        setErrores({});
        setErrorGeneral(null);

        try {
            const { data } = await api.post<{ mensaje: string }>('/contrasena/restablecer', {
                token,
                ...Object.fromEntries(new FormData(evento.currentTarget)),
            });
            navegar('/iniciar-sesion', { replace: true, state: { estado: data.mensaje } });
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
            <Ceja className="mb-6">{t('Recuperar acceso')}</Ceja>
            <h2 className="font-display text-2xl font-semibold text-white">{t('Nueva contraseña')}</h2>
            <p className="mt-1.5 text-sm text-slate-400">{t('Elige una contraseña nueva para tu cuenta.')}</p>

            {errorGeneral && (
                <Aviso tipo="error" className="mt-6">
                    {errorGeneral}
                </Aviso>
            )}

            <form onSubmit={enviar} className="mt-6 space-y-5" noValidate>
                <Campo
                    id="email"
                    etiqueta={t('Correo electrónico')}
                    type="email"
                    required
                    autoComplete="username"
                    defaultValue={parametros.get('email') ?? ''}
                    errores={errores.email}
                />
                <CampoContrasena
                    id="password"
                    etiqueta={t('Contraseña nueva')}
                    required
                    autoFocus
                    autoComplete="new-password"
                    medirSeguridad
                    datosPersonales={[parametros.get('email')?.split('@')[0] ?? '']}
                    errores={errores.password}
                />
                <CampoContrasena
                    id="password_confirmation"
                    etiqueta={t('Confirmar contraseña')}
                    required
                    autoComplete="new-password"
                    errores={errores.password_confirmation}
                />

                <BotonPrimario className="w-full !py-3" disabled={enviando}>
                    {enviando ? t('Guardando…') : t('Guardar contraseña')}
                </BotonPrimario>
            </form>
        </LayoutInvitado>
    );
}
