<template>
  <section class="panel">
    <h2>Manage Quizzes</h2>
    <p class="meta">Admin overview of all quizzes.</p>

    <div v-if="loading" class="state">Loading quizzes...</div>
    <div v-else-if="!isAdmin" class="state state-error">Access denied. Admin only.</div>
    <div v-else>
      <table class="table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Questions</th>
            <th>Pass %</th>
            <th>Published</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="quiz in quizzes" :key="quiz.id">
            <td>{{ quiz.title }}</td>
            <td>{{ quiz.questions?.length || 0 }}</td>
            <td>{{ quiz.pass_percentage }}</td>
            <td>{{ quiz.is_published ? 'Yes' : 'No' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const quizzes = ref([]);
const loading = ref(true);
const profile = ref(null);

const isAdmin = computed(() => profile.value?.role === 'admin');

onMounted(async () => {
  try {
    const savedUser = localStorage.getItem('user');
    if (savedUser) {
      try {
        profile.value = JSON.parse(savedUser);
      } catch (error) {
        localStorage.removeItem('user');
      }
    }

    if (!profile.value) {
      const me = await axios.get('/auth/profile');
      profile.value = me.data;
      localStorage.setItem('user', JSON.stringify(me.data));
    }

    if (profile.value.role === 'admin') {
      const response = await axios.get('/quizzes');
      quizzes.value = response.data;
    }
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.panel {
  background: #fff;
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 1px 4px rgba(27, 39, 53, 0.08);
}

.meta {
  margin: 0.2rem 0 1rem;
  color: #5d7088;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  text-align: left;
  padding: 0.65rem;
  border-bottom: 1px solid #e5ebf2;
}

.state {
  color: #5d7088;
}

.state-error {
  color: #c53030;
}
</style>
