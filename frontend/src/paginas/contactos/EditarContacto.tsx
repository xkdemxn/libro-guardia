import { useParams } from 'react-router';
import { Cargando } from '@/componentes/Cargando';
import { useContacto } from '@/hooks/useContacto';
import { ContactoNoEncontrado } from '@/paginas/contactos/DetalleContacto';
import { EncabezadoContacto, FormularioContacto, valoresIniciales } from '@/paginas/contactos/FormularioContacto';
import { useTranslation } from 'react-i18next';

/** Corregir un contacto con las mismas validaciones del registro (US-07). */
export function EditarContacto() {
    const { t } = useTranslation();
    const { id } = useParams();
    const contacto = useContacto(id);

    if (contacto.isError) {
        return <ContactoNoEncontrado />;
    }

    if (!contacto.data) {
        return <Cargando />;
    }

    return (
        <>
            <EncabezadoContacto titulo={`Editar ${contacto.data.estacion_llamada}`} detalle={t('Corrige los datos del contacto. Se validan igual que al registrarlo.')} volverA={`/contactos/${contacto.data.id}`} />
            <div className="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                <FormularioContacto inicial={valoresIniciales(contacto.data)} fechaMinima={contacto.data.fecha_minima} idContacto={contacto.data.id} textoBoton={t('Guardar cambios')} />
            </div>
        </>
    );
}
