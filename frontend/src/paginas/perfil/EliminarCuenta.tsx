import { useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router';
import { useQueryClient } from '@tanstack/react-query';
import { CampoContrasena } from '@/componentes/CampoContrasena';
import { Dialogo } from '@/componentes/Dialogo';
import { BotonSecundario } from '@/componentes/Formulario';
import { CLAVE_USUARIO } from '@/hooks/useUsuario';
import { api, erroresDeValidacion, type ErroresValidacion } from '@/lib/api';

const botonPeligro =
    'inline-flex items-center justify-center gap-2 rounded-full bg-rose-500 px-5 py-2.5 text-sm font-semibold text-blanco transition hover:bg-rose-400 active:scale-[0.97] focus:outline-none focus-visible:ring-4 focus-visible:ring-rose-400/30 disabled:opacity-50';

export function EliminarCuenta() {
    const [abierto, setAbierto] = useState(false);
    const [errores, setErrores] = useState<ErroresValidacion>({});
    const [enviando, setEnviando] = useState(false);
    const cliente = useQueryClient();
    const navegar = useNavigate();

    async function eliminar(evento: FormEvent<HTMLFormElement>) {
        evento.preventDefault();
        setEnviando(true);
        setErrores({});

        try {
            await api.delete('/perfil', { data: { password: new FormData(evento.currentTarget).get('password') } });
            cliente.clear();
            cliente.setQueryData(CLAVE_USUARIO, null);
            navegar('/iniciar-sesion', { replace: true, state: { estado: 'Tu cuenta y tu libro de guardia se eliminaron.' } });
        } catch (error) {
            setErrores(erroresDeValidacion(error));
        } finally {
            setEnviando(false);
        }
    }

    return (
        <section className="space-y-6">
            <header>
                <h2 className="font-display text-lg font-semibold text-white">Eliminar cuenta</h2>
                <p className="mt-1 text-sm text-slate-400">
                    Al eliminar tu cuenta se borran para siempre tus datos y todos los contactos de tu libro de guardia. Si quieres conservarlos, expórtalos antes.
                </p>
            </header>

            <button type="button" className={botonPeligro} onClick={() => setAbierto(true)}>
                Eliminar cuenta
            </button>

            <Dialogo abierto={abierto} onCerrar={() => setAbierto(false)} titulo="¿Seguro que quieres eliminar tu cuenta?">
                <form onSubmit={eliminar} noValidate>
                    <p className="mt-1 text-sm text-slate-400">Esta acción no se puede deshacer. Escribe tu contraseña para confirmar.</p>
                    <div className="mt-6">
                        <CampoContrasena id="password" etiqueta="Contraseña" autoComplete="current-password" errores={errores.password} />
                    </div>
                    <div className="mt-6 flex justify-end gap-3">
                        <BotonSecundario onClick={() => setAbierto(false)}>Cancelar</BotonSecundario>
                        <button type="submit" className={botonPeligro} disabled={enviando}>
                            {enviando ? 'Eliminando…' : 'Eliminar para siempre'}
                        </button>
                    </div>
                </form>
            </Dialogo>
        </section>
    );
}
