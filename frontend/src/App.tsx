import { lazy, Suspense } from 'react';
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
import { NoEncontrada } from '@/paginas/NoEncontrada';

// Cada sección se descarga al abrirla por primera vez: la carga inicial queda liviana.
const DetalleContacto = lazy(() => import('@/paginas/contactos/DetalleContacto').then((modulo) => ({ default: modulo.DetalleContacto })));
const EditarContacto = lazy(() => import('@/paginas/contactos/EditarContacto').then((modulo) => ({ default: modulo.EditarContacto })));
const Exportar = lazy(() => import('@/paginas/contactos/Exportar').then((modulo) => ({ default: modulo.Exportar })));
const Importar = lazy(() => import('@/paginas/contactos/Importar').then((modulo) => ({ default: modulo.Importar })));
const Libro = lazy(() => import('@/paginas/contactos/Libro').then((modulo) => ({ default: modulo.Libro })));
const NuevoContacto = lazy(() => import('@/paginas/contactos/NuevoContacto').then((modulo) => ({ default: modulo.NuevoContacto })));
const TarjetaQsl = lazy(() => import('@/paginas/contactos/TarjetaQsl').then((modulo) => ({ default: modulo.TarjetaQsl })));
const Estadisticas = lazy(() => import('@/paginas/Estadisticas').then((modulo) => ({ default: modulo.Estadisticas })));
const MiPerfil = lazy(() => import('@/paginas/perfil/MiPerfil').then((modulo) => ({ default: modulo.MiPerfil })));
const BuscarIndicativo = lazy(() => import('@/paginas/publico/BuscarIndicativo').then((modulo) => ({ default: modulo.BuscarIndicativo })));
const PerfilPublico = lazy(() => import('@/paginas/publico/PerfilPublico').then((modulo) => ({ default: modulo.PerfilPublico })));
const Solicitudes = lazy(() => import('@/paginas/Solicitudes').then((modulo) => ({ default: modulo.Solicitudes })));

export function App() {
    return (
        <Suspense fallback={<Cargando />}>
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
        </Suspense>
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
