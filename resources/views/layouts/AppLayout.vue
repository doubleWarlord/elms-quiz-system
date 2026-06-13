<template>
  <div class="app-layout">
    <nav class="navbar">
      <div class="navbar-brand">ELMS Quiz System</div>
      <div class="navbar-menu">
        <router-link to="/" class="nav-link">Quizzes</router-link>
        <button @click="logout" class="btn-logout">Logout</button>
      </div>
    </nav>
    <div class="app-content">
      <router-view></router-view>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();

const logout = async () => {
  try {
    await axios.post('/auth/logout');
    localStorage.removeItem('token');
    router.push({ name: 'login' });
  } catch (error) {
    console.error('Logout failed:', error);
  }
};
</script>

<style scoped>
.app-layout {
  min-height: 100vh;
  background-color: #f5f5f5;
}

.navbar {
  background-color: #667eea;
  color: white;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.navbar-brand {
  font-size: 1.5rem;
  font-weight: bold;
}

.navbar-menu {
  display: flex;
  gap: 2rem;
  align-items: center;
}

.nav-link {
  color: white;
  text-decoration: none;
  transition: opacity 0.2s;
}

.nav-link:hover {
  opacity: 0.8;
}

.btn-logout {
  background-color: #764ba2;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-logout:hover {
  background-color: #5a3a7a;
}

.app-content {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}
</style>
