<template>
  <div class="quiz-results">
    <div class="results-header">
      <h1>Quiz Results</h1>
    </div>

    <div v-if="loading" class="loading">Loading results...</div>
    <div v-else-if="results" class="results-container">
      <div class="score-card" :class="{ passed: results.passed, failed: !results.passed }">
        <h2>{{ results.quiz.title }}</h2>
        <div class="score-display">
          <span class="score-number">{{ results.score }}%</span>
          <span class="score-status">{{ results.passed ? 'PASSED' : 'FAILED' }}</span>
        </div>
        <div class="score-details">
          <p>Correct Answers: <strong>{{ results.correct_answers }} / {{ results.total_questions }}</strong></p>
          <p>Pass Requirement: <strong>{{ results.quiz.pass_percentage }}%</strong></p>
        </div>
      </div>

      <div class="results-actions">
        <button @click="retakeQuiz" class="btn-retry">Retake Quiz</button>
        <button @click="backToQuizzes" class="btn-back">Back to Quizzes</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();
const quizId = route.params.id;

const results = ref(null);
const loading = ref(false);

onMounted(async () => {
  await fetchResults();
});

const fetchResults = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/quizzes/${quizId}/results`);
    results.value = response.data;
  } catch (error) {
    console.error('Failed to fetch results:', error);
  } finally {
    loading.value = false;
  }
};

const retakeQuiz = () => {
  router.push({ name: 'quiz-take', params: { id: quizId } });
};

const backToQuizzes = () => {
  router.push({ name: 'quiz-list' });
};
</script>

<style scoped>
.quiz-results {
  padding: 2rem;
  max-width: 600px;
  margin: 0 auto;
}

.results-header {
  text-align: center;
  margin-bottom: 2rem;
}

.results-header h1 {
  color: #333;
}

.loading {
  text-align: center;
  padding: 3rem;
  color: #666;
}

.results-container {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.score-card {
  padding: 2rem;
  text-align: center;
}

.score-card.passed {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.score-card.failed {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
}

.score-card h2 {
  margin-bottom: 1.5rem;
  font-size: 1.5rem;
}

.score-display {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2rem;
  margin-bottom: 2rem;
}

.score-number {
  font-size: 4rem;
  font-weight: bold;
}

.score-status {
  font-size: 1.5rem;
  font-weight: bold;
  text-transform: uppercase;
}

.score-details {
  text-align: left;
  display: inline-block;
}

.score-details p {
  margin: 0.5rem 0;
  font-size: 1rem;
}

.results-actions {
  padding: 2rem;
  display: flex;
  gap: 1rem;
}

.btn-retry,
.btn-back {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-retry {
  background-color: #667eea;
  color: white;
}

.btn-retry:hover {
  background-color: #5568d3;
}

.btn-back {
  background-color: #cbd5e0;
  color: #333;
}

.btn-back:hover {
  background-color: #a0aec0;
}
</style>
