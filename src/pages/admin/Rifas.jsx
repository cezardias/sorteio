import { useState, useEffect } from 'react';
import api from '../../services/api';
import { Ticket, Plus, Edit2, Trash2 } from 'lucide-react';

const AdminRifas = () => {
    const [rifas, setRifas] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchRifas();
    }, []);

    const fetchRifas = async () => {
        try {
            const res = await api.get('/produtos');
            setRifas(res.data);
            setLoading(false);
        } catch (err) {
            console.error(err);
            setLoading(false);
        }
    };

    return (
        <div className="space-y-8">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-black italic uppercase">SEUS SORTEIOS</h1>
                    <p className="text-gray-400 text-sm">Crie e gerencie suas campanhas de rifas.</p>
                </div>
                <button className="btn-success flex items-center gap-2">
                    <Plus size={20} />
                    NOVO SORTEIO
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {loading ? (
                    Array(3).fill(0).map((_, i) => (
                        <div key={i} className="premium-card h-64 animate-pulse bg-white/5" />
                    ))
                ) : rifas.map((rifa) => (
                    <div key={rifa.id} className="premium-card p-0 overflow-hidden flex flex-col">
                        <div className="h-32 bg-brand-gray relative">
                            <img src={rifa.image_url} alt="" className="w-full h-full object-cover opacity-50" />
                            <div className="absolute inset-0 bg-gradient-to-t from-brand-dark to-transparent" />
                            <div className="absolute bottom-4 left-4">
                                <h3 className="font-black uppercase text-sm">{rifa.nome}</h3>
                            </div>
                        </div>

                        <div className="p-6 flex-grow space-y-4">
                            <div className="flex justify-between items-center text-xs font-bold text-gray-500">
                                <span>PROGRESSO</span>
                                <span>{rifa.porcentagem}%</span>
                            </div>
                            <div className="h-1.5 bg-brand-gray rounded-full overflow-hidden">
                                <div className="h-full bg-brand-red" style={{ width: `${rifa.porcentagem}%` }} />
                            </div>

                            <div className="flex items-center justify-between pt-2">
                                <div className="text-sm font-bold">R$ {rifa.preco_cota} <span className="text-gray-500 font-normal">/cota</span></div>
                                <div className="flex gap-2">
                                    <button className="p-2 hover:bg-white/10 rounded-lg text-brand-red transition-colors"><Edit2 size={16} /></button>
                                    <button className="p-2 hover:bg-white/10 rounded-lg text-gray-400 transition-colors"><Trash2 size={16} /></button>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default AdminRifas;
