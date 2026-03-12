import { useState, useEffect } from 'react';
import api from '../../services/api';
import { Users, Search, Edit2, Trash2, Mail, CreditCard } from 'lucide-react';
import { motion } from 'framer-motion';

const AdminClients = () => {
    const [selectedClient, setSelectedClient] = useState(null);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [editForm, setEditForm] = useState({ name: '', surname: '', email: '', cellphone: '', cpf: '' });

    useEffect(() => {
        fetchClients();
    }, []);

    const fetchClients = async () => {
        try {
            const res = await api.get('/admin/dashboard/todos/clientes');
            setClients(res.data);
            setLoading(false);
        } catch (err) {
            console.error(err);
            setLoading(false);
        }
    };

    const handleEdit = (client) => {
        setSelectedClient(client);
        setEditForm({
            id: client.id,
            name: client.name || '',
            surname: client.surname || '',
            email: client.email || '',
            cellphone: client.cellphone || '',
            cpf: client.cpf || ''
        });
        setIsEditModalOpen(true);
    };

    const handleUpdate = async (e) => {
        e.preventDefault();
        try {
            await api.put('/admin/dashboard/editar/cliente', editForm);
            setIsEditModalOpen(false);
            fetchClients();
            // Mostrar sucesso (ex: toast ou alert simples)
            alert('Cliente atualizado com sucesso!');
        } catch (err) {
            console.error(err);
            alert('Erro ao atualizar cliente: ' + (err.response?.data?.msg || err.message));
        }
    };

    const filteredClients = clients.filter(c =>
        c.name?.toLowerCase().includes(search.toLowerCase()) ||
        c.cellphone?.includes(search) ||
        c.email?.toLowerCase().includes(search.toLowerCase())
    );

    return (
        <div className="space-y-8">
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-black italic uppercase text-white">GERENCIAR CLIENTES</h1>
                    <p className="text-gray-400 text-sm">Visualize e edite as informações dos seus usuários.</p>
                </div>

                <div className="relative max-w-sm w-full">
                    <Search className="absolute left-3 top-3 text-gray-500" size={18} />
                    <input
                        type="text"
                        placeholder="Procurar por nome, email ou telefone..."
                        className="input-field pl-10"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
            </div>

            <div className="premium-card p-0 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-white/5 border-b border-glass-border">
                                <th className="px-6 py-4 text-xs font-black uppercase text-gray-400 tracking-wider">Nome</th>
                                <th className="px-6 py-4 text-xs font-black uppercase text-gray-400 tracking-wider">Contato</th>
                                <th className="px-6 py-4 text-xs font-black uppercase text-gray-400 tracking-wider">Documento</th>
                                <th className="px-6 py-4 text-xs font-black uppercase text-gray-400 tracking-wider text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-glass-border">
                            {loading ? (
                                Array(5).fill(0).map((_, i) => (
                                    <tr key={i} className="animate-pulse">
                                        <td colSpan="4" className="px-6 py-8 bg-white/5" />
                                    </tr>
                                ))
                            ) : filteredClients.map((client) => (
                                <tr key={client.id} className="hover:bg-white/5 transition-colors">
                                    <td className="px-6 py-4 font-bold uppercase">{client.name} {client.surname}</td>
                                    <td className="px-6 py-4 space-y-1">
                                        <div className="flex items-center gap-2 text-sm">
                                            <Mail size={14} className="text-brand-red" />
                                            {client.email || 'N/A'}
                                        </div>
                                        <div className="text-xs text-gray-400">{client.cellphone}</div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-2 text-sm">
                                            <CreditCard size={14} className="text-gray-500" />
                                            {client.cpf || 'N/A'}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <div className="flex items-center justify-end gap-2">
                                            <button
                                                onClick={() => handleEdit(client)}
                                                className="p-2 hover:bg-white/10 rounded-lg text-brand-red transition-colors"
                                            >
                                                <Edit2 size={18} />
                                            </button>
                                            <button className="p-2 hover:bg-white/10 rounded-lg text-gray-500 transition-colors">
                                                <Trash2 size={18} />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Edit Modal */}
            {isEditModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                    <motion.div
                        initial={{ opacity: 0, scale: 0.9 }}
                        animate={{ opacity: 1, scale: 1 }}
                        className="premium-card max-w-lg w-full p-8 relative"
                    >
                        <button
                            onClick={() => setIsEditModalOpen(false)}
                            className="absolute top-4 right-4 text-gray-500 hover:text-white"
                        >
                            <Trash2 size={24} />
                        </button>

                        <h2 className="text-2xl font-black italic uppercase mb-6 flex items-center gap-2">
                            <Edit2 className="text-brand-red" /> EDITAR CLIENTE
                        </h2>

                        <form onSubmit={handleUpdate} className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1">
                                    <label className="text-xs font-bold uppercase text-gray-500">Nome</label>
                                    <input
                                        type="text"
                                        className="input-field"
                                        value={editForm.name}
                                        onChange={(e) => setEditForm({ ...editForm, name: e.target.value })}
                                        required
                                    />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-xs font-bold uppercase text-gray-500">Sobrenome</label>
                                    <input
                                        type="text"
                                        className="input-field"
                                        value={editForm.surname}
                                        onChange={(e) => setEditForm({ ...editForm, surname: e.target.value })}
                                        required
                                    />
                                </div>
                            </div>

                            <div className="space-y-1">
                                <label className="text-xs font-bold uppercase text-gray-500">Email</label>
                                <input
                                    type="email"
                                    className="input-field"
                                    value={editForm.email}
                                    onChange={(e) => setEditForm({ ...editForm, email: e.target.value })}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1">
                                    <label className="text-xs font-bold uppercase text-gray-500">Telefone</label>
                                    <input
                                        type="text"
                                        className="input-field"
                                        value={editForm.cellphone}
                                        onChange={(e) => setEditForm({ ...editForm, cellphone: e.target.value })}
                                        disabled
                                    />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-xs font-bold uppercase text-gray-500">CPF</label>
                                    <input
                                        type="text"
                                        className="input-field"
                                        value={editForm.cpf}
                                        onChange={(e) => setEditForm({ ...editForm, cpf: e.target.value })}
                                    />
                                </div>
                            </div>

                            <button type="submit" className="primary-btn w-full mt-6 py-4">
                                ATUALIZAR DADOS
                            </button>
                        </form>
                    </motion.div>
                </div>
            )}
        </div>
    );
};

export default AdminClients;
