import { useState, useEffect } from 'react';
import api from '../services/api';
import { User, Mail, Phone, CreditCard, Save } from 'lucide-react';
import { motion } from 'framer-motion';

const Profile = () => {
    const [formData, setFormData] = useState({
        name: '',
        surname: '',
        email: '',
        cellphone: '',
        cpf: ''
    });
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState({ type: '', text: '' });

    useEffect(() => {
        const fetchProfile = async () => {
            try {
                const res = await api.get('/get-profile');
                setFormData({
                    name: res.data.name || '',
                    surname: res.data.surname || '',
                    email: res.data.email || '',
                    cellphone: res.data.cellphone || '',
                    cpf: res.data.cpf || ''
                });
                setLoading(false);
            } catch (err) {
                console.error(err);
                setLoading(false);
            }
        };

        fetchProfile();
    }, []);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setMessage({ type: '', text: '' });

        try {
            await api.post('/update-profile', formData);
            setMessage({ type: 'success', text: 'Perfil atualizado com sucesso!' });
        } catch (err) {
            setMessage({ type: 'error', text: err.response?.data?.message || 'Erro ao atualizar perfil' });
        } finally {
            setSaving(false);
        }
    };

    if (loading) return <div className="text-center py-12 font-bold">CARREGANDO PERFIL...</div>;

    return (
        <div className="max-w-2xl mx-auto space-y-8">
            <div className="flex items-center gap-4">
                <div className="p-3 bg-brand-red rounded-2xl">
                    <User size={24} />
                </div>
                <div>
                    <h1 className="text-3xl font-black italic uppercase">MEUS DADOS</h1>
                    <p className="text-gray-400 text-sm">Mantenha suas informações sempre atualizadas.</p>
                </div>
            </div>

            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="premium-card"
            >
                {message.text && (
                    <div className={`mb-6 p-4 rounded-xl text-sm font-bold text-center border ${message.type === 'success' ? 'bg-brand-green/10 border-brand-green/20 text-brand-green' : 'bg-brand-red/10 border-brand-red/20 text-brand-red'}`}>
                        {message.text}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <label className="text-xs font-black text-gray-500 uppercase flex items-center gap-2">
                                <User size={14} /> Nome
                            </label>
                            <input
                                type="text"
                                className="input-field"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-xs font-black text-gray-500 uppercase">Sobrenome</label>
                            <input
                                type="text"
                                className="input-field"
                                value={formData.surname}
                                onChange={(e) => setFormData({ ...formData, surname: e.target.value })}
                                required
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <label className="text-xs font-black text-gray-500 uppercase flex items-center gap-2">
                            <Mail size={14} /> E-mail
                        </label>
                        <input
                            type="email"
                            className="input-field"
                            value={formData.email}
                            onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                            required
                        />
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <label className="text-xs font-black text-gray-500 uppercase flex items-center gap-2">
                                <Phone size={14} /> WhatsApp
                            </label>
                            <input
                                type="tel"
                                className="input-field"
                                value={formData.cellphone}
                                onChange={(e) => setFormData({ ...formData, cellphone: e.target.value })}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-xs font-black text-gray-500 uppercase flex items-center gap-2">
                                <CreditCard size={14} /> CPF
                            </label>
                            <input
                                type="text"
                                className="input-field"
                                value={formData.cpf}
                                onChange={(e) => setFormData({ ...formData, cpf: e.target.value })}
                                required
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={saving}
                        className="w-full btn-primary !py-4 flex items-center justify-center gap-2"
                    >
                        <Save size={20} />
                        {saving ? 'SALVANDO...' : 'SALVAR ALTERAÇÕES'}
                    </button>
                </form>
            </motion.div>
        </div>
    );
};

export default Profile;
