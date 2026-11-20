import { Link } from 'react-router';
import { Ceja } from '@/componentes/Ceja';
import { useTranslation } from 'react-i18next';

export function NoEncontrada() {
    const { t } = useTranslation();
    return (
        <div className="mx-auto grid min-h-screen max-w-md place-items-center px-4">
            <div className="fondo-noche" aria-hidden="true">
                <span />
            </div>
            <div className="vidrio w-full p-8 text-center">
                <Ceja className="mb-6">{t('Error 404')}</Ceja>
                <h1 className="font-display text-2xl font-semibold text-white">{t('Página no encontrada')}</h1>
                <p className="mt-3 text-sm text-slate-400">{t('La dirección no existe o ya no está disponible.')}</p>
                <Link to="/" className="mt-6 inline-block text-sm font-medium text-acento-300 hover:text-acento-200">
                    Volver al inicio &rsaquo;
                </Link>
            </div>
        </div>
    );
}
