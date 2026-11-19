import { EncabezadoContacto, FormularioContacto, valoresIniciales } from '@/paginas/contactos/FormularioContacto';

export function NuevoContacto() {
    return (
        <>
            <EncabezadoContacto titulo="Nuevo contacto" detalle="Anota el QSO mientras ocurre: el botón «Ahora» pone la hora UTC." volverA="/contactos" />
            <div className="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                <FormularioContacto inicial={valoresIniciales()} textoBoton="Guardar contacto" />
            </div>
        </>
    );
}
