import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Add a request interceptor to include the auth token
api.interceptors.request.use(config => {
    const token = localStorage.getItem('client_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export const authService = {
    register: (data) => api.post('/client/cadastro', data),
    login: (data) => api.post('/client/login', data),
    getProfile: () => api.get('/get-profile'),
    updateProfile: (data) => api.post('/update-profile', data)
};

export const rifaService = {
    getAll: () => api.get('/produtos'),
    getOne: (slug, id) => api.get(`/produtos/${slug}/${id}`),
    buy: (data) => api.post('/produtos/comprar-rifa', data),
    checkPayment: (id) => api.get(`/produtos/payment-status/${id}`)
};

export const configService = {
    getSiteConfig: () => api.get('/config')
};

export default api;
