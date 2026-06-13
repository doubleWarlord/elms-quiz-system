<template>
  <div class="quiz-take">
    <div class="quiz-header">
      <h1>{{ quiz?.title }}</h1>
      <div class="quiz-progress">
        <span>Question {{ currentQuestionIndex + 1 }} of {{ totalQuestions }}</span>
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: progressPercentage + '%' }"></div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading">Loading question...</div>
    <div v-else-if="currentQuestion" class="question-container">
      <div class="question-content">
        <h2>{{ currentQuestion.question_text }}</h2>

        <!-- Display media if available -->
        <div v-if="currentQuestion.media && currentQuestion.media.length > 0" class="media-section">
          <div v-for="media in currentQuestion.media" :key="media.id" class="media-item">
            <div v-if="media.media_type === 'image'" class="media-image">
              <img :src="media.media_path" :alt="media.description" />
            </div>
            <div v-if="media.media_type === 'video'" class="media-video">
              <iframe
                width="100%"
                height="300"
                :src="getYoutubeEmbedUrl(media.media_url)"
                frameborder="0"
                allowfullscreen
              ></iframe>
            </div>
            <div v-if="media.media_type === 'text'" class="media-text">
              <p>{{ media.description }}</p>
            </div>
          </div>
        </div>

        <!-- Display answers based on question type -->
        <div class="answers-section">
          <div
            v-for="(answer, index) in currentQuestion.answers"
            :key="answer.id"
            class="answer-option"
            :class="{ selected: selectedAnswer === answer.id }"
          >
            <input
              :id="`answer-${answer.id}`"
              type="radio"
              :value="answer.id"
              v-model="selectedAnswer"
              :name="`question-${currentQuestion.id}`"
            />
            <label :for="`answer-${answer.id}`">{{ answer.answer_text }}</label>
          </div>
        </div>

        <div v-if="feedback" class="feedback" :class="feedbackClass">
          {{ feedback }}
        </div>
      </div>

      <div class="quiz-actions">
        <button @click="previousQuestion" class="btn-secondary" :disabled="currentQuestionIndex === 0">
          Previous
        </button>
        <button @click="submitAnswer" class="btn-primary" :disabled="!selectedAnswer || submitting">
          {{ submitting ? 'Submitting...' : 'Submit Answer' }}
        </button>
      </div>
    </div>

    <div v-else-if="quizCompleted" class="quiz-completed">
      <h2>Quiz Completed!</h2>
      <p>Click below to see your results.</p>
      <button @click="viewResults" class="btn-primary">View Results</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();
const quizId = route.params.id;

const quiz = ref(null);
const currentQuestion = ref(null);
const currentQuestionIndex = ref(0);
const loading = ref(false);
const submitting = ref(false);
const selectedAnswer = ref(null);
const feedback = ref('');
const feedbackClass = ref('');
const quizCompleted = ref(false);

const totalQuestions = computed(() => quiz.value?.questions?.length || 0);
const progressPercentage = computed(() => {
  return totalQuestions.value > 0 ? ((currentQuestionIndex.value + 1) / totalQuestions.value) * 100 : 0;
});

onMounted(async () => {
  await fetchQuiz();
  await startQuiz();
});

const fetchQuiz = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/quizzes/${quizId}`);
    quiz.value = response.data;
  } catch (error) {
    console.error('Failed to fetch quiz:', error);
  } finally {
    loading.value = false;
  }
};

const startQuiz = async () => {
  try {
    await axios.post(`/quizzes/${quizId}/start`);
  } catch (error) {
    console.error('Failed to start quiz:', error);
  }
};

const getCurrentQuestion = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/quizzes/${quizId}/current-question`);
    if (response.data.message) {
      quizCompleted.value = true;
    } else {
      currentQuestion.value = response.data;
      selectedAnswer.value = null;
      feedback.value = '';
    }
  } catch (error) {
    console.error('Failed to get current question:', error);
  } finally {
    loading.value = false;
  }
};

const submitAnswer = async () => {
  if (!selectedAnswer.value) return;

  submitting.value = true;
  try {
    const response = await axios.post(`/quizzes/${quizId}/submit-answer`, {
      question_id: currentQuestion.value.id,
      answer_id: selectedAnswer.value,
    });

    feedback.value = response.data.message;
    feedbackClass.value = response.data.is_correct ? 'feedback-correct' : 'feedback-incorrect';

    if (response.data.is_correct) {
      setTimeout(() => {
        currentQuestionIndex.value++;
        getCurrentQuestion();
      }, 1500);
    }
  } catch (error) {
    feedback.value = 'Error submitting answer';
    feedbackClass.value = 'feedback-incorrect';
  } finally {
    submitting.value = false;
  }
};

const previousQuestion = () => {
  if (currentQuestionIndex.value > 0) {
    currentQuestionIndex.value--;
    currentQuestion.value = quiz.value.questions[currentQuestionIndex.value];
    selectedAnswer.value = null;
    feedback.value = '';
  }
};

const viewResults = () => {
  router.push({ name: 'quiz-results', params: { id: quizId } });
};

const getYoutubeEmbedUrl = (url) => {
  if (!url) return '';
  const videoId = url.split('v=')[1] || url.split('/')[url.split('/').length - 1];
  return `https://www.youtube.com/embed/${videoId}`;
};
</script>

<style scoped>
.quiz-take {
  padding: 2rem;
  max-width: 800px;
  margin: 0 auto;
}

.quiz-header {
  margin-bottom: 2rem;
}

.quiz-header h1 {
  color: #333;
  margin-bottom: 1rem;
}

.quiz-progress {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background-color: #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background-color: #667eea;
  transition: width 0.3s ease;
}

.loading,
.quiz-completed {
  text-align: center;
  padding: 3rem;
  background: white;
  border-radius: 8px;
  color: #666;
}

.question-container {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.question-content h2 {
  color: #333;
  margin-bottom: 1.5rem;
}

.media-section {
  margin-bottom: 2rem;
  padding: 1rem;
  background-color: #f9f9f9;
  border-radius: 4px;
}

.media-item {
  margin-bottom: 1rem;
}

.media-image img {
  max-width: 100%;
  height: auto;
  border-radius: 4px;
}

.media-video {
  margin: 1rem 0;
}

.media-text {
  line-height: 1.6;
  color: #555;
}

.answers-section {
  margin-bottom: 2rem;
}

.answer-option {
  display: flex;
  align-items: center;
  padding: 1rem;
  margin-bottom: 0.5rem;
  border: 2px solid #e0e0e0;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.answer-option:hover {
  border-color: #667eea;
  background-color: #f9f9f9;
}

.answer-option.selected {
  border-color: #667eea;
  background-color: #f0f0ff;
}

.answer-option input {
  margin-right: 1rem;
  cursor: pointer;
}

.answer-option label {
  cursor: pointer;
  flex: 1;
}

.feedback {
  padding: 1rem;
  border-radius: 4px;
  margin-bottom: 1rem;
  font-weight: 500;
}

.feedback-correct {
  background-color: #c6f6d5;
  color: #22543d;
  border: 1px solid #9ae6b4;
}

.feedback-incorrect {
  background-color: #fed7d7;
  color: #742a2a;
  border: 1px solid #fc8181;
}

.quiz-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.btn-primary,
.btn-secondary {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary {
  background-color: #667eea;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #5568d3;
}

.btn-secondary {
  background-color: #cbd5e0;
  color: #333;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #a0aec0;
}

.btn-primary:disabled,
.btn-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
