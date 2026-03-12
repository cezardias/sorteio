import { useState, useEffect } from 'react';
import { rifaService } from '../services/api';
import { Sparkles, ArrowRight } from 'lucide-react';
import { motion } from 'framer-motion';

const Home = () => {
    const [rifas, setRifas] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        rifaService.getAll()
            .then(res => {
                setRifas(res.data);
                setLoading(false);
            })
            .catch(err => {
                console.error(err);
                setLoading(false);
            });
    }, []);

    return (
        <div className="space-y-12">
            {/* Hero Section */}
            <section className="relative overflow-hidden rounded-3xl premium-card p-0 min-h-[400px] flex items-center">
                <div className="absolute inset-0 bg-gradient-to-r from-brand-dark to-transparent z-10" />
                <img
                    src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&q=80&w=1200"
                    alt="Premium Cars"
                    className="absolute inset-0 w-full h-full object-cover opacity-50"
                />

                <div className="relative z-20 p-8 md:p-16 max-w-2xl space-y-6">
                    <motion.div
                        initial={{ opacity: 0, x: -50 }}
                        animate={{ opacity: 1, x: 0 }}
                        className="flex items-center gap-2 text-brand-red font-bold tracking-widest uppercase text-sm"
                    >
                        <Sparkles size={16} />
                        <span>Sorteios de Alta Performance</span>
                    </motion.div>

                    <motion.h1
                        initial={{ opacity: 0, x: -50 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ delay: 0.1 }}
                        className="text-5xl md:text-7xl font-black italic uppercase leading-none"
                    >
                        Sua chance de ter um <span className="text-brand-red">Premium</span>
                    </motion.h1>

                    <motion.p
                        initial={{ opacity: 0, x: -50 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ delay: 0.2 }}
                        className="text-gray-300 text-lg"
                    >
                        Os melhores carros, as melhores experiências. Participe agora dos nossos sorteios ativos.
                    </motion.p>

                    <motion.button
                        initial={{ opacity: 0, scale: 0.9 }}
                        animate={{ opacity: 1, scale: 1 }}
                        transition={{ delay: 0.3 }}
                        className="btn-primary flex items-center gap-2 group"
                    >
                        VER CAMPANHAS ATIVAS
                        <ArrowRight size={20} className="group-hover:translate-x-1 transition-transform" />
                    </motion.button>
                </div>
            </section>

            {/* Campaigns Grid */}
            <section>
                <div className="flex items-center justify-between mb-8">
                    <h2 className="text-3xl font-black italic uppercase">Próximos Sorteios</h2>
                    <div className="h-px flex-grow mx-8 bg-glass-border hidden md:block" />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    {loading ? (
                        Array(3).fill(0).map((_, i) => (
                            <div key={i} className="premium-card h-80 animate-pulse bg-white/5" />
                        ))
                    ) : rifas.map((rifa) => (
                        <motion.div
                            key={rifa.id}
                            whileHover={{ y: -8 }}
                            className="premium-card p-0 overflow-hidden group"
                        >
                            <div className="relative aspect-video">
                                <img
                                    src={rifa.image_url || "https://images.unsplash.com/photo-1494976388531-d1058494cdd8?auto=format&fit=crop&q=80&w=600"}
                                    alt={rifa.nome}
                                    className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                />
                                <div className="absolute top-4 right-4 bg-brand-red px-3 py-1 rounded-full font-black text-xs">
                                    R$ {rifa.preco_cota}
                                </div>
                            </div>

                            <div className="p-6 space-y-4">
                                <h3 className="text-xl font-bold uppercase truncate">{rifa.nome}</h3>

                                <div className="space-y-2">
                                    <div className="flex justify-between text-xs font-bold text-gray-400">
                                        <span>PROGRESSO</span>
                                        <span>{rifa.porcentagem || 0}%</span>
                                    </div>
                                    <div className="h-2 bg-brand-gray rounded-full overflow-hidden">
                                        <motion.div
                                            initial={{ width: 0 }}
                                            animate={{ width: `${rifa.porcentagem || 0}%` }}
                                            className="h-full bg-brand-red"
                                        />
                                    </div>
                                </div>

                                <button className="w-full btn-primary !py-3">PARTICIPAR AGORA</button>
                            </div>
                        </motion.div>
                    ))}
                </div>
            </section>
        </div>
    );
};

export default Home;
