import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { Copy, CheckCircle2, Clock, AlertCircle } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

const Checkout = () => {
    const { orderId } = useParams();
    const navigate = useNavigate();
    const [order, setOrder] = useState(null);
    const [loading, setLoading] = useState(true);
    const [copied, setCopied] = useState(false);
    const [status, setStatus] = useState('pending'); // pending, paid, cancelled

    useEffect(() => {
        const fetchOrder = async () => {
            try {
                const res = await api.get(`/client/checkout/pedido/${orderId}`);
                setOrder(res.data);
                setStatus(res.data.status?.toLowerCase() || 'pending');
                setLoading(false);
            } catch (err) {
                console.error(err);
                setLoading(false);
            }
        };

        fetchOrder();
    }, [orderId]);

    // Polling for payment status
    useEffect(() => {
        if (status !== 'pending' || !orderId) return;

        const interval = setInterval(async () => {
            try {
                const res = await api.get(`/produtos/payment-status/${orderId}`);
                if (res.data.status?.toLowerCase() === 'pago' || res.data.status?.toLowerCase() === 'aprovado') {
                    setStatus('paid');
                    clearInterval(interval);
                }
            } catch (err) {
                console.error('Polling error', err);
            }
        }, 5000);

        return () => clearInterval(interval);
    }, [orderId, status]);

    const copyPix = () => {
        if (order?.pix_copia_e_cola) {
            navigator.clipboard.writeText(order.pix_copia_e_cola);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    if (loading) return <div className="min-h-[60vh] flex items-center justify-center text-xl font-bold">CARREGANDO PAGAMENTO...</div>;
    if (!order) return <div className="min-h-[60vh] flex items-center justify-center text-xl font-bold text-brand-red">PEDIDO NÃO ENCONTRADO</div>;

    return (
        <div className="max-w-2xl mx-auto py-12 space-y-8 text-center">
            <AnimatePresence mode="wait">
                {status === 'pending' && (
                    <motion.div
                        key="pending"
                        initial={{ opacity: 0, scale: 0.9 }}
                        animate={{ opacity: 1, scale: 1 }}
                        exit={{ opacity: 0, scale: 0.9 }}
                        className="premium-card space-y-8"
                    >
                        <div className="space-y-2">
                            <h1 className="text-3xl font-black italic uppercase">PAGAMENTO PIX</h1>
                            <p className="text-gray-400">Escaneie o QR Code abaixo ou copie o código.</p>
                        </div>

                        <div className="bg-white p-4 rounded-2xl inline-block shadow-2xl">
                            <img
                                src={order.pix_qr_code || `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(order.pix_copia_e_cola || '')}`}
                                alt="QR Code PIX"
                                className="w-48 h-48"
                            />
                        </div>

                        <div className="space-y-4">
                            <button
                                onClick={copyPix}
                                className="w-full btn-primary flex items-center justify-center gap-2 !py-4"
                            >
                                {copied ? <CheckCircle2 size={20} /> : <Copy size={20} />}
                                {copied ? 'CÓDIGO COPIADO!' : 'COPIAR CÓDIGO PIX'}
                            </button>

                            <div className="flex items-center justify-center gap-2 text-amber-500 font-bold animate-pulse">
                                <Clock size={18} />
                                <span>AGUARDANDO PAGAMENTO...</span>
                            </div>
                        </div>

                        <div className="pt-8 border-t border-glass-border">
                            <p className="text-xs text-gray-500 uppercase font-black">Detalhes da Compra</p>
                            <div className="flex justify-between mt-4 font-bold">
                                <span className="text-gray-400">PEDIDO:</span>
                                <span>#{order.id}</span>
                            </div>
                            <div className="flex justify-between mt-2 font-bold text-2xl">
                                <span>TOTAL:</span>
                                <span className="text-brand-red">R$ {order.total}</span>
                            </div>
                        </div>
                    </motion.div>
                )}

                {(status === 'paid' || status === 'aprovado') && (
                    <motion.div
                        key="paid"
                        initial={{ opacity: 0, y: 50 }}
                        animate={{ opacity: 1, y: 0 }}
                        className="premium-card space-y-8 border-brand-green bg-brand-green/5"
                    >
                        <div className="flex justify-center">
                            <div className="w-24 h-24 bg-brand-green rounded-full flex items-center justify-center shadow-[0_0_30px_rgba(16,185,129,0.5)]">
                                <CheckCircle2 size={48} className="text-white" />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <h1 className="text-3xl font-black italic uppercase text-brand-green">PAGAMENTO APROVADO!</h1>
                            <p className="text-gray-300">Suas cotas foram geradas com sucesso. Boa sorte!</p>
                        </div>

                        <div className="premium-card bg-brand-dark/50 border-brand-green/20">
                            <p className="text-sm font-bold text-gray-400 mb-4 uppercase italic">Seus Números:</p>
                            <div className="flex flex-wrap justify-center gap-2">
                                {order.numeros?.split(',').map(num => (
                                    <span key={num} className="bg-brand-green/20 text-brand-green px-3 py-1 rounded-lg font-black text-sm border border-brand-green/30">
                                        {num}
                                    </span>
                                ))}
                            </div>
                        </div>

                        <button
                            onClick={() => navigate('/usuario/meus-pedidos')}
                            className="w-full btn-success !py-4"
                        >
                            VER MEUS PEDIDOS
                        </button>
                    </motion.div>
                )}

                {status === 'cancelled' && (
                    <motion.div
                        key="cancelled"
                        initial={{ opacity: 0, y: 50 }}
                        animate={{ opacity: 1, y: 0 }}
                        className="premium-card space-y-8 border-brand-red bg-brand-red/5"
                    >
                        <AlertCircle size={64} className="mx-auto text-brand-red" />
                        <div className="space-y-2">
                            <h1 className="text-3xl font-black italic uppercase text-brand-red">PEDIDO CANCELADO</h1>
                            <p className="text-gray-300">Este pedido expirou ou foi cancelado.</p>
                        </div>
                        <button
                            onClick={() => navigate('/')}
                            className="w-full btn-primary"
                        >
                            VOLTAR AO INÍCIO
                        </button>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
};

export default Checkout;
