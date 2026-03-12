import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { authService } from '../services/api';
import { User, Mail, Phone, Lock, CreditCard, ArrowRight } from 'lucide-react';
import { motion } from 'framer-motion';

const Register = () => {
    const navigate = useNavigate();
    const [formData, setFormData] = useState({
        name: '',
        surname: '',
        email: '',
        cellphone: '',
        cpf: '',
        password: '',
        password_confirmation: ''
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            await authService.register(formData);
            navigate('/login');
        } catch (err) {
            setError(err.response?.data?.message || 'Erro ao realizar cadastro');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="max-w-md mx-auto py-12">
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="premium-card space-y-8"
            >
                <div className="text-center space-y-2">
                    <h1 className="text-3xl font-black italic uppercase">CRIAR CONTA</h1>
                    <p className="text-gray-400 text-sm">Participe dos melhores sorteios com exclusividade.</p>
                </div>

                {error && (
                    <div className="bg-brand-red/10 border border-brand-red/20 text-brand-red p-3 rounded-lg text-sm text-center">
                        {error}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="relative">
                            <User className="absolute left-3 top-3.5 text-gray-500" size={18} />
                            <input
                                type="text"
                                placeholder="Nome"
                                className="input-field pl-10"
                                required
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                            />
                        </div>
                        <div className="relative">
                            <input
                                type="text"
                                placeholder="Sobrenome"
                                className="input-field pl-4"
                                required
                                value={formData.surname}
                                onChange={(e) => setFormData({ ...formData, surname: e.target.value })}
                            />
                        </div>
                    </div>

                    <div className="relative">
                        <Mail className="absolute left-3 top-3.5 text-gray-500" size={18} />
                        <input
                            type="email"
                            placeholder="E-mail"
                            className="input-field pl-10"
                            required
                            value={formData.email}
                            onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                        />
                    </div>

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
                        <CreditCard className="absolute left-3 top-3.5 text-gray-500" size={18} />
                        <input
                            type="text"
                            placeholder="CPF"
                            className="input-field pl-10"
                            required
                            value={formData.cpf}
                            onChange={(e) => setFormData({ ...formData, cpf: e.target.value })}
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

                    <div className="relative">
                        <Lock className="absolute left-3 top-3.5 text-gray-500" size={18} />
                        <input
                            type="password"
                            placeholder="Confirmar Senha"
                            className="input-field pl-10"
                            required
                            value={formData.password_confirmation}
                            onChange={(e) => setFormData({ ...formData, password_confirmation: e.target.value })}
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full btn-primary flex items-center justify-center gap-2"
                    >
                        {loading ? 'CADASTRANDO...' : 'FINALIZAR CADASTRO'}
                        <ArrowRight size={20} />
                    </button>
                </form>

                <p className="text-center text-sm text-gray-400">
                    Já tem uma conta? <Link to="/login" className="text-brand-red font-bold hover:underline">Entre aqui</Link>
                </p>
            </motion.div>
        </div>
    );
};

export default Register;
