import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import axios from 'axios';

const app = createApp(App);

axios.defaults.baseURL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
axios.defaults.headers.common['Accept'] = 'application/json';

const token = localStorage.getItem('token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

app.use(router);
app.mount('#app');
