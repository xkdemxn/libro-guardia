import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import i18n from '@/i18n';
import { App } from '@/App';
import 'leaflet/dist/leaflet.css';
import '@/estilos.css';

const cliente = new QueryClient({
    defaultOptions: {
        queries: { refetchOnWindowFocus: false },
    },
});

// Los textos que manda la API (mensajes, «hace 3 días», continentes…) vienen en el idioma pedido:
// al cambiarlo, se vuelven a pedir.
i18n.on('languageChanged', () => void cliente.invalidateQueries());

createRoot(document.getElementById('raiz')!).render(
    <StrictMode>
        <QueryClientProvider client={cliente}>
            <BrowserRouter>
                <App />
            </BrowserRouter>
        </QueryClientProvider>
    </StrictMode>,
);
