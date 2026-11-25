import { EncabezadoContacto, FormularioContacto, valoresIniciales } from '@/paginas/contactos/FormularioContacto';
import { useTranslation } from 'react-i18next';

export function NuevoContacto() {
    const { t } = useTranslation();
    return (
        <>
            <EncabezadoContacto titulo={t('Nuevo contacto')} detalle={t('Anota el QSO mientras ocurre: el botón «Ahora» pone la hora UTC.')} volverA="/contactos" />
            <div className="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                <FormularioContacto inicial={valoresIniciales()} textoBoton={t('Guardar contacto')} />
            </div>
        </>
    );
}
