import { Routes, Route } from 'react-router-dom';
import Layout from './components/Layout';
import AdminLayout from './components/AdminLayout';
import Home from './pages/Home';
import Register from './pages/Register';
import Login from './pages/Login';
import RifaDetails from './pages/RifaDetails';
import Checkout from './pages/Checkout';
import Orders from './pages/Orders';
import Profile from './pages/Profile';
import AdminClients from './pages/admin/Clients';
import AdminOrders from './pages/admin/Orders';
import AdminRifas from './pages/admin/Rifas';

import AdminRedirect from './pages/admin/AdminRedirect';

function App() {
  return (
    <Routes>
      {/* Redirecionamento Legado */}
      <Route path="/dash/*" element={<AdminRedirect />} />

      {/* Site Público */}
      <Route path="/" element={<Layout />}>
        <Route index element={<Home />} />
        <Route path="cadastro" element={<Register />} />
        <Route path="login" element={<Login />} />
        <Route path="checkout/:orderId" element={<Checkout />} />
        <Route path="usuario/meus-pedidos" element={<Orders />} />
        <Route path="usuario" element={<Profile />} />
        <Route path=":slug/:id" element={<RifaDetails />} />
      </Route>

      {/* Admin Panel */}
      <Route path="/admin" element={<AdminLayout />}>
        <Route index element={<div className="text-white">Em breve: Relatório Geral</div>} />
        <Route path="clientes" element={<AdminClients />} />
        <Route path="sorteios" element={<AdminRifas />} />
        <Route path="pedidos" element={<AdminOrders />} />
        <Route path="vendas" element={<div className="text-white text-3xl font-black italic uppercase">Gestão de Vendas (Em breve)</div>} />
        <Route path="ranking" element={<div className="text-white text-3xl font-black italic uppercase">Ranking de Compradores (Em breve)</div>} />
        <Route path="ganhadores" element={<div className="text-white text-3xl font-black italic uppercase">Lista de Ganhadores (Em breve)</div>} />
        <Route path="afiliados" element={<div className="text-white text-3xl font-black italic uppercase">Gerenciar Afiliados (Em breve)</div>} />
        <Route path="config" element={<div className="text-white text-3xl font-black italic uppercase">Configurações do Site (Em breve)</div>} />
      </Route>
    </Routes>
  );
}

export default App;
