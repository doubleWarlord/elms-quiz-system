<template>
  <div class="shell">
    <aside class="sidebar">
      <div class="brand">
        <router-link to="/" style="color:inherit;text-decoration:none">ELMS</router-link>
      </div>
      <p class="role" v-if="profile">{{ profile.role?.toUpperCase() }}</p>

      <nav class="menu">
        <router-link to="/" class="menu-link">Dashboard</router-link>
        <router-link to="/courses" class="menu-link">Courses</router-link>
        <router-link
          v-if="isTeacher || isAdmin"
          to="/courses/create"
          class="menu-link"
        >
          Create Course
        </router-link>
        <router-link to="/quizzes" class="menu-link">Quizzes</router-link>
        <router-link
          v-if="isTeacher || isAdmin"
          to="/quizzes/create"
          class="menu-link"
        >
          Create Quiz
        </router-link>
        <router-link v-if="isAdmin" to="/admin/users" class="menu-link">Manage Users</router-link>
        <router-link v-if="isAdmin" to="/admin/quizzes" class="menu-link">Manage Quizzes</router-link>
      </nav>

      <button @click="logout" class="logout">Logout</button>
    </aside>

    <main class="content">
      <header class="topbar">
        <h1>{{ pageTitle }}</h1>
        <p v-if="profile" class="welcome">Welcome, {{ profile.name }}</p>
      </header>
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';

const router = useRouter();
const route = useRoute();
const profile = ref(null);

const isAdmin = computed(() => profile.value?.role === 'admin');
const isTeacher = computed(() => profile.value?.role === 'teacher');

const pageTitle = computed(() => {
  switch (route.name) {
    case 'dashboard':
      return 'Dashboard';
    case 'manage-users':
      return 'Manage Users';
    case 'manage-quizzes':
      return 'Manage Quizzes';
    case 'quiz-list':
      return 'Quizzes';
    case 'quiz-create':
      return 'Create Quiz';
    default:
      return 'ELMS Quiz System';
  }
});

const hydrateProfileFromStorage = () => {
  const savedUser = localStorage.getItem('user');
  if (!savedUser) {
    return;
  }

  try {
    profile.value = JSON.parse(savedUser);
  } catch (error) {
    localStorage.removeItem('user');
  }
};

onMounted(async () => {
  hydrateProfileFromStorage();

  try {
    const response = await axios.get('/auth/profile');
    profile.value = response.data;
    localStorage.setItem('user', JSON.stringify(response.data));
  } catch (error) {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    delete axios.defaults.headers.common.Authorization;
    router.push({ name: 'login' });
  }
});

const logout = async () => {
  try {
    await axios.post('/auth/logout');
  } catch (error) {
    // Ignore logout API failures and clear local auth state anyway.
  } finally {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    delete axios.defaults.headers.common['Authorization'];

    try {
      await router.replace({ name: 'login' });
    } finally {
      // Force navigation in case router state is stale.
      if (window.location.pathname !== '/auth/login') {
        window.location.href = '/auth/login';
      }
    }
  }
};
</script>

<style scoped>
.shell {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 250px 1fr;
  background: #f4f6f8;
}

.sidebar {
  background: #20293a;
  color: #f7f9fc;
  display: flex;
  flex-direction: column;
  padding: 1.5rem 1rem;
}

.brand {
  font-size: 1.4rem;
  font-weight: 700;
  letter-spacing: 1px;
  margin-bottom: 0.4rem;
}

.role {
  color: #8fc7ff;
  font-size: 0.85rem;
  margin-bottom: 1.2rem;
}

.menu {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.menu-link {
  color: #d8e3f3;
  text-decoration: none;
  padding: 0.7rem 0.8rem;
  border-radius: 8px;
  transition: background-color 0.2s;
}

.menu-link.router-link-active {
  background: #2f3f5a;
  color: #fff;
}

.menu-link:hover {
  background: #27354d;
}

.logout {
  margin-top: auto;
  background: #e55353;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.65rem 0.8rem;
  cursor: pointer;
}

.content {
  padding: 1.4rem;
}

.topbar {
  background: #fff;
  border-radius: 12px;
  padding: 1rem 1.2rem;
  margin-bottom: 1rem;
  box-shadow: 0 2px 10px rgba(30, 40, 56, 0.08);
}

.topbar h1 {
  font-size: 1.25rem;
  margin: 0;
  color: #1f2d3d;
}

.welcome {
  margin: 0.35rem 0 0;
  color: #5d7088;
  font-size: 0.9rem;
}

@media (max-width: 900px) {
  .shell {
    grid-template-columns: 1fr;
  }

  .sidebar {
    border-bottom: 1px solid #314055;
  }
}
</style>
