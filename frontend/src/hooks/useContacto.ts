import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import type { ContactoDetalle } from '@/lib/tipos';

export function useContacto(id: string | undefined) {
    return useQuery({
        queryKey: ['contacto', id],
        queryFn: async () => (await api.get<{ data: ContactoDetalle }>(`/contactos/${id}`)).data.data,
        enabled: Boolean(id),
        retry: false,
    });
}
