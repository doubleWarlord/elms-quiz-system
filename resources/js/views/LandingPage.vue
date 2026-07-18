<template>
  <div class="landing">
    <!-- Hero -->
    <section class="hero">
      <div class="hero-inner">
        <h1>Empower Your Learning Journey</h1>
        <p class="hero-sub">Access expert-led courses, interactive quizzes, and earn certificates — all in one place.</p>
        <div class="hero-actions">
          <router-link to="/auth/login" class="btn-hero-primary">Get Started</router-link>
          <router-link to="/auth/register" class="btn-hero-secondary">Create Account</router-link>
        </div>
      </div>
    </section>

    <!-- Stats bar -->
    <section class="stats-bar">
      <div class="stat"><span class="stat-num">{{ stats.courses }}+</span><span class="stat-label">Courses</span></div>
      <div class="stat"><span class="stat-num">{{ stats.students }}+</span><span class="stat-label">Students</span></div>
      <div class="stat"><span class="stat-num">{{ stats.certificates }}+</span><span class="stat-label">Certificates Issued</span></div>
    </section>

    <!-- Featured Courses -->
    <section class="section featured-section">
      <h2>Featured Courses</h2>
      <div v-if="loading" class="loading">Loading courses...</div>
      <div v-else-if="courses.length === 0" class="empty">No published courses yet.</div>
      <div v-else class="course-grid">
        <div v-for="course in courses" :key="course.id" class="course-card">
          <div class="course-card-cover" :style="course.cover_image ? `background-image:url('${course.cover_image}')` : ''">
            <span v-if="!course.cover_image" class="cover-placeholder">{{ course.title[0] }}</span>
          </div>
          <div class="course-card-body">
            <span class="course-tag" v-if="course.category">{{ course.category }}</span>
            <h3>{{ course.title }}</h3>
            <p class="course-desc">{{ (course.description || '').slice(0, 80) }}{{ (course.description || '').length > 80 ? '…' : '' }}</p>
            <div class="course-meta">
              <span>{{ levelLabel(course.level) }}</span>
              <span v-if="course.duration_minutes">{{ course.duration_minutes }} min</span>
              <span>{{ course.enrollments_count || 0 }} enrolled</span>
            </div>
          </div>
          <div class="course-card-footer">
            <router-link :to="{ name: 'login' }" class="btn-enroll">Enroll — Login to Access</router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
      <p>© {{ new Date().getFullYear() }} ELMS. All rights reserved.</p>
      <router-link to="/auth/login">Login</router-link>
      <router-link to="/auth/register">Register</router-link>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const courses = ref([]);
const loading = ref(false);
const stats = ref({ courses: 0, students: 0, certificates: 0 });

onMounted(async () => {
  loading.value = true;
  try {
    const res = await axios.get('/courses');
    courses.value = Array.isArray(res.data) ? res.data : [];
    stats.value.courses = courses.value.length;
    stats.value.students = courses.value.reduce((s, c) => s + (c.enrollments_count || 0), 0);
    stats.value.certificates = Math.floor(stats.value.students * 0.6);
  } catch {
    courses.value = [];
  } finally {
    loading.value = false;
  }
});

const levelLabel = (level) => {
  const map = { beginner: 'Beginner', intermediate: 'Intermediate', advanced: 'Advanced' };
  return map[level] || level;
};
</script>

<style scoped>
.landing { background: #f2f5fc; min-height: 100vh; }

.hero {
  background: linear-gradient(135deg, #1b2f55 0%, #2f4df2 100%);
  color: #fff;
  padding: 5rem 2rem 4rem;
  text-align: center;
}
.hero-inner { max-width: 700px; margin: 0 auto; }
.hero h1 { font-size: 2.6rem; margin: 0 0 1rem; }
.hero-sub { font-size: 1.15rem; color: #c8d8ff; margin-bottom: 2rem; }
.hero-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
.btn-hero-primary { background: #fff; color: #1b2f55; font-weight: 700; border-radius: 8px; padding: 0.8rem 2rem; text-decoration: none; }
.btn-hero-secondary { background: transparent; border: 2px solid #fff; color: #fff; border-radius: 8px; padding: 0.8rem 2rem; text-decoration: none; }

.stats-bar {
  display: flex; justify-content: center; gap: 3rem;
  background: #fff; padding: 1.5rem 2rem;
  box-shadow: 0 2px 8px rgba(27,47,85,.07);
}
.stat { display: flex; flex-direction: column; align-items: center; }
.stat-num { font-size: 1.8rem; font-weight: 800; color: #2f4df2; }
.stat-label { font-size: 0.85rem; color: #5d6b8a; }

.section { max-width: 1100px; margin: 0 auto; padding: 3rem 1.5rem; }
.section h2 { font-size: 1.6rem; color: #1b2f55; margin-bottom: 1.5rem; }

.course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; }

.course-card {
  background: #fff; border-radius: 12px; overflow: hidden;
  box-shadow: 0 2px 8px rgba(27,47,85,.08);
  display: flex; flex-direction: column;
  transition: transform .2s, box-shadow .2s;
}
.course-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(27,47,85,.14); }

.course-card-cover {
  height: 140px; background: #dde5ff; background-size: cover; background-position: center;
  display: flex; align-items: center; justify-content: center;
}
.cover-placeholder { font-size: 2.5rem; font-weight: 800; color: #4760ff; opacity: .4; }
.course-card-body { padding: 1rem; flex: 1; }
.course-tag { background: #eef2ff; color: #4760ff; font-size: .75rem; border-radius: 4px; padding: .2rem .5rem; }
.course-card-body h3 { margin: .5rem 0 .4rem; font-size: 1rem; color: #1b2f55; }
.course-desc { font-size: .87rem; color: #5d6b8a; margin: 0 0 .6rem; }
.course-meta { display: flex; gap: .6rem; flex-wrap: wrap; font-size: .78rem; color: #8090aa; }
.course-card-footer { padding: .75rem 1rem; border-top: 1px solid #f0f3fa; }
.btn-enroll { display: block; text-align: center; background: #2f4df2; color: #fff; border-radius: 6px; padding: .55rem; font-size: .88rem; text-decoration: none; }
.btn-enroll:hover { background: #1f3de0; }

.loading, .empty { text-align: center; color: #8090aa; padding: 2rem; }

.landing-footer {
  background: #1b2f55; color: #8cb3e0; text-align: center; padding: 1.5rem;
  display: flex; gap: 1.5rem; justify-content: center; align-items: center;
}
.landing-footer a { color: #8cb3e0; text-decoration: none; }
.landing-footer a:hover { color: #fff; }

@media (max-width: 600px) {
  .hero h1 { font-size: 1.8rem; }
  .stats-bar { gap: 1.5rem; }
}
</style>
