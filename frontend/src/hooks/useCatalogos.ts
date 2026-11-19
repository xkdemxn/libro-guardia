import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import type { Catalogos } from '@/lib/tipos';

/** Bandas y modos: casi nunca cambian, se piden una vez. */
export function useCatalogos() {
    return useQuery({
        queryKey: ['catalogos'],
        queryFn: async () => (await api.get<Catalogos>('/catalogos')).data,
        staleTime: 60 * 60 * 1000,
    });
}
