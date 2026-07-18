<template>
  <div class="course-builder">
    <div class="builder-header">
      <h1>{{ isEdit ? 'Edit Course' : 'Create Course' }}</h1>
    </div>

    <!-- Tab Navigation -->
    <div class="tab-nav">
      <button type="button" class="tab-btn" :class="{ active: activeTab === 'general' }" @click="activeTab = 'general'">General</button>
      <button type="button" class="tab-btn" :class="{ active: activeTab === 'modules' }" @click="activeTab = 'modules'" :disabled="!isEdit">Modules & Lessons</button>
      <button type="button" class="tab-btn" :class="{ active: activeTab === 'settings' }" @click="activeTab = 'settings'">Settings</button>
    </div>

    <!-- General Tab -->
    <div class="tab-panel" v-if="activeTab === 'general'">
      <div class="panel-card">
        <div class="form-group">
          <label>Course Title *</label>
          <input v-model="form.title" class="form-control" placeholder="e.g. Introduction to Data Science" required />
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea v-model="form.description" class="form-control" rows="4" placeholder="What will students learn?"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Category</label>
            <input v-model="form.category" class="form-control" placeholder="e.g. Technology, Business" />
          </div>
          <div class="form-group">
            <label>Level</label>
            <select v-model="form.level" class="form-control">
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="advanced">Advanced</option>
            </select>
          </div>
          <div class="form-group">
            <label>Duration (minutes)</label>
            <input v-model.number="form.duration_minutes" type="number" class="form-control" min="0" />
          </div>
        </div>

        <div v-if="isEdit" class="form-group">
          <label>Cover Image</label>
          <div class="cover-upload-row">
            <input type="file" class="form-control" accept="image/*" @change="onCoverFileChange" />
            <button type="button" class="btn-secondary" :disabled="!coverFile || coverUploading" @click="uploadCover">
              {{ coverUploading ? 'Uploading…' : 'Upload' }}
            </button>
          </div>
          <img v-if="form.cover_image" :src="form.cover_image" class="cover-preview" alt="Cover" />
        </div>

        <div class="form-actions">
          <button type="button" class="btn-primary" :disabled="submitting" @click="handleSubmit">
            {{ submitting ? 'Saving…' : isEdit ? 'Save Changes' : 'Create Course' }}
          </button>
          <button type="button" class="btn-secondary" @click="$router.push({ name: 'course-list' })">Back</button>
        </div>
      </div>
    </div>

    <!-- Modules & Lessons Tab -->
    <div class="tab-panel" v-if="activeTab === 'modules' && isEdit">
      <div class="panel-card">
        <div class="module-builder-header">
          <h2>Modules</h2>
          <button type="button" class="btn-primary" @click="showAddModule = true">+ Add Module</button>
        </div>

        <!-- Add Module inline form -->
        <div v-if="showAddModule" class="inline-card">
          <div class="form-row">
            <div class="form-group">
              <label>Module Title *</label>
              <input v-model="moduleForm.title" class="form-control" placeholder="Module title" />
            </div>
            <div class="form-group">
              <label>Description (optional)</label>
              <input v-model="moduleForm.description" class="form-control" />
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="btn-primary" :disabled="creatingModule" @click="createModule">{{ creatingModule ? 'Adding…' : 'Add Module' }}</button>
            <button type="button" class="btn-secondary" @click="showAddModule = false; moduleForm.title = ''; moduleForm.description = ''">Cancel</button>
          </div>
        </div>

        <div v-if="modules.length === 0 && !showAddModule" class="empty-state">No modules yet. Add your first module above.</div>

        <!-- Module list -->
        <div v-for="(mod, mIdx) in modules" :key="mod.id" class="module-card">
          <div class="module-head">
            <div>
              <p class="module-label">Module {{ mIdx + 1 }}</p>
              <h3>{{ mod.title }}</h3>
              <p class="muted-text" v-if="mod.description">{{ mod.description }}</p>
            </div>
            <div class="module-actions">
              <button type="button" class="btn-secondary" @click="toggleModule(mod.id)">
                {{ isModuleOpen(mod.id) ? 'Collapse' : 'Expand' }}
              </button>
              <button type="button" class="btn-danger" @click="deleteModule(mod.id)">Delete</button>
            </div>
          </div>

          <div v-if="isModuleOpen(mod.id)" class="lesson-section">
            <div class="lesson-section-header">
              <h4>Lessons</h4>
              <button type="button" class="btn-secondary" @click="openAddLesson(mod.id)">+ Add Lesson</button>
            </div>

            <!-- Add lesson inline form -->
            <div v-if="addLessonForModule === mod.id" class="inline-card">
              <div class="form-row">
                <div class="form-group">
                  <label>Lesson Title *</label>
                  <input v-model="lessonForm.title" class="form-control" />
                </div>
                <div class="form-group">
                  <label>Type</label>
                  <select v-model="lessonForm.lesson_type" class="form-control">
                    <option value="text">Text / Paragraph</option>
                    <option value="video">Video</option>
                    <option value="audio">Audio</option>
                    <option value="slide">Slide</option>
                    <option value="document">Document</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Duration (min)</label>
                  <input v-model.number="lessonForm.duration_minutes" type="number" class="form-control" min="0" />
                </div>
              </div>
              <div class="form-group">
                <label>Content / Description</label>
                <textarea v-model="lessonForm.content" class="form-control" rows="3"></textarea>
              </div>
              <div class="form-group">
                <label>External URL (optional)</label>
                <input v-model="lessonForm.media_url" type="url" class="form-control" placeholder="https://…" />
              </div>
              <div class="form-group">
                <label>Upload File (optional)</label>
                <input type="file" class="form-control" @change="onLessonFileChange" />
              </div>
              <div v-if="lessonForm.lesson_type === 'video'" class="form-group checkbox-group">
                <label>
                  <input v-model="lessonForm.requires_completion" type="checkbox" />
                  Require student to finish video before continuing
                </label>
              </div>
              <div class="form-group">
                <label>Link to Quiz (optional)</label>
                <select v-model="lessonForm.quiz_id" class="form-control">
                  <option value="">— None —</option>
                  <option v-for="quiz in quizzes" :key="quiz.id" :value="quiz.id">{{ quiz.title }}</option>
                </select>
              </div>
              <div class="form-actions">
                <button type="button" class="btn-primary" :disabled="creatingLesson" @click="createLesson(mod.id)">{{ creatingLesson ? 'Adding…' : 'Add Lesson' }}</button>
                <button type="button" class="btn-secondary" @click="addLessonForModule = null">Cancel</button>
              </div>
            </div>

            <!-- Lesson list -->
            <div v-if="(mod.lessons || []).length === 0" class="muted-text" style="padding: .5rem 0">No lessons yet.</div>
            <div v-for="(lesson, lIdx) in (mod.lessons || [])" :key="lesson.id" class="lesson-row">
              <span class="lesson-num">{{ lIdx + 1 }}</span>
              <span class="lesson-type-tag">{{ lesson.lesson_type }}</span>
              <span class="lesson-title">{{ lesson.title }}</span>
              <span class="lesson-duration" v-if="lesson.duration_minutes">{{ lesson.duration_minutes }} min</span>
              <span class="lesson-quiz-tag" v-if="lesson.quiz_id">Quiz</span>
              <button type="button" class="btn-danger-sm" @click="deleteLesson(lesson.id)">✕</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Settings Tab -->
    <div class="tab-panel" v-if="activeTab === 'settings'">
      <div class="panel-card">
        <div class="form-group checkbox-group">
          <label>
            <input v-model="form.is_published" type="checkbox" />
            Publish course (visible to students)
          </label>
        </div>
        <div class="form-actions">
          <button type="button" class="btn-primary" :disabled="submitting" @click="handleSubmit">Save Settings</button>
        </div>
      </div>
    </div>

    <div v-if="error" class="alert alert-error">{{ error }}</div>
    <div v-if="success" class="alert alert-success">{{ success }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const courseId = computed(() => route.params.id);
const isEdit = computed(() => !!courseId.value);

const activeTab = ref('general');
const form = ref({ title: '', description: '', category: '', level: 'beginner', duration_minutes: 0, is_published: false, cover_image: '' });
const modules = ref([]);
const quizzes = ref([]);
const openModules = ref({});
const submitting = ref(false);
const coverFile = ref(null);
const coverUploading = ref(false);
const showAddModule = ref(false);
const creatingModule = ref(false);
const moduleForm = ref({ title: '', description: '' });
const addLessonForModule = ref(null);
const creatingLesson = ref(false);
const lessonFile = ref(null);
const lessonForm = ref({ title: '', lesson_type: 'text', content: '', media_url: '', duration_minutes: 0, requires_completion: false, quiz_id: '' });
const error = ref('');
const success = ref('');

onMounted(async () => {
  await Promise.all([fetchQuizzes(), isEdit.value ? fetchCourse() : Promise.resolve()]);
});

const fetchCourse = async () => {
  try {
    const res = await axios.get(`/courses/${courseId.value}`);
    const c = res.data;
    form.value = { title: c.title, description: c.description || '', category: c.category || '', level: c.level || 'beginner', duration_minutes: c.duration_minutes || 0, is_published: !!c.is_published, cover_image: c.cover_image || '' };
    modules.value = c.modules || [];
  } catch (e) {
    error.value = 'Failed to load course';
  }
};

const fetchQuizzes = async () => {
  try {
    const res = await axios.get('/quizzes');
    quizzes.value = res.data || [];
  } catch {}
};

const handleSubmit = async () => {
  submitting.value = true; error.value = ''; success.value = '';
  try {
    if (isEdit.value) {
      await axios.put(`/courses/${courseId.value}`, form.value);
      success.value = 'Course saved.';
      await fetchCourse();
    } else {
      const res = await axios.post('/courses', form.value);
      success.value = 'Course created.';
      await router.replace({ name: 'course-edit', params: { id: res.data.id } });
      await fetchCourse();
      activeTab.value = 'modules';
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to save course';
  } finally {
    submitting.value = false;
  }
};

const toggleModule = (id) => { openModules.value[id] = !openModules.value[id]; };
const isModuleOpen = (id) => !!openModules.value[id];

const createModule = async () => {
  creatingModule.value = true; error.value = '';
  try {
    await axios.post(`/courses/${courseId.value}/modules`, moduleForm.value);
    moduleForm.value = { title: '', description: '' };
    showAddModule.value = false;
    await fetchCourse();
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to add module';
  } finally { creatingModule.value = false; }
};

const deleteModule = async (id) => {
  try { await axios.delete(`/modules/${id}`); await fetchCourse(); }
  catch (e) { error.value = 'Failed to delete module'; }
};

const openAddLesson = (moduleId) => {
  addLessonForModule.value = moduleId;
  lessonForm.value = { title: '', lesson_type: 'text', content: '', media_url: '', duration_minutes: 0, requires_completion: false, quiz_id: '' };
  lessonFile.value = null;
};

const onLessonFileChange = (e) => { lessonFile.value = e.target.files[0] || null; };

const onCoverFileChange = (e) => { coverFile.value = e.target.files[0] || null; };

const uploadCover = async () => {
  if (!coverFile.value) return;
  coverUploading.value = true;
  const fd = new FormData(); fd.append('cover', coverFile.value);
  try {
    const res = await axios.post(`/courses/${courseId.value}/cover`, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    form.value.cover_image = res.data.cover_image;
    coverFile.value = null;
    success.value = 'Cover uploaded.';
  } catch (e) { error.value = 'Failed to upload cover'; }
  finally { coverUploading.value = false; }
};

const createLesson = async (moduleId) => {
  creatingLesson.value = true; error.value = '';
  const fd = new FormData();
  Object.entries(lessonForm.value).forEach(([k, v]) => { if (v !== '' && v !== null && v !== undefined) fd.append(k, v); });
  if (lessonFile.value) fd.append('file', lessonFile.value);
  try {
    await axios.post(`/modules/${moduleId}/lessons`, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    addLessonForModule.value = null; lessonFile.value = null;
    await fetchCourse();
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to add lesson';
  } finally { creatingLesson.value = false; }
};

const deleteLesson = async (id) => {
  try { await axios.delete(`/lessons/${id}`); await fetchCourse(); }
  catch { error.value = 'Failed to delete lesson'; }
};
</script>

<style scoped>
.course-builder { padding: 1.5rem; max-width: 1000px; margin: 0 auto; }
.builder-header { margin-bottom: 1rem; }
.builder-header h1 { color: #1b2f55; margin: 0; }

.tab-nav { display: flex; gap: .6rem; border-bottom: 1px solid #d8deec; margin-bottom: 1rem; }
.tab-btn { border: none; border-bottom: 3px solid transparent; border-radius: 0; background: transparent; color: #5a6687; padding: .7rem .2rem .85rem; font-size: .9rem; font-weight: 600; cursor: pointer; white-space: nowrap; }
.tab-btn.active { color: #1f2f66; border-bottom-color: #4760ff; }
.tab-btn:disabled { opacity: .4; cursor: not-allowed; }
.tab-btn:hover:not(:disabled) { color: #24386f; }

.panel-card { background: #fff; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,.07); }
.inline-card { background: #f5f8ff; border: 1px solid #dde8ff; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }

.form-group { margin-bottom: 1rem; }
.form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; }
label { display: block; margin-bottom: .4rem; color: #4a5568; font-size: .92rem; font-weight: 500; }
.form-control { width: 100%; padding: .65rem; border: 1px solid #d2d5dc; border-radius: 6px; font-size: .95rem; font-family: inherit; }
.form-control:focus { outline: none; border-color: #4760ff; box-shadow: 0 0 0 3px rgba(71,96,255,.15); }
textarea.form-control { resize: vertical; }
.checkbox-group { display: flex; align-items: center; gap: .4rem; }
.checkbox-group label { display: flex; align-items: center; gap: .5rem; margin: 0; }
.form-actions { display: flex; gap: .75rem; margin-top: 1.25rem; }

.cover-upload-row { display: grid; grid-template-columns: 1fr auto; gap: .75rem; }
.cover-preview { margin-top: .75rem; max-height: 120px; border-radius: 8px; object-fit: cover; border: 1px solid #dde3ef; }

.module-builder-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.module-builder-header h2 { margin: 0; color: #1b2f55; }
.module-card { border: 1px solid #e5eaf5; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
.module-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; }
.module-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: #4760ff; font-weight: 700; margin: 0 0 .2rem; }
.module-head h3 { margin: 0; color: #1b2f55; }
.module-actions { display: flex; gap: .5rem; }
.muted-text { color: #667; font-size: .9rem; }

.lesson-section { border-top: 1px solid #eef1f7; margin-top: 1rem; padding-top: 1rem; }
.lesson-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: .75rem; }
.lesson-section-header h4 { margin: 0; }
.lesson-row { display: flex; align-items: center; gap: .6rem; padding: .5rem .4rem; border-bottom: 1px solid #f5f7fc; }
.lesson-num { width: 1.5rem; text-align: center; font-size: .78rem; color: #aaa; }
.lesson-type-tag { background: #eef3ff; color: #4760ff; font-size: .73rem; border-radius: 4px; padding: .15rem .45rem; text-transform: capitalize; }
.lesson-title { flex: 1; font-size: .9rem; color: #2d3a52; }
.lesson-duration { font-size: .78rem; color: #8090aa; }
.lesson-quiz-tag { background: #fef3c7; color: #92400e; font-size: .73rem; border-radius: 4px; padding: .15rem .45rem; }
.btn-danger-sm { background: #fee2e2; color: #b91c1c; border: none; border-radius: 4px; padding: .15rem .45rem; cursor: pointer; font-size: .8rem; }

.empty-state { color: #667; padding: 1rem; text-align: center; }

.btn-primary, .btn-secondary, .btn-danger { border: none; border-radius: 6px; padding: .7rem 1.1rem; font-size: .95rem; cursor: pointer; }
.btn-primary { background: #4760ff; color: #fff; }
.btn-primary:hover:not(:disabled) { background: #2f4df2; }
.btn-secondary { background: #e7eaf1; color: #243; }
.btn-secondary:hover:not(:disabled) { background: #d8dde8; }
.btn-danger { background: #f03e3e; color: #fff; }
.btn-primary:disabled, .btn-secondary:disabled { opacity: .6; cursor: not-allowed; }

.alert { margin-top: 1rem; padding: .8rem 1rem; border-radius: 6px; }
.alert-error { background: #fff0f0; color: #9f2323; border: 1px solid #ffc9c9; }
.alert-success { background: #ecfdf3; color: #1b6b40; border: 1px solid #b7efcc; }
</style>
