import { useEffect, useState, type FormEvent } from 'react';
import { Link, Navigate, useLocation, useNavigate } from 'react-router';
import { useQuery } from '@tanstack/react-query';
import { Ceja } from '@/componentes/Ceja';
import { Aviso, BotonPrimario, BotonSecundario, Campo, Etiqueta } from '@/componentes/Formulario';
import { Cargando } from '@/componentes/Cargando';
import { useFijarUsuario } from '@/hooks/useUsuario';
import { LayoutInvitado } from '@/layouts/LayoutInvitado';
import { api, erroresDeValidacion, esEstado, mensajeDeError, type ErroresValidacion } from '@/lib/api';
import type { MetodosSegundoFactor, Usuario } from '@/lib/tipos';
import type { EstadoNavegacion } from '@/paginas/auth/IniciarSesion';
import { useTranslation } from 'react-i18next';

type Metodo = 'app' | 'correo' | 'recuperacion';

/** Segundo paso del inicio de sesión: código de la app, del correo o de recuperación. */
export function DesafioSegundoFactor() {
    const { t } = useTranslation();
    const navegar = useNavigate();
    const { state } = useLocation() as { state: (EstadoNavegacion & { metodos?: MetodosSegundoFactor }) | null };
    const fijarUsuario = useFijarUsuario();

    const desafio = useQuery({
        queryKey: ['segundo-factor'],
        queryFn: async () => (await api.get<{ metodos: MetodosSegundoFactor }>('/segundo-factor/desafio')).data.metodos,
        initialData: state?.metodos,
        staleTime: 0,
        retry: false,
    });

    const [metodo, setMetodo] = useState<Metodo>('recuperacion');
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [estado, setEstado] = useState<string | null>(null);
    const [enviando, setEnviando] = useState(false);

    const metodos = desafio.data;

    // Método por defecto: la app si la tiene; si no, el correo cuando ya se envió el código.
    useEffect(() => {
        if (metodos) {
            setMetodo(metodos.app ? 'app' : metodos.correo && metodos.correo_enviado ? 'correo' : 'recuperacion');
        }
    }, [metodos?.app, metodos?.correo, metodos?.correo_enviado]);

    if (desafio.isError && esEstado(desafio.error, 404)) {
        return <Navigate to="/iniciar-sesion" replace state={{ estado: t('El inicio de sesión venció. Vuelve a ingresar tu contraseña.') }} />;
    }

    if (!metodos) {
        return <Cargando />;
    }

    async function pedirCodigoCorreo() {
        setErrores({});
        setEstado(null);

        try {
            const { data } = await api.post<{ mensaje: string }>('/segundo-factor/desafio/correo');
            setEstado(data.mensaje);
            setMetodo('correo');
            await desafio.refetch();
        } catch (error) {
            const validacion = erroresDeValidacion(error);
            setErrores(Object.keys(validacion).length ? validacion : { codigo: [mensajeDeError(error)] });
        }
    }

    async function verificar(evento: FormEvent<HTMLFormElement>) {
        evento.preventDefault();
        const codigo = new FormData(evento.currentTarget).get('codigo');
        setEnviando(true);
        setErrores({});

        try {
            const { data } = await api.post<{ data: Usuario }>('/segundo-factor/desafio', { metodo, codigo });
            fijarUsuario(data.data);
            navegar(state?.desde ?? '/contactos', { replace: true });
        } catch (error) {
            if (esEstado(error, 404)) {
                navegar('/iniciar-sesion', { replace: true, state: { estado: t('El inicio de sesión venció. Vuelve a ingresar tu contraseña.') } });

                return;
            }
            const validacion = erroresDeValidacion(error);
            setErrores(Object.keys(validacion).length ? validacion : { codigo: [mensajeDeError(error)] });
        } finally {
            setEnviando(false);
        }
    }

    const opciones: { valor: Metodo; texto: string; visible: boolean }[] = [
        { valor: 'app', texto: t('App autenticadora'), visible: metodos.app },
        { valor: 'correo', texto: t('Código por correo'), visible: metodos.correo && metodos.correo_enviado },
        { valor: 'recuperacion', texto: t('Código de recuperación'), visible: true },
    ];

    return (
        <LayoutInvitado>
            <Ceja className="mb-6">{t('Verificación en dos pasos')}</Ceja>
            <h2 className="font-display text-2xl font-semibold text-white">{t('Confirma que eres tú')}</h2>
            <p className="mt-1.5 text-sm text-slate-400">{t('Confirma tu identidad para acceder a tu bitácora.')}</p>

            {estado && <Aviso className="mt-6">{estado}</Aviso>}

            {metodos.correo && (
                <BotonSecundario className="mt-6 w-full" onClick={pedirCodigoCorreo} disabled={!metodos.envio_correo_disponible}>
                    {metodos.correo_enviado ? t('Reenviar código por correo') : t('Enviar un código a mi correo')}
                </BotonSecundario>
            )}

            <form onSubmit={verificar} className="mt-6 space-y-5" noValidate>
                <div>
                    <Etiqueta htmlFor="metodo">{t('Método')}</Etiqueta>
                    <select id="metodo" className="campo" value={metodo} onChange={(evento) => setMetodo(evento.target.value as Metodo)}>
                        {opciones
                            .filter((opcion) => opcion.visible)
                            .map((opcion) => (
                                <option key={opcion.valor} value={opcion.valor}>
                                    {opcion.texto}
                                </option>
                            ))}
                    </select>
                </div>

                <Campo
                    id="codigo"
                    etiqueta={t('Código')}
                    className="font-mono tracking-widest"
                    autoComplete="one-time-code"
                    inputMode={metodo === 'recuperacion' ? 'text' : 'numeric'}
                    maxLength={40}
                    required
                    autoFocus
                    errores={errores.codigo ?? errores.metodo}
                />

                <BotonPrimario className="w-full !py-3" disabled={enviando}>
                    {enviando ? t('Verificando…') : t('Verificar')}
                </BotonPrimario>
            </form>

            <p className="mt-6 text-center text-sm">
                <Link to="/iniciar-sesion" className="text-acento-300 transition hover:text-acento-200">
                    {t('Volver al inicio de sesión')}
                </Link>
            </p>
        </LayoutInvitado>
    );
}
