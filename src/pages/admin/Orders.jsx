import { useState, useEffect } from 'react';
import api from '../../services/api';
import { ShoppingBag, Search, CheckCircle, XCircle, Clock } from 'lucide-react';

const AdminOrders = () => {
    const [orders, setOrders] = useState([]);
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchOrders();
    }, []);

    const fetchOrders = async () => {
        try {
            const res = await api.get('/admin/dashboard/todos/compras');
            setOrders(res.data);
            setLoading(false);
        } catch (err) {
            console.error(err);
            setLoading(false);
        }
    };

    const getStatusIcon = (status) => {
        switch (status?.toLowerCase()) {
            case 'pago':
            case 'aprovado':
                return <CheckCircle size={18} className="text-brand-green" />;
            case 'pendente':
                return <Clock size={18} className="text-amber-500" />;
            default:
                return <XCircle size={18} className="text-brand-red" />;
        }
    };

    return (
        <div className="space-y-8">
            <div>
                <h1 className="text-3xl font-black italic uppercase">GERENCIAR PEDIDOS</h1>
                <p className="text-gray-400 text-sm">Monitore todas as transações em tempo real.</p>
            </div>

            <div className="premium-card p-0 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-white/5 border-b border-glass-border">
                                <th className="px-6 py-4 text-xs font-black uppercase text-gray-400">ID</th>
                                <th className="px-6 py-4 text-xs font-black uppercase text-gray-400">Cliente</th>
                                <th className="px-6 py-4 text-xs font-black uppercase text-gray-400">Rifa</th>
                                <th className="px-6 py-4 text-xs font-black uppercase text-gray-400">Valor</th>
                                <th className="px-6 py-4 text-xs font-black uppercase text-gray-400 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-glass-border">
                            {loading ? (
                                Array(5).fill(0).map((_, i) => (
                                    <tr key={i} className="animate-pulse">
                                        <td colSpan="5" className="px-6 py-8 bg-white/5" />
                                    </tr>
                                ))
                            ) : orders.map((order) => (
                                <tr key={order.id} className="hover:bg-white/5 transition-colors">
                                    <td className="px-6 py-4 font-bold text-gray-500">#{order.id}</td>
                                    <td className="px-6 py-4 uppercase font-bold text-sm">{order.client?.name || 'Cliente'}</td>
                                    <td className="px-6 py-4 uppercase text-xs font-bold text-gray-400">{order.rifa?.nome || 'Sorteio'}</td>
                                    <td className="px-6 py-4 font-black">R$ {order.total}</td>
                                    <td className="px-6 py-4 text-right">
                                        <div className="flex items-center justify-end gap-2 font-bold text-xs uppercase">
                                            {getStatusIcon(order.status)}
                                            {order.status}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default AdminOrders;
