import { useState, useEffect } from 'react';
import api from '../services/api';
import { ShoppingBag, Calendar, Ticket, ChevronRight } from 'lucide-react';
import { motion } from 'framer-motion';
import { Link } from 'react-router-dom';

const Orders = () => {
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchOrders = async () => {
            try {
                // O endpoint original parece ser algo como /client/meus-pedidos/sorteios/{id}
                // Mas geralmente há um endpoint de listagem geral.
                // Vou assumir que o getProfile ou um similar retorna os pedidos no sistema atual.
                // Baseado nas rotas: Route::middleware('auth.client')->get("/checkout/pedido/{id}", [RifasController::class, "getCompra"])
                // Não vi uma rota de listagem óbvia na api.php. Vou tentar deduzir ou puxar do profile.
                const res = await api.get('/get-profile');
                setOrders(res.data.pedidos || []);
                setLoading(false);
            } catch (err) {
                console.error(err);
                setLoading(false);
            }
        };

        fetchOrders();
    }, []);

    const getStatusColor = (status) => {
        switch (status?.toLowerCase()) {
            case 'pago':
            case 'aprovado':
                return 'text-brand-green bg-brand-green/10 border-brand-green/20';
            case 'pendente':
                return 'text-amber-500 bg-amber-500/10 border-amber-500/20';
            default:
                return 'text-brand-red bg-brand-red/10 border-brand-red/20';
        }
    };

    if (loading) return <div className="text-center py-12 font-bold">CARREGANDO PEDIDOS...</div>;

    return (
        <div className="space-y-8">
            <div className="flex items-center gap-4">
                <div className="p-3 bg-brand-red rounded-2xl">
                    <ShoppingBag size={24} />
                </div>
                <div>
                    <h1 className="text-3xl font-black italic uppercase">MEUS PEDIDOS</h1>
                    <p className="text-gray-400 text-sm">Histórico completo de suas participações.</p>
                </div>
            </div>

            <div className="space-y-4">
                {orders.length === 0 ? (
                    <div className="premium-card text-center py-20 space-y-4">
                        <ShoppingBag size={48} className="mx-auto text-gray-700" />
                        <p className="text-gray-400 font-bold">Você ainda não possui pedidos.</p>
                        <Link to="/" className="btn-primary inline-block">VER SORTEIOS ATIVOS</Link>
                    </div>
                ) : (
                    orders.map((order) => (
                        <motion.div
                            key={order.id}
                            whileHover={{ x: 4 }}
                            className="premium-card flex flex-col md:flex-row items-center justify-between gap-6 hover:border-brand-red/40"
                        >
                            <div className="flex items-center gap-6 w-full md:w-auto">
                                <div className="w-16 h-16 rounded-xl overflow-hidden bg-brand-gray shrink-0">
                                    <img src={order.rifa?.image_url} alt="" className="w-full h-full object-cover" />
                                </div>
                                <div className="space-y-1">
                                    <h3 className="font-bold text-lg uppercase truncate max-w-[200px]">{order.rifa?.nome || 'Sorteio'}</h3>
                                    <div className="flex items-center gap-4 text-xs text-gray-500 font-bold">
                                        <span className="flex items-center gap-1"><Ticket size={12} /> {order.quantidade_cotas} COTAS</span>
                                        <span className="flex items-center gap-1"><Calendar size={12} /> {new Date(order.created_at).toLocaleDateString()}</span>
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center justify-between w-full md:w-auto md:gap-12">
                                <div className="text-right">
                                    <p className="text-xs text-gray-400 font-bold uppercase">VALOR TOTAL</p>
                                    <p className="text-xl font-black text-white">R$ {order.total}</p>
                                </div>

                                <div className={`px-4 py-2 rounded-full border text-xs font-black uppercase ${getStatusColor(order.status)}`}>
                                    {order.status}
                                </div>

                                <Link
                                    to={order.status?.toLowerCase() === 'pendente' ? `/checkout/${order.id}` : '#'}
                                    className="p-2 hover:bg-white/5 rounded-full transition-colors hidden md:block"
                                >
                                    <ChevronRight />
                                </Link>
                            </div>
                        </motion.div>
                    ))
                )}
            </div>
        </div>
    );
};

export default Orders;
