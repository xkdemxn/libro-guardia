import { useState, type FormEvent } from 'react';
import { CampoContrasena } from '@/componentes/CampoContrasena';
import { Aviso, BotonPrimario } from '@/componentes/Formulario';
import { api, erroresDeValidacion, mensajeDeError, type ErroresValidacion } from '@/lib/api';
import type { Perfil } from '@/lib/tipos';
import { useTranslation } from 'react-i18next';

export function CambiarContrasena({ perfil }: { perfil: Perfil }) {
    const { t } = useTranslation();
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [estado, setEstado] = useState<{ tipo: 'exito' | 'error'; texto: string } | null>(null);
    const [enviando, setEnviando] = useState(false);
    // Cambia al guardar para vaciar los campos (y la barra de seguridad).
    const [version, setVersion] = useState(0);

    async function guardar(evento: FormEvent<HTMLFormElement>) {
        evento.preventDefault();
        setEnviando(true);
        setErrores({});
        setEstado(null);

        try {
            const { data } = await api.put<{ mensaje: string }>('/contrasena', Object.fromEntries(new FormData(evento.currentTarget)));
            setEstado({ tipo: 'exito', texto: data.mensaje });
            setVersion((actual) => actual + 1);
        } catch (error) {
            const validacion = erroresDeValidacion(error);
            setErrores(validacion);
            if (Object.keys(validacion).length === 0) {
                setEstado({ tipo: 'error', texto: mensajeDeError(error) });
            }
        } finally {
            setEnviando(false);
        }
    }

    return (
        <section>
            <header>
                <h2 className="font-display text-lg font-semibold text-white">{t('Cambiar contraseña')}</h2>
                <p className="mt-1 text-sm text-slate-400">{t('Usa al menos 8 caracteres, con letras y números. Una contraseña larga es más segura.')}</p>
            </header>

            {estado && (
                <Aviso tipo={estado.tipo} className="mt-6">
                    {estado.texto}
                </Aviso>
            )}

            <form key={version} onSubmit={guardar} className="mt-6 space-y-6" noValidate>
                <CampoContrasena id="current_password" etiqueta={t('Contraseña actual')} autoComplete="current-password" errores={errores.current_password} />
                <CampoContrasena
                    id="password"
                    etiqueta={t('Nueva contraseña')}
                    autoComplete="new-password"
                    medirSeguridad
                    datosPersonales={[perfil.indicativo, perfil.nombres, perfil.apellidos, perfil.email.split('@')[0]]}
                    errores={errores.password}
                />
                <CampoContrasena id="password_confirmation" etiqueta={t('Repite la nueva contraseña')} autoComplete="new-password" errores={errores.password_confirmation} />

                <BotonPrimario className="!px-7" disabled={enviando}>
                    {enviando ? t('Guardando…') : t('Guardar contraseña')}
                </BotonPrimario>
            </form>
        </section>
    );
}
