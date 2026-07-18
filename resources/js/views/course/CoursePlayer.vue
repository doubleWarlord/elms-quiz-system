<template>
  <div class="course-player">
    <div v-if="loading" class="loading">Loading course…</div>

    <template v-else-if="course">
      <!-- Enroll Banner (not yet enrolled) -->
      <div v-if="!enrollment" class="enroll-banner">
        <div class="enroll-banner-inner">
          <div>
            <h2>{{ course.title }}</h2>
            <p>{{ course.description }}</p>
            <p class="course-meta-line">
              <span>{{ course.category }}</span>
              <span>{{ levelLabel(course.level) }}</span>
              <span v-if="course.duration_minutes">{{ course.duration_minutes }} min</span>
            </p>
          </div>
          <button class="btn-enroll-big" @click="enroll" :disabled="enrolling">
            {{ enrolling ? 'Enrolling…' : 'Enroll Now — Free' }}
          </button>
        </div>

        <!-- Module overview for unenrolled -->
        <div class="module-overview">
          <h3>Course Content</h3>
          <div v-for="(mod, idx) in course.modules" :key="mod.id" class="mod-overview-item">
            <strong>{{ idx + 1 }}. {{ mod.title }}</strong>
            <span>{{ (mod.lessons || []).length }} lessons</span>
          </div>
        </div>
      </div>

      <!-- Course Player (enrolled) -->
      <div v-else class="player-layout">
        <!-- Sidebar -->
        <aside class="player-sidebar">
          <div class="sidebar-course-title">{{ course.title }}</div>
          <div class="sidebar-progress">
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
            </div>
            <p class="progress-text">{{ completedLessons }} / {{ totalLessons }} lessons complete ({{ progressPercent }}%)</p>
          </div>
          <div v-for="(mod, mIdx) in course.modules" :key="mod.id" class="sidebar-module">
            <div class="sidebar-module-title" @click="toggleSidebarModule(mod.id)">
              <span>{{ mIdx + 1 }}. {{ mod.title }}</span>
              <span>{{ isSidebarModuleOpen(mod.id) ? '▲' : '▼' }}</span>
            </div>
            <div v-if="isSidebarModuleOpen(mod.id)" class="sidebar-lessons">
              <div
                v-for="lesson in (mod.lessons || [])"
                :key="lesson.id"
                class="sidebar-lesson"
                :class="{ active: currentLesson?.id === lesson.id, completed: isLessonCompleted(lesson.id) }"
                @click="selectLesson(lesson)"
              >
                <span class="sidebar-lesson-icon">{{ isLessonCompleted(lesson.id) ? '✓' : '○' }}</span>
                <span class="sidebar-lesson-name">{{ lesson.title }}</span>
                <span v-if="lesson.quiz_id" class="quiz-badge">Quiz</span>
              </div>
            </div>
          </div>
        </aside>

        <!-- Main content area -->
        <main class="player-main">
          <div v-if="!currentLesson" class="no-lesson-selected">
            <h3>Select a lesson from the sidebar to begin.</h3>
          </div>
          <div v-else class="lesson-view">
            <h2>{{ currentLesson.title }}</h2>

            <!-- Video -->
            <div v-if="currentLesson.lesson_type === 'video'" class="lesson-media">
              <video
                v-if="currentLesson.media_path"
                controls
                class="lesson-video"
                :poster="currentLesson.poster_path || undefined"
                :controlsList="currentLesson.requires_completion ? 'nodownload noplaybackrate' : undefined"
                @ended="onVideoEnded"
                @timeupdate="trackProgress"
                @seeking="preventSkip"
              >
                <source :src="currentLesson.media_path" />
              </video>
              <iframe
                v-else-if="currentLesson.media_url"
                class="lesson-video"
                :src="youtubeEmbed(currentLesson.media_url)"
                frameborder="0" allowfullscreen
              ></iframe>
              <p v-if="currentLesson.requires_completion && !videoWatched" class="watch-note">
                ⚠ Please watch the full video to unlock completion.
              </p>
            </div>

            <!-- Audio -->
            <div v-else-if="currentLesson.lesson_type === 'audio'" class="lesson-media">
              <audio controls class="lesson-audio" :src="currentLesson.media_path || currentLesson.media_url"></audio>
            </div>

            <!-- Slide / text -->
            <div v-else-if="currentLesson.lesson_type === 'slide'" class="lesson-slide">
              <iframe
                v-if="currentLesson.media_path && isPdf(currentLesson.media_path)"
                class="lesson-slide-frame"
                :src="currentLesson.media_path"
              ></iframe>
              <a
                v-else-if="currentLesson.media_path || currentLesson.media_url"
                :href="currentLesson.media_path || currentLesson.media_url"
                target="_blank" rel="noopener"
                class="open-slide-link"
              >Open Slide File ↗</a>
            </div>

            <!-- Document -->
            <div v-else-if="currentLesson.lesson_type === 'document'" class="lesson-doc">
              <a :href="currentLesson.media_path || currentLesson.media_url" target="_blank" rel="noopener" class="open-doc-link">
                Open Document ↗
              </a>
            </div>

            <!-- Content text (all types can have text) -->
            <div v-if="currentLesson.content" class="lesson-content-text">
              <p>{{ currentLesson.content }}</p>
            </div>

            <!-- Linked Quiz -->
            <div v-if="currentLesson.quiz_id" class="lesson-quiz-block">
              <p>This lesson has an assessment quiz.</p>
              <router-link :to="{ name: 'quiz-take', params: { id: currentLesson.quiz_id } }" class="btn-take-quiz">
                Take Quiz
              </router-link>
            </div>

            <!-- Complete button -->
            <div class="lesson-footer">
              <button
                class="btn-complete"
                :disabled="(currentLesson.requires_completion && !videoWatched) || lessonCompleting"
                @click="markComplete"
              >
                {{ isLessonCompleted(currentLesson.id) ? '✓ Completed' : lessonCompleting ? 'Marking…' : 'Mark as Complete' }}
              </button>
              <button v-if="nextLesson" class="btn-next" @click="selectLesson(nextLesson)">
                Next: {{ nextLesson.title }} →
              </button>
            </div>
          </div>

          <!-- Course completion -->
          <div v-if="courseCompleted" class="completion-banner">
            🎉 Congratulations! You completed <strong>{{ course.title }}</strong>.
          </div>
        </main>
      </div>
    </template>

    <div v-if="error" class="alert alert-error">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';

const route = useRoute();
const courseId = route.params.id;

const course = ref(null);
const enrollment = ref(null);
const progressMap = ref({});   // lessonId → LessonProgress
const currentLesson = ref(null);
const loading = ref(false);
const enrolling = ref(false);
const lessonCompleting = ref(false);
const error = ref('');
const sidebarOpenModules = ref({});
const videoWatched = ref(false);
const videoMaxTime = ref(0);
const courseCompleted = ref(false);

const allLessons = computed(() => {
  if (!course.value) return [];
  return (course.value.modules || []).flatMap(m => m.lessons || []);
});

const totalLessons = computed(() => allLessons.value.length);
const completedLessons = computed(() => Object.values(progressMap.value).filter(p => p.completed_at).length);
const progressPercent = computed(() => totalLessons.value > 0 ? Math.round((completedLessons.value / totalLessons.value) * 100) : 0);

const nextLesson = computed(() => {
  if (!currentLesson.value) return null;
  const idx = allLessons.value.findIndex(l => l.id === currentLesson.value.id);
  return idx >= 0 && idx < allLessons.value.length - 1 ? allLessons.value[idx + 1] : null;
});

onMounted(async () => {
  loading.value = true;
  try {
    const res = await axios.get(`/courses/${courseId}`);
    course.value = res.data;
    enrollment.value = res.data.enrollment || null;

    if (enrollment.value) {
      await fetchProgress();
      // Auto-open first module
      if (course.value.modules?.length) {
        sidebarOpenModules.value[course.value.modules[0].id] = true;
      }
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to load course';
  } finally {
    loading.value = false;
  }
});

const fetchProgress = async () => {
  try {
    const res = await axios.get(`/courses/${courseId}/progress`);
    progressMap.value = res.data.progress || {};
    courseCompleted.value = !!res.data.enrollment?.completed_at;
  } catch {}
};

const enroll = async () => {
  enrolling.value = true; error.value = '';
  try {
    const res = await axios.post(`/courses/${courseId}/enroll`);
    enrollment.value = res.data.enrollment;
    await fetchProgress();
    if (course.value.modules?.length) {
      sidebarOpenModules.value[course.value.modules[0].id] = true;
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to enroll';
  } finally { enrolling.value = false; }
};

const isLessonCompleted = (id) => !!(progressMap.value[id]?.completed_at);

const selectLesson = (lesson) => {
  currentLesson.value = lesson;
  videoWatched.value = false;
  videoMaxTime.value = 0;
};

const markComplete = async () => {
  if (!currentLesson.value) return;
  lessonCompleting.value = true;
  try {
    const res = await axios.post(`/lessons/${currentLesson.value.id}/complete`);
    await fetchProgress();
    if (res.data.course_completed) courseCompleted.value = true;
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to mark complete';
  } finally { lessonCompleting.value = false; }
};

const onVideoEnded = () => { videoWatched.value = true; };

const trackProgress = (e) => {
  const t = e.target?.currentTime || 0;
  if (t > videoMaxTime.value) videoMaxTime.value = t;
};

const preventSkip = (e) => {
  const el = e.target;
  if (!el || !currentLesson.value?.requires_completion) return;
  if (el.currentTime > videoMaxTime.value + 1) el.currentTime = videoMaxTime.value;
};

const toggleSidebarModule = (id) => { sidebarOpenModules.value[id] = !sidebarOpenModules.value[id]; };
const isSidebarModuleOpen = (id) => !!sidebarOpenModules.value[id];

const youtubeEmbed = (url) => {
  if (!url) return '';
  const v = url.split('v=')[1]?.split('&')[0] || url.split('/').at(-1);
  return `https://www.youtube.com/embed/${v}`;
};
const isPdf = (src) => /\.pdf(\?|#|$)/i.test(src || '');
const levelLabel = (l) => ({ beginner: 'Beginner', intermediate: 'Intermediate', advanced: 'Advanced' }[l] || l);
</script>

<style scoped>
.course-player { min-height: 100vh; background: #f2f5fc; }
.loading { text-align: center; padding: 3rem; color: #667; }

.enroll-banner { max-width: 900px; margin: 0 auto; padding: 2rem 1.5rem; }
.enroll-banner-inner { background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 12px rgba(27,47,85,.1); display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.enroll-banner-inner h2 { margin: 0 0 .5rem; color: #1b2f55; }
.enroll-banner-inner p { color: #5d6b8a; margin: 0 0 .75rem; }
.course-meta-line { display: flex; gap: 1rem; font-size: .85rem; color: #8090aa; }
.btn-enroll-big { background: #2f4df2; color: #fff; border: none; border-radius: 8px; padding: .9rem 2rem; font-size: 1rem; font-weight: 700; cursor: pointer; white-space: nowrap; }
.btn-enroll-big:hover:not(:disabled) { background: #1f3de0; }

.module-overview { background: #fff; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(27,47,85,.07); }
.module-overview h3 { margin: 0 0 1rem; color: #1b2f55; }
.mod-overview-item { display: flex; justify-content: space-between; padding: .5rem 0; border-bottom: 1px solid #f0f4fa; font-size: .93rem; color: #3a4a66; }

.player-layout { display: grid; grid-template-columns: 300px 1fr; min-height: calc(100vh - 120px); }

.player-sidebar { background: #1b2f55; color: #d8e3f3; overflow-y: auto; padding: 1rem 0; }
.sidebar-course-title { font-weight: 700; font-size: .95rem; padding: .5rem 1rem 1rem; color: #fff; border-bottom: 1px solid rgba(255,255,255,.1); }
.sidebar-progress { padding: .75rem 1rem; border-bottom: 1px solid rgba(255,255,255,.1); }
.progress-bar { height: 6px; background: rgba(255,255,255,.2); border-radius: 3px; overflow: hidden; margin-bottom: .4rem; }
.progress-fill { height: 100%; background: #4af098; transition: width .4s; }
.progress-text { font-size: .78rem; color: #8cb3e0; margin: 0; }
.sidebar-module { border-bottom: 1px solid rgba(255,255,255,.06); }
.sidebar-module-title { display: flex; justify-content: space-between; padding: .75rem 1rem; cursor: pointer; font-weight: 600; font-size: .88rem; color: #c4d8f5; }
.sidebar-module-title:hover { background: rgba(255,255,255,.05); }
.sidebar-lessons { padding-bottom: .35rem; }
.sidebar-lesson { display: flex; align-items: center; gap: .55rem; padding: .45rem 1rem .45rem 1.5rem; font-size: .85rem; color: #9bb8da; cursor: pointer; }
.sidebar-lesson:hover { background: rgba(255,255,255,.06); color: #fff; }
.sidebar-lesson.active { background: rgba(71,96,255,.25); color: #fff; }
.sidebar-lesson.completed { color: #6be0a8; }
.sidebar-lesson-icon { font-size: .8rem; width: 1rem; text-align: center; }
.sidebar-lesson-name { flex: 1; }
.quiz-badge { background: rgba(245,200,80,.2); color: #f5c850; font-size: .7rem; border-radius: 4px; padding: .1rem .35rem; }

.player-main { background: #f5f7fc; overflow-y: auto; padding: 1.5rem 2rem; }
.no-lesson-selected { display: flex; align-items: center; justify-content: center; min-height: 300px; color: #8090aa; }

.lesson-view { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(27,47,85,.06); }
.lesson-view h2 { margin: 0 0 1.25rem; color: #1b2f55; }
.lesson-media { margin-bottom: 1.25rem; }
.lesson-video, .lesson-slide-frame { width: 100%; border-radius: 8px; background: #000; }
.lesson-video { aspect-ratio: 16/9; }
.lesson-slide-frame { min-height: 400px; border: 1px solid #dde; }
.lesson-audio { width: 100%; }
.open-slide-link, .open-doc-link { display: inline-block; background: #eef3ff; color: #2f4df2; padding: .6rem 1.2rem; border-radius: 6px; text-decoration: none; font-weight: 600; }
.lesson-content-text { border-left: 3px solid #4760ff; padding: .75rem 1rem; background: #f8faff; border-radius: 0 8px 8px 0; margin-bottom: 1.25rem; color: #2d3a52; line-height: 1.6; }
.lesson-quiz-block { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 1rem; margin-bottom: 1.25rem; }
.lesson-quiz-block p { margin: 0 0 .65rem; color: #92400e; }
.btn-take-quiz { background: #f59e0b; color: #fff; border: none; border-radius: 6px; padding: .6rem 1.2rem; font-size: .9rem; cursor: pointer; text-decoration: none; display: inline-block; }
.lesson-footer { display: flex; gap: 1rem; margin-top: 1.5rem; }
.btn-complete { background: #22c55e; color: #fff; border: none; border-radius: 6px; padding: .7rem 1.3rem; font-size: .95rem; cursor: pointer; }
.btn-complete:disabled { opacity: .5; cursor: not-allowed; }
.btn-next { background: #4760ff; color: #fff; border: none; border-radius: 6px; padding: .7rem 1.3rem; font-size: .95rem; cursor: pointer; }
.watch-note { color: #c0392b; font-size: .87rem; margin: .5rem 0 0; }

.completion-banner { background: linear-gradient(135deg, #22c55e, #15803d); color: #fff; border-radius: 10px; padding: 1.25rem 1.5rem; text-align: center; font-size: 1.05rem; margin-top: 1.5rem; }

.alert { margin: 1rem; padding: .8rem 1rem; border-radius: 6px; }
.alert-error { background: #fff0f0; color: #9f2323; border: 1px solid #ffc9c9; }

@media (max-width: 900px) {
  .player-layout { grid-template-columns: 1fr; }
  .player-sidebar { max-height: 40vh; overflow-y: auto; }
  .enroll-banner-inner { flex-direction: column; }
}
</style>
