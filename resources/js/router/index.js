import { createRouter, createWebHistory } from 'vue-router';
import AuthLayout from '../views/layouts/AuthLayout.vue';
import AppLayout from '../views/layouts/AppLayout.vue';
import Login from '../views/auth/Login.vue';
import Register from '../views/auth/Register.vue';
import QuizList from '../views/quiz/QuizList.vue';
import QuizCreate from '../views/quiz/QuizCreate.vue';
import QuizTake from '../views/quiz/QuizTake.vue';
import QuizResults from '../views/quiz/QuizResults.vue';
import QuizCertificate from '../views/quiz/QuizCertificate.vue';
import DashboardHome from '../views/dashboard/DashboardHome.vue';
import ManageUsers from '../views/admin/ManageUsers.vue';
import ManageQuizzes from '../views/admin/ManageQuizzes.vue';
import LandingPage from '../views/LandingPage.vue';
import CourseCreate from '../views/course/CourseCreate.vue';
import CoursePlayer from '../views/course/CoursePlayer.vue';

const routes = [
    { path: '/welcome', component: LandingPage, name: 'landing' },
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
            { path: '', component: DashboardHome, name: 'dashboard' },
            // Courses
            { path: 'courses', component: CourseCreate, name: 'course-list' },
            { path: 'courses/create', component: CourseCreate, name: 'course-create' },
            { path: 'courses/:id/edit', component: CourseCreate, name: 'course-edit' },
            { path: 'courses/:id', component: CoursePlayer, name: 'course-player' },
            // Quizzes
            { path: 'quizzes', component: QuizList, name: 'quiz-list' },
            { path: 'quizzes/create', component: QuizCreate, name: 'quiz-create' },
            { path: 'quizzes/:id/edit', component: QuizCreate, name: 'quiz-edit' },
            { path: 'quizzes/:id/take', component: QuizTake, name: 'quiz-take' },
            { path: 'quizzes/:id/results', component: QuizResults, name: 'quiz-results' },
            { path: 'quizzes/:id/certificate', component: QuizCertificate, name: 'quiz-certificate' },
            // Admin
            { path: 'admin/users', component: ManageUsers, name: 'manage-users' },
            { path: 'admin/quizzes', component: ManageQuizzes, name: 'manage-quizzes' },
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
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;
