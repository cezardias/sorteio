import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { authService } from '../services/api';
import { Phone, Lock, ArrowRight } from 'lucide-react';
import { motion } from 'framer-motion';

const Login = () => {
    const navigate = useNavigate();
    const [formData, setFormData] = useState({
        cellphone: '',
        password: ''
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const res = await authService.login(formData);
            localStorage.setItem('client_token', res.data.token);
            localStorage.setItem('client_user', JSON.stringify(res.data.user));
            navigate('/');
        } catch (err) {
            setError(err.response?.data?.message || 'Telefone ou senha incorretos');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="max-w-md mx-auto py-24">
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="premium-card space-y-8"
            >
                <div className="text-center space-y-2">
                    <h1 className="text-3xl font-black italic uppercase">BEM-VINDO</h1>
                    <p className="text-gray-400 text-sm">Acesse sua conta para gerenciar seus pedidos.</p>
                </div>

                {error && (
                    <div className="bg-brand-red/10 border border-brand-red/20 text-brand-red p-3 rounded-lg text-sm text-center">
                        {error}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="relative">
                        <Phone className="absolute left-3 top-3.5 text-gray-500" size={18} />
                        <input
                            type="tel"
                            placeholder="Telefone (WhatsApp)"
                            className="input-field pl-10"
                            required
                            value={formData.cellphone}
                            onChange={(e) => setFormData({ ...formData, cellphone: e.target.value })}
                        />
                    </div>

                    <div className="relative">
                        <Lock className="absolute left-3 top-3.5 text-gray-500" size={18} />
                        <input
                            type="password"
                            placeholder="Senha"
                            className="input-field pl-10"
                            required
                            value={formData.password}
                            onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full btn-primary flex items-center justify-center gap-2"
                    >
                        {loading ? 'ENTRANDO...' : 'ACESSAR CONTA'}
                        <ArrowRight size={20} />
                    </button>
                </form>

                <p className="text-center text-sm text-gray-400">
                    Não tem uma conta? <Link to="/cadastro" className="text-brand-red font-bold hover:underline">Cadastre-se</Link>
                </p>
            </motion.div>
        </div>
    );
};

export default Login;
