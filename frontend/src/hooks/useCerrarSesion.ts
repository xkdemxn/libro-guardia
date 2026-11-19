import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router';
import { api } from '@/lib/api';
import { CLAVE_USUARIO } from '@/hooks/useUsuario';

export function useCerrarSesion() {
    const cliente = useQueryClient();
    const navegar = useNavigate();

    return useMutation({
        mutationFn: () => api.delete('/sesion'),
        onSuccess: () => {
            // Nada de los datos del operador debe quedar en memoria después de salir.
            cliente.clear();
            cliente.setQueryData(CLAVE_USUARIO, null);
            navegar('/iniciar-sesion', { replace: true });
        },
    });
}
