<template>
  <div class="quiz-create">
    <h1>{{ isEdit ? 'Edit Quiz' : 'Create Quiz' }}</h1>
    <form @submit.prevent="handleSubmit" class="quiz-form">
      <div class="form-group">
        <label for="title">Quiz Title:</label>
        <input v-model="form.title" type="text" id="title" class="form-control" required />
      </div>

      <div class="form-group">
        <label for="description">Description:</label>
        <textarea v-model="form.description" id="description" class="form-control" rows="4"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="pass_percentage">Pass Percentage:</label>
          <input v-model.number="form.pass_percentage" type="number" id="pass_percentage" class="form-control" min="0" max="100" required />
        </div>
        <div class="form-group">
          <label for="attempts_allowed">Attempts Allowed:</label>
          <input v-model.number="form.attempts_allowed" type="number" id="attempts_allowed" class="form-control" min="0" required />
        </div>
      </div>

      <div class="form-group">
        <label for="is_published">
          <input v-model="form.is_published" type="checkbox" id="is_published" />
          Publish Quiz
        </label>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-primary" :disabled="submitting">{{ submitting ? 'Saving...' : 'Save Quiz' }}</button>
        <button type="button" @click="cancel" class="btn-secondary">Cancel</button>
      </div>
    </form>

    <div v-if="error" class="alert alert-error">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();
const quizId = route.params.id;
const isEdit = !!quizId;

const form = ref({
  title: '',
  description: '',
  pass_percentage: 70,
  attempts_allowed: 0,
  is_published: false,
});

const submitting = ref(false);
const error = ref('');

onMounted(async () => {
  if (isEdit) {
    await fetchQuiz();
  }
});

const fetchQuiz = async () => {
  try {
    const response = await axios.get(`/quizzes/${quizId}`);
    form.value = response.data;
  } catch (err) {
    error.value = 'Failed to load quiz';
  }
};

const handleSubmit = async () => {
  submitting.value = true;
  error.value = '';

  try {
    if (isEdit) {
      await axios.put(`/quizzes/${quizId}`, form.value);
    } else {
      await axios.post('/quizzes', form.value);
    }
    router.push({ name: 'quiz-list' });
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save quiz';
  } finally {
    submitting.value = false;
  }
};

const cancel = () => {
  router.push({ name: 'quiz-list' });
};
</script>

<style scoped>
.quiz-create {
  padding: 2rem;
  max-width: 600px;
  margin: 0 auto;
}

.quiz-create h1 {
  color: #333;
  margin-bottom: 2rem;
}

.quiz-form {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  color: #555;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
  font-family: inherit;
}

.form-control:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
}

textarea.form-control {
  resize: vertical;
}

.form-actions {
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

.btn-secondary:hover {
  background-color: #a0aec0;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.alert {
  margin-top: 1rem;
  padding: 0.75rem;
  border-radius: 4px;
}

.alert-error {
  background-color: #fee;
  color: #c33;
  border: 1px solid #fcc;
}
</style>
