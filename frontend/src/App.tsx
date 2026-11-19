import { Navigate, Outlet, Route, Routes, useLocation } from 'react-router';
import { Cargando } from '@/componentes/Cargando';
import { useUsuario } from '@/hooks/useUsuario';
import { LayoutApp } from '@/layouts/LayoutApp';
import { DesafioSegundoFactor } from '@/paginas/auth/DesafioSegundoFactor';
import { IniciarSesion } from '@/paginas/auth/IniciarSesion';
import { OlvideContrasena } from '@/paginas/auth/OlvideContrasena';
import { Registro } from '@/paginas/auth/Registro';
import { RestablecerContrasena } from '@/paginas/auth/RestablecerContrasena';
import { EnConstruccion } from '@/paginas/EnConstruccion';
import { NoEncontrada } from '@/paginas/NoEncontrada';

export function App() {
    return (
        <Routes>
            <Route index element={<Inicio />} />

            {/* Sin sesión */}
            <Route element={<SoloInvitados />}>
                <Route path="iniciar-sesion" element={<IniciarSesion />} />
                <Route path="registro" element={<Registro />} />
                <Route path="segundo-factor" element={<DesafioSegundoFactor />} />
                <Route path="olvide-contrasena" element={<OlvideContrasena />} />
                <Route path="restablecer-contrasena/:token" element={<RestablecerContrasena />} />
            </Route>

            {/* Públicas: con o sin sesión (US-15) */}
            <Route element={<LayoutSegunSesion />}>
                <Route path="indicativos" element={<EnConstruccion titulo="Directorio de indicativos" rutaBlade="/indicativos" />} />
                <Route path="indicativos/:indicativo" element={<EnConstruccion titulo="Perfil público" rutaBlade="/indicativos" />} />
            </Route>

            {/* Con sesión */}
            <Route element={<RequiereSesion />}>
                <Route path="contactos" element={<EnConstruccion titulo="Libro de guardia" rutaBlade="/contactos" />} />
                <Route path="contactos/nuevo" element={<EnConstruccion titulo="Nuevo contacto" rutaBlade="/contactos/create" />} />
                <Route path="contactos/importar" element={<EnConstruccion titulo="Importar ADIF" rutaBlade="/contactos/importar" />} />
                <Route path="contactos/exportar" element={<EnConstruccion titulo="Exportar" rutaBlade="/contactos/exportar" />} />
                <Route path="estadisticas" element={<EnConstruccion titulo="Estadísticas" rutaBlade="/estadisticas" />} />
                <Route path="solicitudes" element={<EnConstruccion titulo="Solicitudes de confirmación" rutaBlade="/solicitudes" />} />
                <Route path="perfil" element={<EnConstruccion titulo="Mi perfil" rutaBlade="/profile" />} />
            </Route>

            <Route path="*" element={<NoEncontrada />} />
        </Routes>
    );
}

function Inicio() {
    const { data: usuario, isPending } = useUsuario();

    if (isPending) {
        return <Cargando />;
    }

    return <Navigate to={usuario ? '/contactos' : '/iniciar-sesion'} replace />;
}

/** Sin sesión, guarda la página pedida para volver a ella después de iniciar sesión. */
function RequiereSesion() {
    const { data: usuario, isPending } = useUsuario();
    const ubicacion = useLocation();

    if (isPending) {
        return <Cargando />;
    }

    if (!usuario) {
        return <Navigate to="/iniciar-sesion" replace state={{ desde: ubicacion.pathname + ubicacion.search }} />;
    }

    return <LayoutApp usuario={usuario} />;
}

/** Con sesión no tiene sentido ver el formulario de inicio de sesión ni el de registro. */
function SoloInvitados() {
    const { data: usuario, isPending } = useUsuario();

    if (isPending) {
        return <Cargando />;
    }

    return usuario ? <Navigate to="/contactos" replace /> : <Outlet />;
}

function LayoutSegunSesion() {
    const { data: usuario, isPending } = useUsuario();

    if (isPending) {
        return <Cargando />;
    }

    if (usuario) {
        return <LayoutApp usuario={usuario} />;
    }

    return (
        <>
            <div className="fondo-noche" aria-hidden="true">
                <span />
            </div>
            <Outlet />
        </>
    );
}
