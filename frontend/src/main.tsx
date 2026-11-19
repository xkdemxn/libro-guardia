import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { App } from '@/App';
import 'leaflet/dist/leaflet.css';
import '@/estilos.css';

const cliente = new QueryClient({
    defaultOptions: {
        queries: { refetchOnWindowFocus: false },
    },
});

createRoot(document.getElementById('raiz')!).render(
    <StrictMode>
        <QueryClientProvider client={cliente}>
            <BrowserRouter>
                <App />
            </BrowserRouter>
        </QueryClientProvider>
    </StrictMode>,
);
