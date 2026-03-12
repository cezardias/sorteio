import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { rifaService } from '../services/api';
import { Ticket, ShoppingCart, Info, CheckCircle2 } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

const RifaDetails = () => {
    const { slug, id } = useParams();
    const navigate = useNavigate();
    const [rifa, setRifa] = useState(null);
    const [loading, setLoading] = useState(true);
    const [quantity, setQuantity] = useState(1);
    const [purchasing, setPurchasing] = useState(false);

    useEffect(() => {
        rifaService.getOne(slug, id)
            .then(res => {
                setRifa(res.data);
                setLoading(false);
            })
            .catch(err => {
                console.error(err);
                setLoading(false);
            });
    }, [slug, id]);

    const handleBuy = async () => {
        const token = localStorage.getItem('client_token');
        if (!token) {
            navigate('/login');
            return;
        }

        setPurchasing(true);
        try {
            const res = await rifaService.buy({
                rifas_id: id,
                quantidade_cotas: quantity
            });
            // Redirecionar para página de pagamento com o ID do pedido
            navigate(`/checkout/${res.data.id}`);
        } catch (err) {
            alert(err.response?.data?.message || 'Erro ao realizar compra');
        } finally {
            setPurchasing(false);
        }
    };

    if (loading) return <div className="min-h-[60vh] flex items-center justify-center text-xl font-bold">CARREGANDO...</div>;
    if (!rifa) return <div className="min-h-[60vh] flex items-center justify-center text-xl font-bold text-brand-red">RIFA NÃO ENCONTRADA</div>;

    return (
        <div className="max-w-6xl mx-auto space-y-12">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
                {/* Left: Images */}
                <div className="space-y-4">
                    <div className="aspect-square rounded-3xl overflow-hidden premium-card p-0 border-2 border-brand-red">
                        <img
                            src={rifa.image_url || "https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&q=80&w=800"}
                            alt={rifa.nome}
                            className="w-full h-full object-cover"
                        />
                    </div>
                    <div className="grid grid-cols-4 gap-4">
                        {/* Aqui entrariam miniaturas das outras imagens se houver */}
                        <div className="aspect-square rounded-xl overflow-hidden premium-card p-0 opacity-50 hover:opacity-100 transition-opacity cursor-pointer">
                            <img src={rifa.image_url} alt="" className="w-full h-full object-cover" />
                        </div>
                    </div>
                </div>

                {/* Right: Info & Buy */}
                <div className="space-y-8">
                    <div className="space-y-2">
                        <h1 className="text-4xl md:text-5xl font-black italic uppercase leading-none">{rifa.nome}</h1>
                        <p className="text-gray-400 font-medium">{rifa.sub_titulo || "Garanta sua cota e concorra a este prêmio incrível!"}</p>
                    </div>

                    <div className="flex items-center gap-4">
                        <div className="bg-brand-red/10 border border-brand-red/30 px-6 py-4 rounded-3xl">
                            <p className="text-xs font-bold text-gray-400 uppercase">Cota promocional</p>
                            <p className="text-3xl font-black text-white">R$ {rifa.preco_cota}</p>
                        </div>
                        <div className="flex-grow space-y-2">
                            <div className="flex justify-between text-xs font-bold text-gray-400">
                                <span>PROGRESSO DO SORTEIO</span>
                                <span>{rifa.porcentagem || 0}%</span>
                            </div>
                            <div className="h-3 bg-brand-gray rounded-full overflow-hidden">
                                <div className="h-full bg-brand-red" style={{ width: `${rifa.porcentagem || 0}%` }} />
                            </div>
                        </div>
                    </div>

                    <div className="premium-card space-y-6">
                        <div className="flex items-center gap-2 font-bold text-brand-red">
                            <Ticket size={20} />
                            <span>SELECIONE A QUANTIDADE</span>
                        </div>

                        <div className="grid grid-cols-3 sm:grid-cols-5 gap-3">
                            {[10, 50, 100, 500, 1000].map(val => (
                                <button
                                    key={val}
                                    onClick={() => setQuantity(val)}
                                    className={`py-3 rounded-xl border font-bold transition-all ${quantity === val ? 'bg-brand-red border-brand-red text-white scale-105' : 'bg-white/5 border-glass-border hover:border-brand-red'}`}
                                >
                                    +{val}
                                </button>
                            ))}
                        </div>

                        <div className="flex items-center gap-4">
                            <div className="flex items-center bg-brand-dark border border-glass-border rounded-xl px-4 py-2">
                                <button onClick={() => setQuantity(Math.max(1, quantity - 1))} className="text-2xl font-bold px-4">-</button>
                                <input
                                    type="number"
                                    value={quantity}
                                    onChange={(e) => setQuantity(Math.max(1, parseInt(e.target.value) || 1))}
                                    className="bg-transparent border-none text-center w-16 font-bold text-xl outline-none"
                                />
                                <button onClick={() => setQuantity(quantity + 1)} className="text-2xl font-bold px-4">+</button>
                            </div>
                            <div className="flex-grow">
                                <p className="text-xs text-gray-400 font-bold">TOTAL A PAGAR</p>
                                <p className="text-2xl font-black">R$ {(quantity * parseFloat(rifa.preco_cota)).toFixed(2)}</p>
                            </div>
                        </div>

                        <button
                            onClick={handleBuy}
                            disabled={purchasing}
                            className="w-full btn-success !py-5 flex items-center justify-center gap-3 text-xl"
                        >
                            <ShoppingCart size={24} />
                            {purchasing ? 'PROCESSANDO...' : 'COMPRAR AGORA'}
                        </button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-glass-border">
                            <CheckCircle2 size={32} className="text-brand-green shrink-0" />
                            <div>
                                <p className="font-bold text-sm">PAGAMENTO SEGURO</p>
                                <p className="text-xs text-gray-400">Via PIX com aprovação imediata pelo Escale Cyber.</p>
                            </div>
                        </div>
                        <div className="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-glass-border">
                            <Info size={32} className="text-brand-red shrink-0" />
                            <div>
                                <p className="font-bold text-sm">SORTEIO OFICIAL</p>
                                <p className="text-xs text-gray-400">Resultado baseado no sorteio da Loteria Federal.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Description & Rewards */}
            <section className="premium-card space-y-6">
                <h2 className="text-2xl font-black italic uppercase">Descrição do Prêmio</h2>
                <div
                    className="text-gray-300 leading-relaxed whitespace-pre-wrap"
                    dangerouslySetInnerHTML={{ __html: rifa.descricao }}
                />
            </section>
        </div>
    );
};

export default RifaDetails;
