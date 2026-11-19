import { useQuery, useQueryClient } from '@tanstack/react-query';
import { api, esEstado } from '@/lib/api';
import type { Usuario } from '@/lib/tipos';

export const CLAVE_USUARIO = ['usuario'] as const;

/**
 * El operador con sesión iniciada, o null si no hay sesión (la API responde 401).
 */
export function useUsuario() {
    return useQuery({
        queryKey: CLAVE_USUARIO,
        queryFn: async (): Promise<Usuario | null> => {
            try {
                const { data } = await api.get<{ data: Usuario }>('/usuario');

                return data.data;
            } catch (error) {
                if (esEstado(error, 401)) {
                    return null;
                }

                throw error;
            }
        },
        staleTime: 5 * 60 * 1000,
        retry: false,
    });
}

/** Guarda el operador recién autenticado (o null al cerrar sesión) sin volver a pedirlo. */
export function useFijarUsuario() {
    const cliente = useQueryClient();

    return (usuario: Usuario | null) => cliente.setQueryData(CLAVE_USUARIO, usuario);
}
