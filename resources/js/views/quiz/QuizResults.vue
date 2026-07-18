<template>
  <div class="quiz-results">
    <div class="results-header">
      <h1>Quiz Results</h1>
    </div>

    <div v-if="loading" class="loading">Loading results...</div>
    <div v-else-if="results" class="results-container">
      <div class="score-card" :class="{ passed: results.passed, failed: !results.passed }">
        <h2>{{ results.quiz.title }}</h2>
        <div class="attempt-badge" v-if="results.student_quiz?.attempt_number">
          Attempt {{ results.student_quiz.attempt_number }}<span v-if="results.quiz?.attempts_allowed > 0"> / {{ results.quiz.attempts_allowed }}</span>
        </div>
        <div class="score-display">
          <span class="score-number">{{ results.score }}%</span>
          <span class="score-status">{{ results.passed ? 'PASSED' : 'FAILED' }}</span>
        </div>
        <div class="score-details">
          <p>Correct Answers: <strong>{{ results.correct_answers }} / {{ results.total_questions }}</strong></p>
          <p>Pass Requirement: <strong>{{ results.quiz.pass_percentage }}%</strong></p>
        </div>

        <div v-if="results.passed && results.quiz?.certificate_enabled && results.student_quiz?.certificate_code" class="certificate-box">
          <p><strong>Certificate Generated</strong></p>
          <p>Certificate Code: <strong>{{ results.student_quiz.certificate_code }}</strong></p>
          <p v-if="results.student_quiz?.pass_notification_sent_at">Pass email sent to your account.</p>
          <p v-if="results.quiz?.pass_notification_cc_email">CC sent to: {{ results.quiz.pass_notification_cc_email }}</p>
        </div>
        <div v-else-if="results.passed && !results.quiz?.certificate_enabled" class="certificate-box">
          <p><strong>Certificate Disabled</strong></p>
          <p>This quiz does not issue certificates, but your pass result is recorded.</p>
        </div>
      </div>

      <div class="results-actions">
        <button
          v-if="results.passed && results.quiz?.certificate_enabled && results.student_quiz?.certificate_code"
          @click="viewCertificate"
          class="btn-certificate"
        >
          View Certificate
        </button>
        <button 
          v-if="canRetake" 
          @click="retakeQuiz" 
          class="btn-retry">
          Retake Quiz
        </button>
        <button @click="backToQuizzes" class="btn-back">Back to Quizzes</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();
const quizId = route.params.id;

const results = ref(null);
const loading = ref(false);

const canRetake = computed(() => {
  if (!results.value) return false;
  const { quiz, student_quiz } = results.value;

  if (results.value.passed) {
    return false;
  }

  // Allow retake if: no attempt limit set OR attempts remaining
  if (quiz.attempts_allowed === 0) {
    return true; // unlimited attempts
  }

  return student_quiz.attempt_number < quiz.attempts_allowed;
});

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

const viewCertificate = () => {
  router.push({ name: 'quiz-certificate', params: { id: quizId } });
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

.attempt-badge {
  display: inline-block;
  background-color: rgba(255, 255, 255, 0.2);
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-size: 0.9rem;
  margin-bottom: 1rem;
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

.certificate-box {
  margin-top: 1.25rem;
  border: 1px solid rgba(255, 255, 255, 0.35);
  background: rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  padding: 0.85rem;
  text-align: left;
}

.certificate-box p {
  margin: 0.35rem 0;
}

.results-actions {
  padding: 2rem;
  display: flex;
  gap: 1rem;
}

.btn-retry,
.btn-back,
.btn-certificate {
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

.btn-certificate {
  background-color: #1f8a5b;
  color: #fff;
}

.btn-certificate:hover {
  background-color: #19764d;
}

.btn-back {
  background-color: #cbd5e0;
  color: #333;
}

.btn-back:hover {
  background-color: #a0aec0;
}
</style>

