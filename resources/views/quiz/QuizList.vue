<template>
  <div class="quiz-list">
    <div class="list-header">
      <h1>Quizzes</h1>
      <button v-if="isTeacher" @click="navigateToCreate" class="btn-create">
        + Create Quiz
      </button>
    </div>

    <div v-if="loading" class="loading">Loading quizzes...</div>
    <div v-else-if="quizzes.length === 0" class="no-quizzes">
      <p>No quizzes available yet.</p>
    </div>
    <div v-else class="quizzes-grid">
      <div v-for="quiz in quizzes" :key="quiz.id" class="quiz-card">
        <h3>{{ quiz.title }}</h3>
        <p class="description">{{ quiz.description }}</p>
        <div class="quiz-info">
          <span class="questions">{{ quiz.questions.length }} Questions</span>
          <span class="pass-rate">Pass: {{ quiz.pass_percentage }}%</span>
        </div>
        <div class="quiz-actions">
          <button @click="viewQuiz(quiz.id)" class="btn-view">View</button>
          <button v-if="isTeacher" @click="editQuiz(quiz.id)" class="btn-edit">Edit</button>
          <button v-else @click="takeQuiz(quiz.id)" class="btn-take">Take Quiz</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const quizzes = ref([]);
const loading = ref(false);
const userRole = ref('');

const isTeacher = computed(() => userRole.value === 'teacher');

onMounted(async () => {
  await fetchQuizzes();
  await fetchUserProfile();
});

const fetchQuizzes = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/quizzes');
    quizzes.value = response.data;
  } catch (error) {
    console.error('Failed to fetch quizzes:', error);
  } finally {
    loading.value = false;
  }
};

const fetchUserProfile = async () => {
  try {
    const response = await axios.get('/auth/profile');
    userRole.value = response.data.role;
  } catch (error) {
    console.error('Failed to fetch profile:', error);
  }
};

const navigateToCreate = () => {
  router.push({ name: 'quiz-create' });
};

const viewQuiz = (id) => {
  router.push({ name: 'quiz-take', params: { id } });
};

const editQuiz = (id) => {
  router.push({ name: 'quiz-create', params: { id } });
};

const takeQuiz = (id) => {
  router.push({ name: 'quiz-take', params: { id } });
};
</script>

<style scoped>
.quiz-list {
  padding: 2rem;
}

.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

h1 {
  color: #333;
  margin: 0;
}

.btn-create {
  background-color: #667eea;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  transition: background-color 0.2s;
}

.btn-create:hover {
  background-color: #5568d3;
}

.loading,
.no-quizzes {
  text-align: center;
  padding: 3rem;
  color: #666;
  font-size: 1.1rem;
}

.quizzes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
}

.quiz-card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.quiz-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.quiz-card h3 {
  color: #333;
  margin-bottom: 0.5rem;
}

.description {
  color: #666;
  margin-bottom: 1rem;
  font-size: 0.95rem;
}

.quiz-info {
  display: flex;
  justify-content: space-between;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #eee;
}

.questions,
.pass-rate {
  font-size: 0.9rem;
  color: #667eea;
  font-weight: 500;
}

.quiz-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-view,
.btn-edit,
.btn-take {
  flex: 1;
  padding: 0.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: background-color 0.2s;
}

.btn-view {
  background-color: #667eea;
  color: white;
}

.btn-view:hover {
  background-color: #5568d3;
}

.btn-edit {
  background-color: #48bb78;
  color: white;
}

.btn-edit:hover {
  background-color: #38a169;
}

.btn-take {
  background-color: #ed8936;
  color: white;
}

.btn-take:hover {
  background-color: #dd6b20;
}
</style>
