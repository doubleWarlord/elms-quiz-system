<template>
  <div class="quiz-take">
    <div class="quiz-header">
      <h1>{{ quiz?.title }}</h1>
      <div class="quiz-attempt-info" v-if="studentQuiz">
        <span>Attempt {{ studentQuiz.attempt_number }}<span v-if="quiz?.attempts_allowed > 0"> of {{ quiz.attempts_allowed }}</span></span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" :style="{ width: progressPercentage + '%' }"></div>
      </div>
    </div>

    <div v-if="loading" class="loading">Loading question...</div>
    <div v-else-if="currentQuestion" class="question-container">
      <div class="question-content">
        <h2 v-if="currentStep === 'read'">Read First</h2>
        <h2 v-else>{{ currentQuestion.question_text }}</h2>

        <div v-if="currentQuestion.media && currentQuestion.media.length > 0" class="media-section">
          <div v-for="media in currentQuestion.media" :key="media.id" class="media-item">
            <div v-if="media.media_type === 'image'" class="media-image">
              <img :src="media.media_path || media.media_url" :alt="media.description || 'Question image'" />
            </div>
            <div v-if="media.media_type === 'video'" class="media-video">
              <div class="video-player-shell" :class="videoPlayerClass(media)">
                <div v-if="media.player_caption" class="video-player-caption">
                  {{ media.player_caption }}
                </div>
                <video
                  v-if="isTrackableVideo(media)"
                  controls
                  class="media-video-player"
                  :poster="media.poster_path || undefined"
                  :controlsList="media.requires_completion ? 'nodownload noplaybackrate noremoteplayback' : undefined"
                  :disablePictureInPicture="media.requires_completion ? true : undefined"
                  @ended="markVideoCompleted(media.id)"
                  @timeupdate="trackVideoProgress(media.id, $event)"
                  @seeking="preventVideoSkip(media.id, $event)"
                >
                <source :src="media.media_path || media.media_url" />
                Your browser does not support video playback.
              </video>
                <iframe v-else-if="media.media_url" width="100%" height="300" :src="getYoutubeEmbedUrl(media.media_url)" frameborder="0" allowfullscreen></iframe>
              </div>
              <p v-if="media.requires_completion && !isTrackableVideo(media)" class="watch-note">
                This video is marked as required, but external embeds cannot be fully tracked. Upload the video file directly for strict completion control.
              </p>
            </div>
            <div v-if="media.media_type === 'audio'" class="media-audio">
              <audio controls class="media-audio-player" :src="media.media_path || media.media_url"></audio>
            </div>
            <div v-if="media.media_type === 'text' || media.media_type === 'paragraph'" class="media-text">
              <p>{{ media.description }}</p>
            </div>
            <div v-if="media.media_type === 'document'" class="media-doc">
              <a :href="media.media_path || media.media_url" target="_blank" rel="noopener noreferrer">Open document</a>
            </div>
            <div v-if="media.media_type === 'slide'" class="media-slide">
              <p v-if="media.description" class="slide-content-text">{{ media.description }}</p>
              <iframe
                v-if="isPdfSource(media.media_path || media.media_url)"
                class="media-slide-frame"
                :src="media.media_path || media.media_url"
              ></iframe>
              <a
                v-else-if="media.media_path || media.media_url"
                :href="media.media_path || media.media_url"
                target="_blank"
                rel="noopener noreferrer"
              >
                Open slide file
              </a>
            </div>
          </div>
        </div>

        <div v-if="currentStep === 'read'" class="reading-panel">
          <p class="reading-question">{{ currentQuestion.question_text }}</p>
          <p class="reading-note">Please read/watch/listen carefully before continuing to answer.</p>
        </div>

        <div v-if="currentStep === 'answer'" class="answers-section">
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
        <p v-if="currentStep === 'read' && hasPendingVideoWatch" class="watch-note">
          Please watch the video until the end before continuing.
        </p>
        <button
          v-if="currentStep === 'read'"
          @click="currentStep = 'answer'"
          class="btn-secondary"
          :disabled="hasPendingVideoWatch"
        >
          Continue to Questions
        </button>
        <button v-if="currentStep === 'answer'" @click="submitAnswer" class="btn-primary" :disabled="!selectedAnswer || submitting">
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
const studentQuiz = ref(null);
const loading = ref(false);
const submitting = ref(false);
const selectedAnswer = ref(null);
const feedback = ref('');
const feedbackClass = ref('');
const quizCompleted = ref(false);
const currentStep = ref('read');
const requiredVideoIds = ref([]);
const watchedVideoIds = ref([]);
const videoProgressMap = ref({});

const totalQuestions = computed(() => quiz.value?.questions?.length || 0);
  const currentQuestionIndex = computed(() => {
    if (!quiz.value?.questions?.length || !currentQuestion.value) {
      return 0;
    }

    const index = quiz.value.questions.findIndex((question) => question.id === currentQuestion.value.id);
    return index >= 0 ? index : 0;
  });

const progressPercentage = computed(() => {
  return totalQuestions.value > 0 ? ((currentQuestionIndex.value + 1) / totalQuestions.value) * 100 : 0;
});

const hasPendingVideoWatch = computed(() => {
  if (requiredVideoIds.value.length === 0) {
    return false;
  }

  return watchedVideoIds.value.length < requiredVideoIds.value.length;
});

onMounted(async () => {
  await Promise.all([fetchQuiz(), startQuiz()]);
  await getCurrentQuestion();
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
    const response = await axios.post(`/quizzes/${quizId}/start`);
    studentQuiz.value = response.data;
  } catch (error) {
    const message = error.response?.data?.message || '';

    if (message.includes('already passed')) {
      router.push({ name: 'quiz-results', params: { id: quizId } });
      return;
    }

    console.error('Failed to start quiz:', error);
  }
};

const getCurrentQuestion = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/quizzes/${quizId}/current-question`);
    if (response.data.message) {
      currentQuestion.value = null;
      quizCompleted.value = true;
      requiredVideoIds.value = [];
      watchedVideoIds.value = [];
    } else {
      quizCompleted.value = false;
      currentQuestion.value = response.data;
      currentStep.value = 'read';
      selectedAnswer.value = null;
      feedback.value = '';
      initializeVideoRequirements();
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

    setTimeout(() => {
      getCurrentQuestion();
    }, 1200);
  } catch (error) {
    feedback.value = 'Error submitting answer';
    feedbackClass.value = 'feedback-incorrect';
  } finally {
    submitting.value = false;
  }
};

const viewResults = () => {
  router.push({ name: 'quiz-results', params: { id: quizId } });
};

const isTrackableVideo = (media) => {
  if (media.media_path) {
    return true;
  }

  return /\.(mp4|webm|ogg|mov)(\?|#|$)/i.test(media.media_url || '');
};

const initializeVideoRequirements = () => {
  const mediaItems = currentQuestion.value?.media || [];
  requiredVideoIds.value = mediaItems
    .filter((media) => media.media_type === 'video' && media.requires_completion !== false && isTrackableVideo(media))
    .map((media) => media.id);
  watchedVideoIds.value = [];
  videoProgressMap.value = {};
};

const markVideoCompleted = (mediaId) => {
  if (!requiredVideoIds.value.includes(mediaId)) {
    return;
  }

  if (!watchedVideoIds.value.includes(mediaId)) {
    watchedVideoIds.value.push(mediaId);
  }
};

const trackVideoProgress = (mediaId, event) => {
  const videoElement = event.target;
  if (!videoElement || !requiredVideoIds.value.includes(mediaId)) {
    return;
  }

  const currentTime = videoElement.currentTime || 0;
  const maxWatched = videoProgressMap.value[mediaId] || 0;
  if (currentTime > maxWatched) {
    videoProgressMap.value[mediaId] = currentTime;
  }
};

const preventVideoSkip = (mediaId, event) => {
  const videoElement = event.target;
  if (!videoElement || !requiredVideoIds.value.includes(mediaId)) {
    return;
  }

  const maxWatched = videoProgressMap.value[mediaId] || 0;
  if (videoElement.currentTime > maxWatched + 1) {
    videoElement.currentTime = maxWatched;
  }
};

const videoPlayerClass = (media) => {
  return {
    framed: media.player_layout === 'framed',
    cinema: media.player_layout === 'cinema',
    compact: media.player_layout === 'compact',
  };
};

const getYoutubeEmbedUrl = (url) => {
  if (!url) return '';
  const videoId = url.split('v=')[1]?.split('&')[0] || url.split('/')[url.split('/').length - 1];
  return `https://www.youtube.com/embed/${videoId}`;
};

const isPdfSource = (source) => /\.pdf(\?|#|$)/i.test(source || '');

const isVideoRequirementMet = (media) => {
  if (media.media_type !== 'video') {
    return true;
  }

  if (media.requires_completion === false) {
    return true;
  }

  return watchedVideoIds.value.includes(media.id);
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

.quiz-attempt-info {
  font-size: 0.9rem;
  color: #667eea;
  font-weight: 500;
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

.video-player-shell {
  border-radius: 10px;
  overflow: hidden;
  padding: 0.75rem;
  background: #f4f6fb;
  border: 1px solid #dde3f0;
}

.video-player-shell.framed {
  box-shadow: inset 0 0 0 2px #d1d9ea;
}

.video-player-shell.cinema {
  background: #121826;
  border-color: #121826;
  color: #fff;
}

.video-player-shell.compact {
  padding: 0.35rem;
}

.video-player-caption {
  font-size: 0.9rem;
  font-weight: 600;
  color: inherit;
  margin-bottom: 0.6rem;
}

.media-video-player,
.media-audio-player {
  width: 100%;
}

.video-player-shell.cinema .media-video-player {
  border-radius: 6px;
}

.media-text {
  line-height: 1.6;
  color: #555;
}

.media-slide {
  border: 1px solid #dde3f0;
  border-radius: 8px;
  padding: 0.75rem;
  background: #f7f9ff;
}

.slide-content-text {
  margin-bottom: 0.6rem;
  color: #24324f;
  line-height: 1.55;
}

.media-slide-frame {
  width: 100%;
  min-height: 320px;
  border: 1px solid #d4dbeb;
  border-radius: 6px;
  background: #fff;
}

.reading-panel {
  border: 1px solid #e7e8f3;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  background: #fafbff;
}

.reading-question {
  font-size: 1.05rem;
  color: #202437;
}

.reading-note {
  color: #5a6077;
  margin: 0.5rem 0 0;
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
  align-items: center;
  margin-top: 2rem;
}

.watch-note {
  margin: 0;
  color: #a94442;
  font-size: 0.9rem;
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

