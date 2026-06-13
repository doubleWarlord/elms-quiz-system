import { createRouter, createWebHistory } from 'vue-router';
import AuthLayout from '../views/layouts/AuthLayout.vue';
import AppLayout from '../views/layouts/AppLayout.vue';
import Login from '../views/auth/Login.vue';
import Register from '../views/auth/Register.vue';
import QuizList from '../views/quiz/QuizList.vue';
import QuizCreate from '../views/quiz/QuizCreate.vue';
import QuizTake from '../views/quiz/QuizTake.vue';
import QuizResults from '../views/quiz/QuizResults.vue';

const routes = [
    {
        path: '/auth',
        component: AuthLayout,
        children: [
            { path: 'login', component: Login, name: 'login' },
            { path: 'register', component: Register, name: 'register' },
        ],
    },
    {
        path: '/',
        component: AppLayout,
        meta: { requiresAuth: true },
        children: [
            { path: '', component: QuizList, name: 'quiz-list' },
            { path: 'quizzes/create', component: QuizCreate, name: 'quiz-create' },
            { path: 'quizzes/:id/take', component: QuizTake, name: 'quiz-take' },
            { path: 'quizzes/:id/results', component: QuizResults, name: 'quiz-results' },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token');
    const requiresAuth = to.matched.some(record => record.meta.requiresAuth);

    if (requiresAuth && !token) {
        next({ name: 'login' });
    } else if ((to.name === 'login' || to.name === 'register') && token) {
        next({ name: 'quiz-list' });
    } else {
        next();
    }
});

export default router;
