import { useState, type FormEvent, type ReactNode } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { CampoContrasena } from '@/componentes/CampoContrasena';
import { Aviso, BotonPrimario, BotonSecundario, Campo, ErrorCampo } from '@/componentes/Formulario';
import { api, erroresDeValidacion, mensajeDeError, type ErroresValidacion } from '@/lib/api';
import type { EstadoSegundoFactor, Perfil } from '@/lib/tipos';
import { useTranslation } from 'react-i18next';

type Accion = 'preparar-app' | 'desactivar-app' | 'activar-correo' | 'desactivar-correo' | 'recuperacion';

/** Verificación en dos pasos: app autenticadora (QR) y código por correo. */
export function Seguridad({ perfil }: { perfil: Perfil }) {
    const { t } = useTranslation();
    const cliente = useQueryClient();
    const [estado, setEstado] = useState<EstadoSegundoFactor | null>(null);
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [errorGeneral, setErrorGeneral] = useState<string | null>(null);
    const [enviando, setEnviando] = useState(false);
    const [version, setVersion] = useState(0);

    const app = estado?.app ?? perfil.segundo_factor.app;
    const correo = estado?.correo ?? perfil.segundo_factor.correo;
    const qr = estado?.qr_svg ?? null;

    function aplicar(nuevo: EstadoSegundoFactor) {
        setEstado(nuevo);
        // El resto de la app (menú, perfil) ve el cambio sin recargar.
        cliente.setQueryData<Perfil>(['perfil'], (anterior) => anterior && { ...anterior, segundo_factor: { app: nuevo.app, correo: nuevo.correo } });
        void cliente.invalidateQueries({ queryKey: ['usuario'] });
    }

    async function ejecutar(accion: Accion, password: string) {
        setEnviando(true);
        setErrores({});
        setErrorGeneral(null);

        try {
            aplicar((await api.post<EstadoSegundoFactor>('/segundo-factor/configuracion', { accion, password })).data);
            setVersion((actual) => actual + 1);
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

    async function confirmar(evento: FormEvent<HTMLFormElement>) {
        evento.preventDefault();
        setEnviando(true);
        setErrores({});

        try {
            aplicar((await api.post<EstadoSegundoFactor>('/segundo-factor/configuracion/confirmar', { codigo: new FormData(evento.currentTarget).get('codigo') })).data);
        } catch (error) {
            const validacion = erroresDeValidacion(error);
            setErrores(Object.keys(validacion).length ? validacion : { codigo: [mensajeDeError(error)] });
        } finally {
            setEnviando(false);
        }
    }

    function enviarAccion(evento: FormEvent<HTMLFormElement>) {
        evento.preventDefault();
        const boton = (evento.nativeEvent as SubmitEvent).submitter as HTMLButtonElement | null;
        void ejecutar(boton?.value as Accion, String(new FormData(evento.currentTarget).get('password') ?? ''));
    }

    return (
        <section className="space-y-6">
            <header>
                <h2 className="font-display text-lg font-semibold text-white">{t('Verificación en dos pasos')}</h2>
                <p className="mt-1 text-sm text-slate-400">{t('Elige qué métodos podrás usar después de ingresar tu contraseña.')}</p>
            </header>

            {estado && !errorGeneral && <Aviso>{estado.mensaje}</Aviso>}
            {errorGeneral && <Aviso tipo="error">{errorGeneral}</Aviso>}

            {estado?.codigos_recuperacion && (
                <div className="rounded-2xl border border-amber-400/25 bg-amber-400/10 p-5">
                    <h3 className="font-medium text-amber-100">{t('Códigos de recuperación de un solo uso')}</h3>
                    <p className="mt-1 text-sm text-amber-100/80">
                        {t('Guárdalos ahora en un lugar seguro. No volverán a mostrarse y te permitirán entrar si pierdes acceso a tus métodos.')}
                    </p>
                    <ul className="mt-4 grid gap-1.5 font-mono text-sm text-white sm:grid-cols-2">
                        {estado.codigos_recuperacion.map((codigo) => (
                            <li key={codigo}>{codigo}</li>
                        ))}
                    </ul>
                    <BotonSecundario className="mt-4 !py-2" onClick={() => void navigator.clipboard?.writeText(estado.codigos_recuperacion!.join('\n'))}>
                        {t('Copiar códigos')}
                    </BotonSecundario>
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2">
                <Metodo titulo={t('App autenticadora')} activo={app}>
                    {t('Google Authenticator, Microsoft Authenticator u otra app compatible. Después de tu contraseña, usarás el código de la app para entrar.')}
                </Metodo>
                <Metodo titulo={t('Código por correo')} activo={correo}>
                    Te enviamos un código a {perfil.email}.
                    {!perfil.envio_correo_disponible && <span className="mt-1 block text-slate-500">{t('El envío de correo todavía no está configurado.')}</span>}
                    {perfil.envio_correo_disponible && !perfil.correo_verificado && <span className="mt-1 block text-amber-300">{t('Verifica tu correo para poder activarlo.')}</span>}
                </Metodo>
            </div>

            {qr && (
                <div className="rounded-2xl bg-white/[0.03] p-5 ring-1 ring-white/10">
                    <p className="text-sm text-slate-300">{t('Escanea el QR con tu app autenticadora e ingresa el código de seis dígitos que aparece en la app.')}</p>
                    <div className="mt-4 flex flex-col gap-5 sm:flex-row sm:items-start">
                        <div className="inline-block shrink-0 rounded-2xl bg-blanco p-4" role="img" aria-label={t('QR para configurar la app autenticadora')} dangerouslySetInnerHTML={{ __html: qr }} />
                        <form onSubmit={confirmar} className="flex-1 space-y-3" noValidate>
                            {estado?.clave_manual && (
                                <p className="text-xs text-slate-500">
                                    ¿No puedes escanear? Escribe esta clave en la app: <span className="break-all font-mono text-slate-300">{estado.clave_manual}</span>
                                </p>
                            )}
                            <Campo
                                id="codigo"
                                etiqueta={t('Código de la app')}
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                maxLength={6}
                                className="font-mono tracking-[0.4em]"
                                errores={errores.codigo}
                            />
                            <BotonPrimario disabled={enviando}>{t('Activar app')}</BotonPrimario>
                        </form>
                    </div>
                </div>
            )}

            <form key={version} onSubmit={enviarAccion} className="space-y-4" noValidate>
                <CampoContrasena id="password" etiqueta={t('Contraseña actual (para confirmar cualquier cambio)')} autoComplete="current-password" errores={errores.password} />
                <ErrorCampo mensajes={errores.accion} />

                <div className="flex flex-wrap gap-2">
                    {app ? (
                        <BotonSecundario type="submit" value="desactivar-app" disabled={enviando}>
                            {t('Desactivar app')}
                        </BotonSecundario>
                    ) : (
                        <BotonSecundario type="submit" value="preparar-app" disabled={enviando}>
                            {qr ? t('Generar otro QR') : t('Configurar app con QR')}
                        </BotonSecundario>
                    )}

                    {correo ? (
                        <BotonSecundario type="submit" value="desactivar-correo" disabled={enviando}>
                            {t('Desactivar correo')}
                        </BotonSecundario>
                    ) : (
                        perfil.correo_verificado &&
                        perfil.envio_correo_disponible && (
                            <BotonSecundario type="submit" value="activar-correo" disabled={enviando}>
                                {t('Activar correo')}
                            </BotonSecundario>
                        )
                    )}

                    {(app || correo) && (
                        <BotonSecundario type="submit" value="recuperacion" disabled={enviando}>
                            {t('Renovar códigos de recuperación')}
                        </BotonSecundario>
                    )}
                </div>
            </form>
        </section>
    );
}

function Metodo({ titulo, activo, children }: { titulo: string; activo: boolean; children: ReactNode }) {
    const { t } = useTranslation();
    return (
        <div className={`rounded-2xl p-4 ring-1 ${activo ? 'bg-emerald-400/10 ring-emerald-400/25' : 'bg-white/[0.02] ring-white/10'}`}>
            <div className="flex items-center justify-between gap-3">
                <h3 className="font-medium text-white">{titulo}</h3>
                <span className={`chip ${activo ? 'border-emerald-400/30 bg-emerald-400/15 text-emerald-200' : 'border-white/10 bg-white/5 text-slate-400'}`}>
                    <span className={`punto ${activo ? 'bg-emerald-400' : 'bg-slate-500'}`} />
                    {activo ? t('Activa') : t('Desactivada')}
                </span>
            </div>
            <p className="mt-2 text-sm text-slate-400">{children}</p>
        </div>
    );
}
