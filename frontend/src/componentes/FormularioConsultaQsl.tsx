import { useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router';
import { BotonPrimario, Etiqueta } from '@/componentes/Formulario';

/** Número de QSO (impreso al pie de la tarjeta) + indicativo de quien la recibió. */
export function FormularioConsultaQsl({ qso = '', indicativo = '', compacto = false }: { qso?: string; indicativo?: string; compacto?: boolean }) {
    const navegar = useNavigate();
    const [numero, setNumero] = useState(qso);
    const [miIndicativo, setMiIndicativo] = useState(indicativo);

    function consultar(evento: FormEvent) {
        evento.preventDefault();
        const parametros = new URLSearchParams({ qso: numero.replace(/\D/g, ''), indicativo: miIndicativo.replace(/\s+/g, '').toUpperCase() });
        navegar(`/qsl?${parametros.toString()}`);
    }

    return (
        <form onSubmit={consultar} className={compacto ? 'space-y-3' : 'space-y-4'}>
            <div className="grid grid-cols-[minmax(0,2fr)_minmax(0,3fr)] gap-3">
                <div>
                    <Etiqueta htmlFor="consulta-qso">N.º de QSO</Etiqueta>
                    <div className="relative">
                        <span className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 font-mono text-slate-500">#</span>
                        <input
                            id="consulta-qso"
                            inputMode="numeric"
                            required
                            placeholder="61"
                            value={numero}
                            onChange={(evento) => setNumero(evento.target.value)}
                            className="campo !pl-8 font-mono"
                        />
                    </div>
                </div>
                <div>
                    <Etiqueta htmlFor="consulta-indicativo">Tu indicativo</Etiqueta>
                    <input
                        id="consulta-indicativo"
                        required
                        maxLength={20}
                        placeholder="LU4BB"
                        value={miIndicativo}
                        onChange={(evento) => setMiIndicativo(evento.target.value)}
                        className="campo font-mono uppercase"
                    />
                </div>
            </div>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-xs text-slate-500">El número va al pie de la tarjeta: «QSO #61».</p>
                <BotonPrimario className={compacto ? '!py-2' : ''}>Consultar</BotonPrimario>
            </div>
        </form>
    );
}
