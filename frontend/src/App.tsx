import { Navigate, Outlet, Route, Routes, useLocation } from 'react-router';
import { Cargando } from '@/componentes/Cargando';
import { useUsuario } from '@/hooks/useUsuario';
import { LayoutApp } from '@/layouts/LayoutApp';
import { LayoutPublico } from '@/layouts/LayoutPublico';
import { DesafioSegundoFactor } from '@/paginas/auth/DesafioSegundoFactor';
import { IniciarSesion } from '@/paginas/auth/IniciarSesion';
import { OlvideContrasena } from '@/paginas/auth/OlvideContrasena';
import { Registro } from '@/paginas/auth/Registro';
import { RestablecerContrasena } from '@/paginas/auth/RestablecerContrasena';
import { DetalleContacto } from '@/paginas/contactos/DetalleContacto';
import { EditarContacto } from '@/paginas/contactos/EditarContacto';
import { Exportar } from '@/paginas/contactos/Exportar';
import { Importar } from '@/paginas/contactos/Importar';
import { Libro } from '@/paginas/contactos/Libro';
import { NuevoContacto } from '@/paginas/contactos/NuevoContacto';
import { TarjetaQsl } from '@/paginas/contactos/TarjetaQsl';
import { Estadisticas } from '@/paginas/Estadisticas';
import { NoEncontrada } from '@/paginas/NoEncontrada';
import { MiPerfil } from '@/paginas/perfil/MiPerfil';
import { BuscarIndicativo } from '@/paginas/publico/BuscarIndicativo';
import { PerfilPublico } from '@/paginas/publico/PerfilPublico';
import { Solicitudes } from '@/paginas/Solicitudes';

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
                <Route path="indicativos" element={<BuscarIndicativo />} />
                <Route path="indicativos/:indicativo" element={<PerfilPublico />} />
            </Route>

            {/* Con sesión */}
            <Route element={<RequiereSesion />}>
                <Route path="contactos" element={<Libro />} />
                <Route path="contactos/nuevo" element={<NuevoContacto />} />
                <Route path="contactos/importar" element={<Importar />} />
                <Route path="contactos/exportar" element={<Exportar />} />
                <Route path="estadisticas" element={<Estadisticas />} />
                <Route path="solicitudes" element={<Solicitudes />} />
                <Route path="contactos/:id" element={<DetalleContacto />} />
                <Route path="contactos/:id/editar" element={<EditarContacto />} />
                <Route path="contactos/:id/tarjeta" element={<TarjetaQsl />} />
                <Route path="perfil" element={<MiPerfil />} />
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

    return <LayoutPublico />;
}
