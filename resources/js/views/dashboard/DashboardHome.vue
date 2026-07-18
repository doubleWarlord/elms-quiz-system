<template>
  <section class="dashboard-home">
    <div v-if="!profile" class="loading">Loading dashboard…</div>
    <template v-else>

      <!-- Quick action cards -->
      <div class="quick-cards">
        <router-link class="qcard" to="/courses">
          <span class="qcard-icon">📚</span>
          <span>Browse Courses</span>
        </router-link>
        <router-link class="qcard" to="/courses/create" v-if="isTeacher || isAdmin">
          <span class="qcard-icon">➕</span>
          <span>Create Course</span>
        </router-link>
        <router-link class="qcard" to="/quizzes">
          <span class="qcard-icon">📝</span>
          <span>Quizzes</span>
        </router-link>
        <router-link class="qcard" to="/quizzes/create" v-if="isTeacher || isAdmin">
          <span class="qcard-icon">✏️</span>
          <span>Create Quiz</span>
        </router-link>
        <router-link class="qcard" to="/admin/users" v-if="isAdmin">
          <span class="qcard-icon">👥</span>
          <span>Manage Users</span>
        </router-link>
        <router-link class="qcard" to="/admin/quizzes" v-if="isAdmin">
          <span class="qcard-icon">⚙️</span>
          <span>Manage Quizzes</span>
        </router-link>
      </div>

      <!-- My enrolled courses (students) -->
      <template v-if="isStudent">
        <h2 class="section-title">My Courses</h2>
        <div v-if="loadingCourses" class="loading">Loading courses…</div>
        <div v-else-if="enrolledCourses.length === 0" class="empty-state">
          You haven't enrolled in any courses yet.
          <router-link to="/courses" class="browse-link">Browse courses →</router-link>
        </div>
        <div v-else class="enrolled-grid">
          <router-link
            v-for="course in enrolledCourses"
            :key="course.id"
            :to="{ name: 'course-player', params: { id: course.id } }"
            class="enrolled-card"
          >
            <div class="enrolled-cover" :style="course.cover_image ? `background-image:url('${course.cover_image}')` : ''">
              <span v-if="!course.cover_image" class="cover-placeholder">{{ course.title[0] }}</span>
            </div>
            <div class="enrolled-body">
              <p class="enrolled-cat" v-if="course.category">{{ course.category }}</p>
              <h3>{{ course.title }}</h3>
              <div class="progress-bar">
                <div class="progress-fill" :style="{ width: (course.progress_percent || 0) + '%' }"></div>
              </div>
              <p class="progress-label">{{ course.completed_lessons || 0 }} / {{ course.total_lessons || 0 }} lessons · {{ course.progress_percent || 0 }}%</p>
              <span v-if="course.enrollment?.completed_at" class="badge-complete">✓ Completed</span>
            </div>
          </router-link>
        </div>
      </template>

      <!-- Teacher: my courses -->
      <template v-if="isTeacher || isAdmin">
        <h2 class="section-title">My Courses</h2>
        <div v-if="loadingCourses" class="loading">Loading…</div>
        <div v-else-if="myCourses.length === 0" class="empty-state">
          No courses yet. <router-link to="/courses/create" class="browse-link">Create one →</router-link>
        </div>
        <div v-else class="enrolled-grid">
          <router-link
            v-for="course in myCourses"
            :key="course.id"
            :to="{ name: 'course-edit', params: { id: course.id } }"
            class="enrolled-card"
          >
            <div class="enrolled-cover" :style="course.cover_image ? `background-image:url('${course.cover_image}')` : ''">
              <span v-if="!course.cover_image" class="cover-placeholder">{{ course.title[0] }}</span>
            </div>
            <div class="enrolled-body">
              <p class="enrolled-cat" v-if="course.category">{{ course.category }}</p>
              <h3>{{ course.title }}</h3>
              <p class="enrolled-meta">{{ course.enrollments_count || 0 }} enrolled · {{ course.is_published ? 'Published' : 'Draft' }}</p>
            </div>
          </router-link>
        </div>
      </template>

    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const profile = ref(null);
const enrolledCourses = ref([]);
const myCourses = ref([]);
const loadingCourses = ref(false);

const isAdmin = computed(() => profile.value?.role === 'admin');
const isTeacher = computed(() => profile.value?.role === 'teacher');
const isStudent = computed(() => profile.value?.role === 'student');

onMounted(async () => {
  const saved = localStorage.getItem('user');
  if (saved) { try { profile.value = JSON.parse(saved); } catch {} }

  loadingCourses.value = true;
  try {
    if (profile.value?.role === 'student') {
      const res = await axios.get('/courses/enrolled');
      enrolledCourses.value = Array.isArray(res.data) ? res.data : [];
    } else {
      const res = await axios.get('/courses');
      myCourses.value = Array.isArray(res.data) ? res.data : [];
    }
  } catch {} finally { loadingCourses.value = false; }
});
</script>

<style scoped>
.dashboard-home { padding: .5rem; }
.section-title { font-size: 1.15rem; color: #1b2f55; margin: 1.5rem 0 .75rem; }
.loading { color: #5d7088; padding: 1rem; }
.empty-state { color: #8090aa; padding: 1rem; }
.browse-link { color: #4760ff; text-decoration: none; font-weight: 600; margin-left: .4rem; }

.quick-cards { display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: .5rem; }
.qcard { display: flex; align-items: center; gap: .5rem; background: #fff; border: 1px solid #dde5ef; border-radius: 10px; padding: .75rem 1.1rem; text-decoration: none; color: #1f2d3d; font-weight: 600; font-size: .9rem; box-shadow: 0 1px 4px rgba(27,39,53,.06); }
.qcard:hover { border-color: #7ea5d6; background: #f5f8ff; }
.qcard-icon { font-size: 1.1rem; }

.enrolled-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem; }
.enrolled-card { background: #fff; border-radius: 12px; overflow: hidden; text-decoration: none; color: #1f2d3d; box-shadow: 0 2px 8px rgba(27,47,85,.07); border: 1px solid #e8edf5; display: flex; flex-direction: column; }
.enrolled-card:hover { border-color: #b3c4e8; box-shadow: 0 4px 14px rgba(27,47,85,.12); }
.enrolled-cover { height: 100px; background: #dde5ff; background-size: cover; background-position: center; display: flex; align-items: center; justify-content: center; }
.cover-placeholder { font-size: 2rem; font-weight: 800; color: #4760ff; opacity: .4; }
.enrolled-body { padding: .85rem; flex: 1; }
.enrolled-cat { font-size: .73rem; color: #4760ff; background: #eef2ff; border-radius: 4px; display: inline-block; padding: .1rem .4rem; margin-bottom: .4rem; }
.enrolled-body h3 { margin: 0 0 .65rem; font-size: .97rem; }
.enrolled-meta { margin: 0; color: #8090aa; font-size: .82rem; }
.progress-bar { height: 5px; background: #e8edf5; border-radius: 3px; overflow: hidden; margin-bottom: .3rem; }
.progress-fill { height: 100%; background: #4760ff; transition: width .4s; }
.progress-label { margin: 0; font-size: .78rem; color: #8090aa; }
.badge-complete { display: inline-block; margin-top: .4rem; background: #d1fae5; color: #065f46; font-size: .75rem; border-radius: 4px; padding: .15rem .5rem; }
</style>
