import { Link } from 'react-router';
import { useTranslation } from 'react-i18next';

/** Una sola línea al pie de todas las pantallas; lleva a «Sobre nosotros». */
export function PiePagina({ className = '' }: { className?: string }) {
    const { t } = useTranslation();
    return (
        <footer className={`py-6 text-center ${className}`}>
            <Link to="/sobre-nosotros" className="text-xs tracking-wide text-slate-500 transition hover:text-slate-300">
                {t('Radioaficionados DX Ecuador · 2026')}
            </Link>
        </footer>
    );
}
