import { useState, useEffect } from 'react';
import { Outlet, Link, useNavigate } from 'react-router-dom';
import { LayoutDashboard, Users, Ticket, Settings, LogOut, ChevronRight, Trophy, Gift, Share2, ShoppingCart } from 'lucide-react';

const AdminLayout = () => {
    const navigate = useNavigate();

    useEffect(() => {
        const token = localStorage.getItem('admin_token');
        if (!token && window.location.pathname !== '/admin/login') {
            // navigate('/admin/login');
        }
    }, [navigate]);

    const handleLogout = () => {
        localStorage.removeItem('admin_token');
        navigate('/admin/login');
    };

    return (
        <div className="min-h-screen bg-brand-dark flex flex-col md:flex-row">
            {/* Sidebar */}
            <aside className="w-full md:w-64 bg-brand-gray/30 border-r border-glass-border p-6 flex flex-col gap-12 overflow-y-auto max-h-screen">
                <div className="flex items-center gap-2 px-2">
                    <span className="text-xl font-black italic tracking-tighter text-white">
                        ADMIN<span className="text-brand-red">PANEL</span>
                    </span>
                </div>

                <nav className="flex-grow space-y-2">
                    <Link to="/admin" className="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-colors group">
                        <div className="flex items-center gap-3">
                            <LayoutDashboard size={20} className="group-hover:text-brand-red" />
                            <span className="font-bold text-sm text-white">Dashboard</span>
                        </div>
                        <ChevronRight size={14} className="text-gray-600" />
                    </Link>

                    <Link to="/admin/vendas" className="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-colors group">
                        <div className="flex items-center gap-3">
                            <ShoppingCart size={20} className="group-hover:text-brand-red" />
                            <span className="font-bold text-sm text-white">Vendas</span>
                        </div>
                        <ChevronRight size={14} className="text-gray-600" />
                    </Link>

                    <Link to="/admin/sorteios" className="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-colors group">
                        <div className="flex items-center gap-3">
                            <Ticket size={20} className="group-hover:text-brand-red" />
                            <span className="font-bold text-sm text-white">Sorteios</span>
                        </div>
                        <ChevronRight size={14} className="text-gray-600" />
                    </Link>

                    <Link to="/admin/pedidos" className="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-colors group">
                        <div className="flex items-center gap-3">
                            <Gift size={20} className="group-hover:text-brand-red" />
                            <span className="font-bold text-sm text-white">Pedidos</span>
                        </div>
                        <ChevronRight size={14} className="text-gray-600" />
                    </Link>

                    <Link to="/admin/clientes" className="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-colors group">
                        <div className="flex items-center gap-3">
                            <Users size={20} className="group-hover:text-brand-red" />
                            <span className="font-bold text-sm text-white">Clientes</span>
                        </div>
                        <ChevronRight size={14} className="text-gray-600" />
                    </Link>

                    <Link to="/admin/ranking" className="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-colors group">
                        <div className="flex items-center gap-3">
                            <Trophy size={20} className="group-hover:text-brand-red" />
                            <span className="font-bold text-sm text-white">Ranking</span>
                        </div>
                        <ChevronRight size={14} className="text-gray-600" />
                    </Link>

                    <Link to="/admin/ganhadores" className="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-colors group">
                        <div className="flex items-center gap-3">
                            <Gift size={20} className="group-hover:text-brand-red" />
                            <span className="font-bold text-sm text-white">Ganhadores</span>
                        </div>
                        <ChevronRight size={14} className="text-gray-600" />
                    </Link>

                    <Link to="/admin/afiliados" className="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-colors group">
                        <div className="flex items-center gap-3">
                            <Share2 size={20} className="group-hover:text-brand-red" />
                            <span className="font-bold text-sm text-white">Afiliados</span>
                        </div>
                        <ChevronRight size={14} className="text-gray-600" />
                    </Link>

                    <Link to="/admin/config" className="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-colors group">
                        <div className="flex items-center gap-3">
                            <Settings size={20} className="group-hover:text-brand-red" />
                            <span className="font-bold text-sm text-white">Configurações</span>
                        </div>
                        <ChevronRight size={14} className="text-gray-600" />
                    </Link>
                </nav>

                <button
                    onClick={handleLogout}
                    className="flex items-center gap-3 p-3 rounded-xl hover:bg-brand-red/10 text-brand-red transition-colors font-bold text-sm"
                >
                    <LogOut size={20} />
                    <span>Sair do Painel</span>
                </button>
            </aside>

            {/* Content Area */}
            <main className="flex-grow p-6 md:p-12">
                <Outlet />
            </main>
        </div>
    );
};

export default AdminLayout;
