<template>
  <div class="certificate-page">
    <div class="toolbar">
      <button class="btn-back" @click="goBack">Back to Results</button>
      <button class="btn-download" @click="downloadPdf">Download PDF</button>
      <button class="btn-print" @click="printCertificate">Print / Save PDF</button>
    </div>

    <div v-if="loading" class="loading">Loading certificate...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <div v-else-if="certificateData" class="certificate-card" id="certificate-card">
      <div class="certificate-border">
        <img v-if="certificateData.certificate.logo_url" :src="certificateData.certificate.logo_url" alt="Certificate logo" class="logo" />
        <p class="overline">Certificate</p>
        <h1>{{ certificateData.certificate.title }}</h1>
        <p class="body">{{ certificateData.certificate.body }}</p>

        <div class="meta">
          <p><strong>Student:</strong> {{ certificateData.student.name }}</p>
          <p><strong>Quiz:</strong> {{ certificateData.quiz.title }}</p>
          <p><strong>Score:</strong> {{ certificateData.student_quiz.score }}%</p>
          <p><strong>Certificate Code:</strong> {{ certificateData.student_quiz.certificate_code }}</p>
          <p><strong>Completed:</strong> {{ formatDate(certificateData.student_quiz.completed_at) }}</p>
        </div>

        <p class="footer">{{ certificateData.certificate.footer }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const quizId = route.params.id;

const loading = ref(false);
const error = ref('');
const certificateData = ref(null);

onMounted(async () => {
  await fetchCertificate();
});

const fetchCertificate = async () => {
  loading.value = true;
  error.value = '';

  try {
    const response = await axios.get(`/quizzes/${quizId}/certificate`);
    certificateData.value = response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load certificate.';
  } finally {
    loading.value = false;
  }
};

const printCertificate = () => {
  window.print();
};

const downloadPdf = async () => {
  try {
    const response = await axios.get(`/quizzes/${quizId}/certificate/download`, {
      responseType: 'blob',
    });

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `certificate-quiz-${quizId}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to download certificate PDF.';
  }
};

const goBack = () => {
  router.push({ name: 'quiz-results', params: { id: quizId } });
};

const formatDate = (dateTime) => {
  if (!dateTime) return '-';
  return new Date(dateTime).toLocaleString();
};
</script>

<style scoped>
.certificate-page {
  padding: 1.5rem;
  max-width: 980px;
  margin: 0 auto;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.btn-back,
.btn-download,
.btn-print {
  border: none;
  border-radius: 8px;
  padding: 0.65rem 1rem;
  cursor: pointer;
}

.btn-back {
  background: #d8dde8;
  color: #1d2433;
}

.btn-print {
  background: #2f4df2;
  color: #fff;
}

.btn-download {
  background: #1f8a5b;
  color: #fff;
}

.loading,
.error {
  text-align: center;
  padding: 2rem;
}

.error {
  color: #b42318;
}

.certificate-card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  padding: 1.2rem;
}

.certificate-border {
  border: 8px double #152447;
  border-radius: 8px;
  padding: 2rem;
  text-align: center;
}

.logo {
  max-width: 180px;
  max-height: 70px;
  object-fit: contain;
  display: block;
  margin: 0 auto 0.8rem;
}

.overline {
  text-transform: uppercase;
  letter-spacing: 0.2rem;
  color: #3f4c68;
  margin: 0;
}

h1 {
  margin: 0.8rem 0 1.1rem;
  color: #10192f;
}

.body {
  max-width: 760px;
  margin: 0 auto 1.4rem;
  line-height: 1.6;
  color: #2b3448;
}

.meta {
  text-align: left;
  margin: 1rem auto;
  max-width: 520px;
}

.meta p {
  margin: 0.35rem 0;
}

.footer {
  margin-top: 1.5rem;
  color: #2e3d64;
  font-weight: 600;
}

@media print {
  .toolbar {
    display: none;
  }

  .certificate-page {
    padding: 0;
  }

  .certificate-card {
    box-shadow: none;
    border-radius: 0;
  }
}
</style>
