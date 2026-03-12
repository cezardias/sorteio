import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

const AdminRedirect = () => {
    const navigate = useNavigate();

    useEffect(() => {
        // Redireciona para o novo painel admin
        navigate('/admin', { replace: true });
    }, [navigate]);

    return (
        <div className="min-h-screen bg-brand-dark flex items-center justify-center">
            <div className="flex flex-col items-center gap-4">
                <div className="w-12 h-12 border-4 border-brand-red border-t-transparent rounded-full animate-spin"></div>
                <p className="text-gray-400 font-bold animate-pulse uppercase tracking-widest text-xs">Redirecionando para o novo painel...</p>
            </div>
        </div>
    );
};

export default AdminRedirect;
