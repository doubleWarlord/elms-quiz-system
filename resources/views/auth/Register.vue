<template>
  <div class="register-form">
    <h1>Register</h1>
    <form @submit.prevent="handleRegister">
      <div class="form-group">
        <label for="name">Name:</label>
        <input
          v-model="form.name"
          type="text"
          id="name"
          class="form-control"
          required
        />
      </div>
      <div class="form-group">
        <label for="email">Email:</label>
        <input
          v-model="form.email"
          type="email"
          id="email"
          class="form-control"
          required
        />
      </div>
      <div class="form-group">
        <label for="role">Role:</label>
        <select v-model="form.role" id="role" class="form-control" required>
          <option value="student">Student</option>
          <option value="teacher">Teacher</option>
        </select>
      </div>
      <div class="form-group">
        <label for="password">Password:</label>
        <input
          v-model="form.password"
          type="password"
          id="password"
          class="form-control"
          required
        />
      </div>
      <div class="form-group">
        <label for="password_confirmation">Confirm Password:</label>
        <input
          v-model="form.password_confirmation"
          type="password"
          id="password_confirmation"
          class="form-control"
          required
        />
      </div>
      <button type="submit" class="btn-primary" :disabled="loading">
        {{ loading ? 'Registering...' : 'Register' }}
      </button>
    </form>
    <p class="form-link">
      Already have an account? <router-link to="/auth/login">Login here</router-link>
    </p>
    <div v-if="error" class="alert alert-error">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const form = ref({
  name: '',
  email: '',
  role: 'student',
  password: '',
  password_confirmation: '',
});
const loading = ref(false);
const error = ref('');

const handleRegister = async () => {
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'Passwords do not match';
    return;
  }

  loading.value = true;
  error.value = '';
  try {
    const response = await axios.post('/auth/register', form.value);
    localStorage.setItem('token', response.data.token);
    axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
    router.push({ name: 'quiz-list' });
  } catch (err) {
    error.value = err.response?.data?.message || 'Registration failed';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.register-form {
  text-align: center;
}

h1 {
  margin-bottom: 2rem;
  color: #333;
}

.form-group {
  margin-bottom: 1.5rem;
  text-align: left;
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
}

.form-control:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
}

.btn-primary {
  width: 100%;
  padding: 0.75rem;
  background-color: #667eea;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background-color: #5568d3;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-link {
  margin-top: 1rem;
  color: #666;
}

.form-link a {
  color: #667eea;
  text-decoration: none;
}

.form-link a:hover {
  text-decoration: underline;
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
