import { Outlet, Link } from 'react-router-dom';
import { Menu, User, ShoppingBag, LogOut } from 'lucide-react';
import { motion } from 'framer-motion';

const Layout = () => {
    return (
        <div className="min-h-screen flex flex-col">
            <header className="sticky top-0 z-50 bg-brand-dark/80 backdrop-blur-md border-b border-glass-border">
                <div className="container mx-auto px-4 h-20 flex items-center justify-between">
                    <Link to="/" className="flex items-center gap-2">
                        <span className="text-2xl font-black italic tracking-tighter text-white">
                            PREMIUM<span className="text-brand-red">MULTIMARCAS</span>
                        </span>
                    </Link>

                    <nav className="hidden md:flex items-center gap-8">
                        <Link to="/" className="text-sm font-bold hover:text-brand-red transition-colors">SORTEIOS</Link>
                        <Link to="/ganhadores" className="text-sm font-bold hover:text-brand-red transition-colors">GANHADORES</Link>
                    </nav>

                    <div className="flex items-center gap-4">
                        <Link to="/usuario/meus-pedidos" className="p-2 hover:bg-white/5 rounded-full transition-colors">
                            <ShoppingBag size={20} />
                        </Link>
                        <Link to="/usuario" className="flex items-center gap-2 bg-brand-red px-4 py-2 rounded-xl font-bold text-sm hover:scale-105 transition-transform">
                            <User size={18} />
                            <span className="hidden sm:inline">MINHA CONTA</span>
                        </Link>
                        <button className="md:hidden p-2">
                            <Menu size={24} />
                        </button>
                    </div>
                </div>
            </header>

            <main className="flex-grow container mx-auto px-4 py-8">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -20 }}
                    transition={{ duration: 0.5 }}
                >
                    <Outlet />
                </motion.div>
            </main>

            <footer className="bg-brand-gray/50 border-t border-glass-border py-12 mt-20">
                <div className="container mx-auto px-4 text-center">
                    <p className="text-sm text-gray-400">
                        &copy; 2026 Premium Multimarcas. Todos os direitos reservados.
                    </p>
                </div>
            </footer>
        </div>
    );
};

export default Layout;
