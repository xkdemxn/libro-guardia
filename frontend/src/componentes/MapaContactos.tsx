import { useEffect, useRef } from 'react';
import L from 'leaflet';
import { useTranslation } from 'react-i18next';
import { temaClaroActivo } from '@/lib/tema';
import type { Estadisticas } from '@/lib/tipos';

/** Mapa base de Esri (gratuito, sin clave): gris oscuro o gris claro según el tema. */
const urlMapaBase = (claro: boolean) =>
    `https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_${claro ? 'Light' : 'Dark'}_Gray_Base/MapServer/tile/{z}/{y}/{x}`;

const escapar = (texto: string) => texto.replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);

/** Mapa mundial de contactos (innovación 2): un punto por ubicación, más grande cuantos más contactos. */
export function MapaContactos({ origen, puntos }: Pick<Estadisticas, 'origen' | 'puntos'>) {
    const { t } = useTranslation();
    const contenedor = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!contenedor.current) {
            return;
        }

        const mapa = L.map(contenedor.current, { worldCopyJump: true, scrollWheelZoom: false }).setView(origen ? [origen.lat, origen.lon] : [10, -40], 2);
        const mapaBase = L.tileLayer(urlMapaBase(temaClaroActivo()), {
            attribution: 'Mapa base &copy; Esri, HERE, Garmin, &copy; OpenStreetMap',
            maxZoom: 10,
        }).addTo(mapa);
        const alCambiarTema = (evento: Event) => mapaBase.setUrl(urlMapaBase((evento as CustomEvent<{ claro: boolean }>).detail.claro));
        window.addEventListener('tema-cambiado', alCambiarTema);

        const limites: L.LatLngTuple[] = [];
        const maximo = Math.max(1, ...puntos.map((punto) => punto.cantidad));

        puntos.forEach((punto) => {
            if (origen) {
                L.polyline(
                    [
                        [origen.lat, origen.lon],
                        [punto.lat, punto.lon],
                    ],
                    { color: '#52adff', weight: 1, opacity: 0.35, interactive: false },
                ).addTo(mapa);
            }

            L.circleMarker([punto.lat, punto.lon], {
                radius: 5 + 9 * Math.sqrt(punto.cantidad / maximo),
                color: '#070e1c',
                weight: 2,
                fillColor: '#52adff',
                fillOpacity: 0.9,
            })
                .bindTooltip(
                    `<strong>${escapar(punto.lugar)}</strong><br>${punto.cantidad === 1 ? t('1 contacto') : t('{{n}} contactos', { n: punto.cantidad })}<br><span style="font-family:monospace">${escapar(punto.indicativos.join(', '))}</span>`,
                    { direction: 'top', offset: [0, -6] },
                )
                .addTo(mapa);

            limites.push([punto.lat, punto.lon]);
        });

        if (origen) {
            L.circleMarker([origen.lat, origen.lon], { radius: 7, color: '#070e1c', weight: 2, fillColor: '#ffb84a', fillOpacity: 1 })
                .bindTooltip(t('Mi estación'), { direction: 'top', offset: [0, -6] })
                .addTo(mapa);
            limites.push([origen.lat, origen.lon]);
        }

        if (limites.length > 1) {
            mapa.fitBounds(limites, { padding: [30, 30], maxZoom: 4 });
        }

        return () => {
            window.removeEventListener('tema-cambiado', alCambiarTema);
            mapa.remove();
        };
    }, [origen, puntos, t]);

    return (
        <div
            ref={contenedor}
            className="h-[26rem] w-full border-y border-white/5 bg-noche-950"
            role="img"
            aria-label={t('Mapa mundial con {{n}} ubicaciones contactadas', { n: puntos.length })}
        />
    );
}
