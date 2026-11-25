import { ErrorCampo, Etiqueta } from '@/componentes/Formulario';
import type { ErroresValidacion } from '@/lib/api';
import type { Ubicaciones } from '@/lib/tipos';
import { useTranslation } from 'react-i18next';

export const esEcuador = (pais: string | null | undefined): boolean => ['Ecuador', 'Islas Galápagos'].includes(pais ?? '');

interface Props {
    ubicaciones: Ubicaciones | undefined;
    paisPrefijo: string | null;
    valores: { pais: string; provincia: string; ciudad: string };
    /** Ciudad guardada antes de que existieran las listas: se sigue ofreciendo. */
    ciudadGuardada: string | null;
    paisGuardado: string | null;
    onCambiar: (valores: { pais: string; provincia: string; ciudad: string }) => void;
    errores: ErroresValidacion;
}

/**
 * País, provincia (solo Ecuador) y ciudad como desplegables. Ecuador sin provincia elegida
 * muestra los cantones agrupados por provincia.
 */
export function Ubicacion({ ubicaciones, paisPrefijo, valores, ciudadGuardada, paisGuardado, onCambiar, errores }: Props) {
    const { t } = useTranslation();
    const paisEfectivo = valores.pais || paisPrefijo;
    const ecuador = esEcuador(paisEfectivo);

    const grupos: [string, string[]][] = !ubicaciones
        ? []
        : ecuador
          ? valores.provincia
              ? [['', ubicaciones.ecuador[valores.provincia] ?? []]]
              : Object.entries(ubicaciones.ecuador)
          : [['', ubicaciones.ciudades[paisEfectivo ?? ''] ?? []]];

    const todasLasCiudades = grupos.flatMap(([, ciudades]) => ciudades);
    const ciudadFueraDeLista = valores.ciudad && !todasLasCiudades.includes(valores.ciudad) && valores.ciudad === ciudadGuardada;
    const paisFueraDeLista = valores.pais && ubicaciones && !ubicaciones.paises.includes(valores.pais) && valores.pais === paisGuardado;

    return (
        <div className="grid grid-cols-1 gap-5 sm:col-span-2 sm:grid-cols-3">
            <div>
                <Etiqueta htmlFor="pais">{t('País')}</Etiqueta>
                <select id="pais" className="campo" value={valores.pais} onChange={(evento) => onCambiar({ pais: evento.target.value, provincia: '', ciudad: '' })}>
                    <option value="">{paisPrefijo ? t('{{pais}} (automático)', { pais: paisPrefijo }) : t('Elige un país')}</option>
                    {paisFueraDeLista && <option value={valores.pais}>{valores.pais}</option>}
                    {ubicaciones?.paises.map((pais) => (
                        <option key={pais} value={pais}>
                            {pais}
                        </option>
                    ))}
                </select>
                <ErrorCampo mensajes={errores.pais} />
            </div>

            {ecuador && (
                <div>
                    <Etiqueta htmlFor="provincia">{t('Provincia')}</Etiqueta>
                    <select
                        id="provincia"
                        className="campo"
                        value={valores.provincia}
                        onChange={(evento) => onCambiar({ ...valores, provincia: evento.target.value, ciudad: '' })}
                    >
                        <option value="">{t('Elige una provincia')}</option>
                        {ubicaciones?.provincias.map((provincia) => (
                            <option key={provincia} value={provincia}>
                                {provincia}
                            </option>
                        ))}
                    </select>
                    <ErrorCampo mensajes={errores.provincia} />
                </div>
            )}

            <div>
                <Etiqueta htmlFor="ciudad">{ecuador ? t('Ciudad o cantón') : t('Ciudad')}</Etiqueta>
                <select id="ciudad" className="campo" value={valores.ciudad} onChange={(evento) => onCambiar({ ...valores, ciudad: evento.target.value })}>
                    <option value="">{t('Elige una ciudad')}</option>
                    {ciudadFueraDeLista && <option value={valores.ciudad}>{valores.ciudad}</option>}
                    {grupos.map(([grupo, ciudades]) =>
                        grupo === '' ? (
                            ciudades.map((ciudad) => (
                                <option key={ciudad} value={ciudad}>
                                    {ciudad}
                                </option>
                            ))
                        ) : (
                            <optgroup key={grupo} label={grupo}>
                                {ciudades.map((ciudad) => (
                                    <option key={ciudad} value={ciudad}>
                                        {ciudad}
                                    </option>
                                ))}
                            </optgroup>
                        ),
                    )}
                </select>
                <ErrorCampo mensajes={errores.ciudad} />
            </div>
        </div>
    );
}
