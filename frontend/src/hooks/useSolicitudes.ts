import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import type { Solicitud } from '@/lib/tipos';

/** Solicitudes de confirmación pendientes (también alimenta el aviso de la barra superior). */
export function useSolicitudes() {
    return useQuery({
        queryKey: ['solicitudes'],
        queryFn: async () => (await api.get<{ data: Solicitud[] }>('/solicitudes')).data.data,
        staleTime: 60 * 1000,
    });
}
