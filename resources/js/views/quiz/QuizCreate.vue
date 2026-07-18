<template>
  <div class="quiz-create">
    <h1>{{ isEdit ? 'Edit Quiz' : 'Create Quiz' }}</h1>

    <div class="tab-nav">
      <button type="button" class="tab-btn" :class="{ active: activeTab === 'general' }" @click="activeTab = 'general'">
        General
      </button>
      <button type="button" class="tab-btn" :class="{ active: activeTab === 'questions' }" @click="activeTab = 'questions'">
        Questions
      </button>
      <button type="button" class="tab-btn" :class="{ active: activeTab === 'certificate' }" @click="activeTab = 'certificate'">
        Certificate
      </button>
      <button type="button" class="tab-btn" :class="{ active: activeTab === 'config' }" @click="activeTab = 'config'">
        Config
      </button>
    </div>

    <div class="quiz-form">
      <div v-if="activeTab === 'general'" class="tab-panel">
        <div class="form-group">
          <label for="title">Quiz Title:</label>
          <input v-model="form.title" type="text" id="title" class="form-control" required />
        </div>

        <div class="form-group">
          <label for="description">Description:</label>
          <textarea v-model="form.description" id="description" class="form-control" rows="4"></textarea>
        </div>

        <div class="form-group checkbox-group">
          <label for="is_published">
            <input v-model="form.is_published" type="checkbox" id="is_published" />
            Publish Quiz
          </label>
        </div>
      </div>

      <div v-if="activeTab === 'config'" class="tab-panel">
        <div class="form-row">
          <div class="form-group">
            <label for="pass_percentage">Pass Percentage:</label>
            <input
              v-model.number="form.pass_percentage"
              type="number"
              id="pass_percentage"
              class="form-control"
              min="0"
              max="100"
              required
            />
          </div>
          <div class="form-group">
            <label for="attempts_allowed">Attempts Allowed:</label>
            <input
              v-model.number="form.attempts_allowed"
              type="number"
              id="attempts_allowed"
              class="form-control"
              min="0"
              required
            />
          </div>
        </div>

        <div class="form-group checkbox-group">
          <label for="pass_notification_enabled">
            <input v-model="form.pass_notification_enabled" type="checkbox" id="pass_notification_enabled" />
            Send pass email notification
          </label>
        </div>

        <div class="form-group">
          <label for="pass_notification_cc_email">HR CC Email (optional)</label>
          <input
            v-model="form.pass_notification_cc_email"
            type="email"
            id="pass_notification_cc_email"
            class="form-control"
            placeholder="hr@company.com"
          />
        </div>
      </div>

      <div v-if="activeTab === 'certificate'" class="tab-panel">
        <div class="form-group">
          <label for="certificate_enabled">Should this quiz provide a certificate when student passes?</label>
          <select id="certificate_enabled" v-model="form.certificate_enabled" class="form-control">
            <option :value="true">Yes, provide certificate</option>
            <option :value="false">No certificate needed</option>
          </select>
        </div>

        <div class="certificate-summary-card" :class="{ disabled: !form.certificate_enabled }">
          <div class="certificate-summary-head">
            <div>
              <p class="template-preview-label">Certificate Customization</p>
              <p class="certificate-summary-status">{{ certificateStatusText }}</p>
            </div>
            <div class="certificate-summary-actions">
              <button
                type="button"
                class="btn-secondary"
                @click="openCertificateDialog(hasCertificateTemplateConfigured ? 'edit' : 'create')"
                :disabled="!form.certificate_enabled"
              >
                {{ hasCertificateTemplateConfigured ? 'Edit Template' : 'Create Template' }}
              </button>
              <button
                type="button"
                class="btn-secondary"
                @click="openCertificateDialog('preview')"
                :disabled="!form.certificate_enabled"
              >
                Preview
              </button>
            </div>
          </div>
          <p v-if="!form.certificate_enabled" class="template-help">
            Certificate is disabled. Students can still pass the quiz, but no certificate page or PDF will be generated.
          </p>
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn-primary" :disabled="submitting" @click="handleSubmit">
          {{ submitting ? 'Saving...' : (isEdit ? 'Update Quiz' : 'Create Quiz') }}
        </button>
        <button type="button" @click="cancel" class="btn-secondary">Back to List</button>
      </div>
    </div>

    <div v-if="error" class="alert alert-error">{{ error }}</div>
    <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

    <div v-if="activeTab === 'questions' && !isEdit" class="helper-card">
      <h2>Next step</h2>
      <p>Save this quiz first. Then you can add questions with paragraph, video, audio, and image/document uploads.</p>
    </div>

    <div v-if="activeTab === 'questions' && isEdit" class="builder-section">
      <div class="builder-header">
        <h2>Question Builder</h2>
        <button class="btn-secondary" type="button" @click="openPreview" :disabled="questions.length === 0">
          Preview Student Flow
        </button>
      </div>

      <form @submit.prevent="createQuestion" class="question-form">
        <div class="form-group">
          <label for="question_text">Question:</label>
          <textarea
            id="question_text"
            v-model="questionForm.question_text"
            class="form-control"
            rows="3"
            required
          ></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="question_type">Type:</label>
            <select id="question_type" v-model="questionForm.type" class="form-control">
              <option value="multiple_choice">Multiple Choice</option>
              <option value="true_false">True / False</option>
              <option value="short_answer">Short Answer</option>
            </select>
          </div>
          <div class="form-group">
            <label for="question_explanation">Explanation (optional):</label>
            <input id="question_explanation" v-model="questionForm.explanation" class="form-control" type="text" />
          </div>
        </div>

        <button type="submit" class="btn-primary" :disabled="creatingQuestion">
          {{ creatingQuestion ? 'Adding Question...' : 'Add Question' }}
        </button>
      </form>

      <div v-if="questions.length === 0" class="empty-state">No questions yet.</div>

      <div v-for="(question, index) in questions" :key="question.id" class="question-card">
        <div class="question-head">
          <template v-if="editingQuestionId === question.id">
            <div class="edit-question-wrap">
              <div class="form-group">
                <label>Edit Question</label>
                <textarea v-model="editingQuestionForm.question_text" class="form-control" rows="3"></textarea>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Type</label>
                  <select v-model="editingQuestionForm.type" class="form-control">
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="true_false">True / False</option>
                    <option value="short_answer">Short Answer</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Explanation (optional)</label>
                  <input v-model="editingQuestionForm.explanation" class="form-control" type="text" />
                </div>
              </div>
            </div>
          </template>
          <template v-else>
            <div>
              <p class="question-label">Question {{ index + 1 }}</p>
              <h3>{{ question.question_text }}</h3>
              <p class="question-meta">Type: {{ question.type }}</p>
              <p class="question-meta" v-if="question.explanation">Explanation: {{ question.explanation }}</p>
            </div>
          </template>

          <div class="question-actions">
            <template v-if="editingQuestionId === question.id">
              <button class="btn-secondary" type="button" @click="saveQuestion(question.id)" :disabled="savingQuestionId === question.id">
                {{ savingQuestionId === question.id ? 'Saving...' : 'Save' }}
              </button>
              <button class="btn-secondary" type="button" @click="cancelEditQuestion">Cancel</button>
            </template>
            <template v-else>
              <button class="btn-secondary" type="button" @click="toggleQuestionCollapse(question.id)">
                {{ isQuestionCollapsed(question.id) ? 'Expand' : 'Collapse' }}
              </button>
              <button class="btn-secondary" type="button" @click="startEditQuestion(question)">Edit</button>
              <button class="btn-danger" type="button" @click="deleteQuestion(question.id)">Delete Question</button>
            </template>
          </div>
        </div>

        <div v-if="isQuestionCollapsed(question.id) && editingQuestionId !== question.id" class="collapsed-note">
          Collapsed. Click Expand to edit answers and media.
        </div>

        <div v-if="!isQuestionCollapsed(question.id) || editingQuestionId === question.id" class="subsection">
          <h4>Media</h4>
          <div v-if="(question.media || []).length === 0" class="muted">No media yet.</div>
          <ul v-else class="media-list">
            <li v-for="media in question.media" :key="media.id">
              <strong>{{ media.media_type }}</strong>
              <span v-if="media.description"> - {{ media.description }}</span>
              <span v-if="media.media_url"> - URL: {{ media.media_url }}</span>
              <span v-if="media.media_path"> - File: {{ media.media_path }}</span>
            </li>
          </ul>

          <form class="inline-form" @submit.prevent="addMedia(question.id)">
            <div class="form-row">
              <div class="form-group">
                <label>Media Type</label>
                <select v-model="getMediaForm(question.id).media_type" class="form-control">
                  <option value="paragraph">Paragraph</option>
                  <option value="slide">Slide Content</option>
                  <option value="video">Video</option>
                  <option value="audio">Voice Over (Audio)</option>
                  <option value="image">Graphic / Image</option>
                  <option value="document">Document</option>
                </select>
              </div>
              <div class="form-group">
                <label>Media URL (optional)</label>
                <input v-model="getMediaForm(question.id).media_url" class="form-control" type="url" placeholder="https://..." />
              </div>
            </div>

            <div v-if="getMediaForm(question.id).media_type === 'video'" class="form-group checkbox-group">
              <label>
                <input v-model="getMediaForm(question.id).requires_completion" type="checkbox" />
                Require student to finish this video before continuing
              </label>
            </div>

            <div v-if="getMediaForm(question.id).media_type === 'video'" class="form-row">
              <div class="form-group">
                <label>Player View</label>
                <select v-model="getMediaForm(question.id).player_layout" class="form-control">
                  <option value="default">Default</option>
                  <option value="framed">Framed</option>
                  <option value="cinema">Cinema</option>
                  <option value="compact">Compact</option>
                </select>
              </div>
              <div class="form-group">
                <label>Player Caption (optional)</label>
                <input v-model="getMediaForm(question.id).player_caption" class="form-control" type="text" placeholder="Describe the view or provide instructions" />
              </div>
            </div>

            <div v-if="getMediaForm(question.id).media_type === 'video'" class="form-group">
              <label>Video Poster / Thumbnail (optional)</label>
              <input type="file" class="form-control" accept="image/*" @change="onPosterFileChange(question.id, $event)" />
            </div>

            <p v-if="getMediaForm(question.id).media_type === 'video' && getMediaForm(question.id).requires_completion" class="template-help">
              For strict completion control, upload the video file directly. External embeds can be displayed, but they cannot be fully tracked for completion.
            </p>

            <div class="form-group">
              <label>Paragraph or Description</label>
              <textarea
                v-model="getMediaForm(question.id).description"
                class="form-control"
                rows="2"
                placeholder="Use this for paragraph/slide reading content"
              ></textarea>
            </div>

            <div class="form-group">
              <label>Upload File (optional)</label>
              <input type="file" class="form-control" @change="onMediaFileChange(question.id, $event)" />
            </div>

            <button class="btn-secondary" type="submit" :disabled="addingMediaFor === question.id">
              {{ addingMediaFor === question.id ? 'Uploading...' : 'Add Media' }}
            </button>
          </form>
        </div>

        <div class="subsection" v-if="(!isQuestionCollapsed(question.id) || editingQuestionId === question.id) && question.type !== 'short_answer'">
          <h4>Answers</h4>
          <div v-if="(question.answers || []).length === 0" class="muted">No answers yet.</div>
          <ul v-else class="answer-list">
            <li v-for="answer in question.answers" :key="answer.id">
              <template v-if="editingAnswerId === answer.id">
                <div class="answer-edit-row">
                  <input v-model="editingAnswerForm.answer_text" class="form-control" type="text" />
                  <label class="inline-checkbox">
                    <input v-model="editingAnswerForm.is_correct" type="checkbox" />
                    Correct
                  </label>
                  <button class="btn-secondary" type="button" @click="saveAnswer(answer.id)" :disabled="savingAnswerId === answer.id">
                    {{ savingAnswerId === answer.id ? 'Saving...' : 'Save' }}
                  </button>
                  <button class="btn-secondary" type="button" @click="cancelEditAnswer">Cancel</button>
                </div>
              </template>
              <template v-else>
                <div class="answer-view-row">
                  <span>
                    {{ answer.answer_text }}
                    <span class="correct-tag" v-if="answer.is_correct">Correct</span>
                  </span>
                  <button class="btn-secondary" type="button" @click="startEditAnswer(answer)">Edit</button>
                </div>
              </template>
            </li>
          </ul>

          <form class="inline-form" @submit.prevent="addAnswer(question.id)">
            <div class="form-row">
              <div class="form-group">
                <label>Answer Text</label>
                <input v-model="getAnswerForm(question.id).answer_text" class="form-control" type="text" required />
              </div>
              <div class="form-group checkbox-group">
                <label>
                  <input v-model="getAnswerForm(question.id).is_correct" type="checkbox" />
                  Mark as correct
                </label>
              </div>
            </div>

            <button class="btn-secondary" type="submit" :disabled="addingAnswerFor === question.id">
              {{ addingAnswerFor === question.id ? 'Adding...' : 'Add Answer' }}
            </button>
          </form>
        </div>
      </div>

      <div v-if="previewOpen && previewQuestion" class="preview-panel">
        <div class="preview-head">
          <h3>Student Preview</h3>
          <button class="btn-danger" type="button" @click="closePreview">Close Preview</button>
        </div>

        <p class="preview-meta">
          Question {{ previewQuestionIndex + 1 }} of {{ questions.length }}
          <span class="preview-step-tag">{{ previewStep === 'read' ? 'Read Page' : 'Answer Page' }}</span>
        </p>

        <div class="preview-content">
          <h4 v-if="previewStep === 'read'">Read First</h4>
          <h4 v-else>{{ previewQuestion.question_text }}</h4>

          <div v-if="(previewQuestion.media || []).length > 0" class="preview-media-wrap">
            <div v-for="media in previewQuestion.media" :key="media.id" class="preview-media-item">
              <template v-if="media.media_type === 'image'">
                <img :src="mediaSource(media)" class="preview-image" alt="Question graphic" />
              </template>
              <template v-else-if="media.media_type === 'video'">
                <video v-if="media.media_path" class="preview-video" controls>
                  <source :src="media.media_path" />
                </video>
                <iframe
                  v-else-if="media.media_url"
                  class="preview-video"
                  :src="getYoutubeEmbedUrl(media.media_url)"
                  frameborder="0"
                  allowfullscreen
                ></iframe>
              </template>
              <template v-else-if="media.media_type === 'audio'">
                <audio class="preview-audio" controls :src="mediaSource(media)"></audio>
              </template>
              <template v-else-if="media.media_type === 'document'">
                <a :href="mediaSource(media)" target="_blank" rel="noopener noreferrer">Open document</a>
              </template>
              <template v-else-if="media.media_type === 'slide'">
                <p>{{ media.description }}</p>
                <iframe
                  v-if="isPdfSource(mediaSource(media))"
                  class="preview-video"
                  :src="mediaSource(media)"
                ></iframe>
                <a v-else-if="mediaSource(media)" :href="mediaSource(media)" target="_blank" rel="noopener noreferrer">Open slide file</a>
              </template>
              <template v-else>
                <p>{{ media.description }}</p>
              </template>
            </div>
          </div>

          <div v-if="previewStep === 'read'" class="preview-read-box">
            <p>{{ previewQuestion.question_text }}</p>
            <p class="preview-note">Student sees this reading page first, then continues to answer page.</p>
          </div>

          <div v-else class="preview-answers">
            <template v-if="previewQuestion.type === 'short_answer'">
              <textarea class="form-control" rows="3" placeholder="Short answer preview" disabled></textarea>
            </template>
            <template v-else>
              <label v-for="answer in (previewQuestion.answers || [])" :key="answer.id" class="preview-answer-item">
                <input
                  type="radio"
                  :name="`preview-q-${previewQuestion.id}`"
                  :value="answer.id"
                  v-model="previewSelectedAnswer"
                />
                <span>{{ answer.answer_text }}</span>
              </label>
            </template>
          </div>
        </div>

        <div class="preview-actions">
          <button
            v-if="previewStep === 'read'"
            class="btn-secondary"
            type="button"
            @click="previewStep = 'answer'"
          >
            Continue to Answer
          </button>
          <button
            v-else
            class="btn-primary"
            type="button"
            @click="goToNextPreviewQuestion"
          >
            {{ previewQuestionIndex < questions.length - 1 ? 'Next Question' : 'Finish Preview' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="certificateDialogOpen" class="certificate-modal-overlay" @click.self="closeCertificateDialog">
      <div class="certificate-modal">
        <div class="certificate-modal-head">
          <h3>{{ certificateDialogTitle }}</h3>
          <button type="button" class="btn-secondary" @click="closeCertificateDialog">Close</button>
        </div>

        <div class="certificate-mode-switch">
          <button type="button" class="btn-secondary" :class="{ active: certificateDialogMode === 'create' }" @click="setCertificateDialogMode('create')">
            Create
          </button>
          <button type="button" class="btn-secondary" :class="{ active: certificateDialogMode === 'edit' }" @click="setCertificateDialogMode('edit')">
            Edit
          </button>
          <button type="button" class="btn-secondary" :class="{ active: certificateDialogMode === 'preview' }" @click="setCertificateDialogMode('preview')">
            Preview
          </button>
        </div>

        <div v-if="certificateDialogMode !== 'preview'">
          <div class="form-group">
            <label for="certificate_template_title">Certificate Title Template</label>
            <input
              v-model="form.certificate_template_title"
              @focus="selectedTemplateField = 'title'"
              type="text"
              id="certificate_template_title"
              class="form-control"
              placeholder="Certificate of Achievement"
            />
          </div>

          <div class="form-group">
            <label for="certificate_template_body">Certificate Body Template</label>
            <textarea
              v-model="form.certificate_template_body"
              @focus="selectedTemplateField = 'body'"
              id="certificate_template_body"
              class="form-control"
              rows="4"
              placeholder="This certifies that {student_name} has passed {quiz_title} with score {score}%..."
            ></textarea>
            <p class="template-help">Available placeholders: {student_name}, {student_email}, {quiz_title}, {score}, {pass_mark}, {attempt_number}, {certificate_code}, {completed_at}, {app_name}</p>
          </div>

          <div class="form-group">
            <label for="certificate_template_footer">Certificate Footer Template</label>
            <input
              v-model="form.certificate_template_footer"
              @focus="selectedTemplateField = 'footer'"
              type="text"
              id="certificate_template_footer"
              class="form-control"
              placeholder="Authorized by {app_name}"
            />
          </div>

          <div class="template-tools">
            <p class="template-help">Insert placeholder into {{ selectedTemplateFieldLabel }}:</p>
            <div class="placeholder-chips">
              <button v-for="token in placeholderTokens" :key="token" type="button" class="chip" @click="insertPlaceholder(token)">
                {{ token }}
              </button>
            </div>
            <button type="button" class="btn-secondary" @click="resetCertificateTemplateDefaults">
              Reset Template Defaults
            </button>
          </div>

          <div class="form-group" v-if="isEdit">
            <label>Certificate Logo (optional)</label>
            <div class="logo-upload-row">
              <input type="file" class="form-control" accept="image/*" @change="onLogoFileChange" />
              <button type="button" class="btn-secondary" :disabled="!logoFile || logoUploading" @click="uploadCertificateLogo">
                {{ logoUploading ? 'Uploading...' : 'Upload Logo' }}
              </button>
            </div>
            <img v-if="form.certificate_logo_path" :src="form.certificate_logo_path" alt="Certificate logo" class="logo-preview" />
          </div>
          <p v-else class="template-help">Save quiz first to upload a logo image.</p>
        </div>

        <div class="template-preview-card" v-else>
          <p class="template-preview-label">Live Certificate Preview</p>
          <img v-if="certificateTemplatePreview.logo_url" :src="certificateTemplatePreview.logo_url" alt="Certificate logo preview" class="template-logo" />
          <h3>{{ certificateTemplatePreview.title }}</h3>
          <p>{{ certificateTemplatePreview.body }}</p>
          <div class="template-preview-meta">
            <p><strong>Student:</strong> {{ certificateTemplatePreview.tokens.student_name }}</p>
            <p><strong>Quiz:</strong> {{ certificateTemplatePreview.tokens.quiz_title }}</p>
            <p><strong>Score:</strong> {{ certificateTemplatePreview.tokens.score }}%</p>
            <p><strong>Certificate Code:</strong> {{ certificateTemplatePreview.tokens.certificate_code }}</p>
          </div>
          <p class="template-preview-footer">{{ certificateTemplatePreview.footer }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';

const router = useRouter();
const route = useRoute();

const quizId = computed(() => route.params.id);
const isEdit = computed(() => !!quizId.value);
const activeTab = ref('general');

const form = ref({
  title: '',
  description: '',
  pass_percentage: 70,
  attempts_allowed: 0,
  is_published: false,
  pass_notification_enabled: true,
  pass_notification_cc_email: '',
  certificate_enabled: true,
  certificate_template_title: 'Certificate of Achievement',
  certificate_template_body: '',
  certificate_template_footer: 'Authorized by {app_name}',
  certificate_logo_path: '',
});

const questionForm = ref({
  question_text: '',
  type: 'multiple_choice',
  explanation: '',
});

const questions = ref([]);
const answerForms = ref({});
const mediaForms = ref({});

const submitting = ref(false);
const creatingQuestion = ref(false);
const addingAnswerFor = ref(null);
const addingMediaFor = ref(null);
const error = ref('');
const successMessage = ref('');
const editingQuestionId = ref(null);
const editingQuestionForm = ref({ question_text: '', type: 'multiple_choice', explanation: '' });
const savingQuestionId = ref(null);
const editingAnswerId = ref(null);
const editingAnswerForm = ref({ answer_text: '', is_correct: false });
const savingAnswerId = ref(null);
const collapsedQuestions = ref({});
const previewOpen = ref(false);
const previewQuestionIndex = ref(0);
const previewStep = ref('read');
const previewSelectedAnswer = ref(null);
const selectedTemplateField = ref('body');
const logoFile = ref(null);
const logoUploading = ref(false);
const certificateDialogOpen = ref(false);
const certificateDialogMode = ref('create');

const placeholderTokens = [
  '{student_name}',
  '{student_email}',
  '{quiz_title}',
  '{score}',
  '{pass_mark}',
  '{attempt_number}',
  '{certificate_code}',
  '{completed_at}',
  '{app_name}',
];

const previewQuestion = computed(() => questions.value[previewQuestionIndex.value] || null);
const selectedTemplateFieldLabel = computed(() => {
  if (selectedTemplateField.value === 'title') {
    return 'Title';
  }

  if (selectedTemplateField.value === 'footer') {
    return 'Footer';
  }

  return 'Body';
});
const hasCertificateTemplateConfigured = computed(() => {
  const title = (form.value.certificate_template_title || '').trim();
  const body = (form.value.certificate_template_body || '').trim();
  const footer = (form.value.certificate_template_footer || '').trim();

  return title.length > 0 || body.length > 0 || footer.length > 0;
});
const certificateStatusText = computed(() => {
  if (!form.value.certificate_enabled) {
    return 'Disabled for this quiz';
  }

  return hasCertificateTemplateConfigured.value
    ? 'Enabled with template configured'
    : 'Enabled with default template';
});
const certificateDialogTitle = computed(() => {
  if (certificateDialogMode.value === 'preview') {
    return 'Preview Certificate Template';
  }

  if (certificateDialogMode.value === 'edit') {
    return 'Edit Certificate Template';
  }

  return 'Create Certificate Template';
});
const certificateTemplatePreview = computed(() => {
  const tokens = {
    student_name: 'Sample Student',
    student_email: 'student@example.com',
    quiz_title: form.value.title || 'Sample Quiz',
    score: '85',
    pass_mark: String(form.value.pass_percentage ?? 70),
    attempt_number: '1',
    certificate_code: 'CERT-100-200-20260621120000',
    completed_at: new Date().toLocaleString(),
    app_name: 'ELMS Quiz System',
  };

  const title = applyTemplate(form.value.certificate_template_title || 'Certificate of Achievement', tokens);
  const body = applyTemplate(
    form.value.certificate_template_body
      || 'This certifies that {student_name} has successfully passed "{quiz_title}" with a score of {score}% on {completed_at}. Certificate code: {certificate_code}.',
    tokens
  );
  const footer = applyTemplate(form.value.certificate_template_footer || 'Authorized by {app_name}', tokens);

  return {
    title,
    body,
    footer,
    logo_url: form.value.certificate_logo_path || null,
    tokens,
  };
});

onMounted(async () => {
  if (isEdit.value) {
    await fetchQuiz();
  }
});

const fetchQuiz = async () => {
  try {
    const response = await axios.get(`/quizzes/${quizId.value}`);
    const quiz = response.data;
    form.value = {
      title: quiz.title,
      description: quiz.description || '',
      pass_percentage: quiz.pass_percentage,
      attempts_allowed: quiz.attempts_allowed,
      is_published: !!quiz.is_published,
      pass_notification_enabled: quiz.pass_notification_enabled !== false,
      pass_notification_cc_email: quiz.pass_notification_cc_email || '',
      certificate_enabled: quiz.certificate_enabled !== false,
      certificate_template_title: quiz.certificate_template_title || 'Certificate of Achievement',
      certificate_template_body: quiz.certificate_template_body || '',
      certificate_template_footer: quiz.certificate_template_footer || 'Authorized by {app_name}',
      certificate_logo_path: quiz.certificate_logo_path || '',
    };
    questions.value = (quiz.questions || []).map((q) => ({
      ...q,
      media: q.media || [],
      answers: q.answers || [],
    }));

    const questionIds = new Set(questions.value.map((question) => question.id));
    Object.keys(collapsedQuestions.value).forEach((questionId) => {
      if (!questionIds.has(Number(questionId))) {
        delete collapsedQuestions.value[questionId];
      }
    });

    if (previewQuestionIndex.value >= questions.value.length) {
      previewQuestionIndex.value = 0;
      previewStep.value = 'read';
      previewSelectedAnswer.value = null;
      if (questions.value.length === 0) {
        previewOpen.value = false;
      }
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load quiz';
  }
};

const handleSubmit = async () => {
  submitting.value = true;
  error.value = '';
  successMessage.value = '';

  try {
    if (isEdit.value) {
      await axios.put(`/quizzes/${quizId.value}`, form.value);
      successMessage.value = 'Quiz updated successfully.';
      await fetchQuiz();
    } else {
      const response = await axios.post('/quizzes', form.value);
      successMessage.value = 'Quiz created. You can now add questions and media.';
      await router.replace({ name: 'quiz-edit', params: { id: response.data.id } });
      activeTab.value = 'questions';
      await fetchQuiz();
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save quiz';
  } finally {
    submitting.value = false;
  }
};

const createQuestion = async () => {
  if (!isEdit.value) return;

  creatingQuestion.value = true;
  error.value = '';

  try {
    await axios.post(`/quizzes/${quizId.value}/questions`, questionForm.value);
    questionForm.value = {
      question_text: '',
      type: 'multiple_choice',
      explanation: '',
    };
    await fetchQuiz();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to create question';
  } finally {
    creatingQuestion.value = false;
  }
};

const deleteQuestion = async (questionId) => {
  error.value = '';
  try {
    await axios.delete(`/questions/${questionId}`);
    await fetchQuiz();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete question';
  }
};

const getAnswerForm = (questionId) => {
  if (!answerForms.value[questionId]) {
    answerForms.value[questionId] = {
      answer_text: '',
      is_correct: false,
    };
  }
  return answerForms.value[questionId];
};

const addAnswer = async (questionId) => {
  const payload = getAnswerForm(questionId);
  addingAnswerFor.value = questionId;
  error.value = '';

  try {
    await axios.post(`/questions/${questionId}/answers`, payload);
    answerForms.value[questionId] = {
      answer_text: '',
      is_correct: false,
    };
    await fetchQuiz();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to add answer';
  } finally {
    addingAnswerFor.value = null;
  }
};

const startEditQuestion = (question) => {
  editingQuestionId.value = question.id;
  collapsedQuestions.value[question.id] = false;
  editingQuestionForm.value = {
    question_text: question.question_text,
    type: question.type,
    explanation: question.explanation || '',
  };
};

const isQuestionCollapsed = (questionId) => !!collapsedQuestions.value[questionId];

const toggleQuestionCollapse = (questionId) => {
  collapsedQuestions.value[questionId] = !collapsedQuestions.value[questionId];
};

const cancelEditQuestion = () => {
  editingQuestionId.value = null;
  editingQuestionForm.value = { question_text: '', type: 'multiple_choice', explanation: '' };
};

const saveQuestion = async (questionId) => {
  savingQuestionId.value = questionId;
  error.value = '';

  try {
    await axios.put(`/questions/${questionId}`, editingQuestionForm.value);
    cancelEditQuestion();
    await fetchQuiz();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to update question';
  } finally {
    savingQuestionId.value = null;
  }
};

const startEditAnswer = (answer) => {
  editingAnswerId.value = answer.id;
  editingAnswerForm.value = {
    answer_text: answer.answer_text,
    is_correct: !!answer.is_correct,
  };
};

const cancelEditAnswer = () => {
  editingAnswerId.value = null;
  editingAnswerForm.value = { answer_text: '', is_correct: false };
};

const saveAnswer = async (answerId) => {
  savingAnswerId.value = answerId;
  error.value = '';

  try {
    await axios.put(`/answers/${answerId}`, editingAnswerForm.value);
    cancelEditAnswer();
    await fetchQuiz();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to update answer';
  } finally {
    savingAnswerId.value = null;
  }
};

const getMediaForm = (questionId) => {
  if (!mediaForms.value[questionId]) {
    mediaForms.value[questionId] = {
      media_type: 'paragraph',
      media_url: '',
      description: '',
      requires_completion: true,
      player_layout: 'default',
      player_caption: '',
        poster_file: null,
      file: null,
    };
  }
  return mediaForms.value[questionId];
};

const onMediaFileChange = (questionId, event) => {
  const [file] = event.target.files || [];
  getMediaForm(questionId).file = file || null;
};

const onPosterFileChange = (questionId, event) => {
  const [file] = event.target.files || [];
  getMediaForm(questionId).poster_file = file || null;
};

const addMedia = async (questionId) => {
  const mediaForm = getMediaForm(questionId);
  addingMediaFor.value = questionId;
  error.value = '';

  const formData = new FormData();
  formData.append('media_type', mediaForm.media_type);
  if (mediaForm.media_url) {
    formData.append('media_url', mediaForm.media_url);
  }
  if (mediaForm.description) {
    formData.append('description', mediaForm.description);
  }
  if (mediaForm.media_type === 'video') {
    formData.append('requires_completion', mediaForm.requires_completion ? '1' : '0');
    formData.append('player_layout', mediaForm.player_layout || 'default');
    if (mediaForm.player_caption) {
      formData.append('player_caption', mediaForm.player_caption);
    }
    if (mediaForm.poster_file) {
      formData.append('poster_file', mediaForm.poster_file);
    }
  }
  if (mediaForm.file) {
    formData.append('file', mediaForm.file);
  }

  try {
    await axios.post(`/questions/${questionId}/media`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    mediaForms.value[questionId] = {
      media_type: 'paragraph',
      media_url: '',
      description: '',
      requires_completion: true,
      player_layout: 'default',
      player_caption: '',
      poster_file: null,
      file: null,
    };

    await fetchQuiz();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to add media';
  } finally {
    addingMediaFor.value = null;
  }
};

const cancel = () => {
  router.push({ name: 'quiz-list' });
};

const openPreview = () => {
  previewOpen.value = true;
  previewQuestionIndex.value = 0;
  previewStep.value = 'read';
  previewSelectedAnswer.value = null;
};

const openCertificateDialog = (mode = 'edit') => {
  certificateDialogMode.value = mode;
  certificateDialogOpen.value = true;
};

const closeCertificateDialog = () => {
  certificateDialogOpen.value = false;
};

const setCertificateDialogMode = (mode) => {
  certificateDialogMode.value = mode;
};

const closePreview = () => {
  previewOpen.value = false;
};

const goToNextPreviewQuestion = () => {
  if (previewQuestionIndex.value < questions.value.length - 1) {
    previewQuestionIndex.value += 1;
    previewStep.value = 'read';
    previewSelectedAnswer.value = null;
    return;
  }

  closePreview();
};

const mediaSource = (media) => media.media_path || media.media_url || '';

const getYoutubeEmbedUrl = (url) => {
  if (!url) {
    return '';
  }

  const videoId = url.split('v=')[1]?.split('&')[0] || url.split('/')[url.split('/').length - 1];
  return `https://www.youtube.com/embed/${videoId}`;
};

const isPdfSource = (source) => /\.pdf(\?|#|$)/i.test(source || '');

const applyTemplate = (template, tokens) => {
  let output = template;

  Object.entries(tokens).forEach(([key, value]) => {
    output = output.replaceAll(`{${key}}`, value);
  });

  return output;
};

const resetCertificateTemplateDefaults = () => {
  form.value.certificate_template_title = 'Certificate of Achievement';
  form.value.certificate_template_body = 'This certifies that {student_name} has successfully passed "{quiz_title}" with a score of {score}% on {completed_at}. Certificate code: {certificate_code}.';
  form.value.certificate_template_footer = 'Authorized by {app_name}';
};

const insertPlaceholder = (token) => {
  if (selectedTemplateField.value === 'title') {
    form.value.certificate_template_title = `${form.value.certificate_template_title || ''}${token}`;
    return;
  }

  if (selectedTemplateField.value === 'footer') {
    form.value.certificate_template_footer = `${form.value.certificate_template_footer || ''}${token}`;
    return;
  }

  form.value.certificate_template_body = `${form.value.certificate_template_body || ''}${token}`;
};

const onLogoFileChange = (event) => {
  const [file] = event.target.files || [];
  logoFile.value = file || null;
};

const uploadCertificateLogo = async () => {
  if (!logoFile.value || !isEdit.value) {
    return;
  }

  logoUploading.value = true;
  error.value = '';

  const payload = new FormData();
  payload.append('logo', logoFile.value);

  try {
    const response = await axios.post(`/quizzes/${quizId.value}/certificate-logo`, payload, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    form.value.certificate_logo_path = response.data.certificate_logo_path;
    logoFile.value = null;
    successMessage.value = response.data.message || 'Certificate logo uploaded.';
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to upload certificate logo.';
  } finally {
    logoUploading.value = false;
  }
};
</script>

<style scoped>
.quiz-create {
  padding: 2rem;
  max-width: 1000px;
  margin: 0 auto;
}

.quiz-create h1 {
  color: #333;
  margin-bottom: 1.5rem;
}

.tab-nav {
  display: flex;
  flex-wrap: nowrap;
  gap: 0.6rem;
  margin-bottom: 1rem;
  overflow-x: auto;
  padding-bottom: 0.25rem;
  -webkit-overflow-scrolling: touch;
  border-bottom: 1px solid #d8deec;
}

.tab-btn {
  flex: 0 0 auto;
  border: none;
  border-bottom: 3px solid transparent;
  border-radius: 0;
  background: transparent;
  color: #5a6687;
  padding: 0.7rem 0.2rem 0.85rem;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}

.tab-btn.active {
  color: #1f2f66;
  border-bottom-color: #4760ff;
}

.tab-btn:hover {
  color: #24386f;
}

.tab-panel {
  margin-bottom: 0.25rem;
}

h2 {
  margin-top: 0;
  color: #223;
}

.quiz-form,
.builder-section,
.helper-card {
  background: white;
  border-radius: 10px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  margin-bottom: 1.25rem;
}

.builder-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.checkbox-group {
  display: flex;
  align-items: center;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  color: #555;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 0.7rem;
  border: 1px solid #d2d5dc;
  border-radius: 6px;
  font-size: 0.95rem;
  font-family: inherit;
}

.form-control:focus {
  outline: none;
  border-color: #4760ff;
  box-shadow: 0 0 0 3px rgba(71, 96, 255, 0.15);
}

.template-help {
  margin: 0.45rem 0 0;
  font-size: 0.82rem;
  color: #5f6b87;
}

.certificate-summary-card {
  border: 1px solid #dce3f2;
  border-radius: 8px;
  background: #f8faff;
  padding: 1rem;
  margin-bottom: 1rem;
}

.certificate-summary-card.disabled {
  background: #f5f6fa;
}

.certificate-summary-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.certificate-summary-actions {
  display: flex;
  gap: 0.5rem;
}

.certificate-summary-status {
  margin: 0.35rem 0 0;
  color: #2e3d64;
  font-size: 0.9rem;
}

.certificate-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(10, 18, 40, 0.5);
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 1.5rem;
  z-index: 1000;
  overflow-y: auto;
}

.certificate-modal {
  width: min(920px, 100%);
  background: #fff;
  border-radius: 10px;
  padding: 1.25rem;
  box-shadow: 0 20px 36px rgba(10, 18, 40, 0.25);
}

.certificate-modal-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.9rem;
}

.certificate-modal-head h3 {
  margin: 0;
}

.certificate-mode-switch {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.certificate-mode-switch .btn-secondary.active {
  background: #4760ff;
  color: #fff;
}

.template-tools {
  border: 1px dashed #cfd8ee;
  border-radius: 8px;
  padding: 0.75rem;
  margin-bottom: 1rem;
}

.placeholder-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin: 0.45rem 0 0.75rem;
}

.chip {
  border: 1px solid #c4d0f7;
  background: #eef3ff;
  color: #334a9f;
  border-radius: 999px;
  font-size: 0.78rem;
  padding: 0.2rem 0.55rem;
  cursor: pointer;
}

.logo-upload-row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0.75rem;
}

.logo-preview {
  margin-top: 0.75rem;
  max-width: 220px;
  max-height: 80px;
  object-fit: contain;
  border: 1px solid #d9e0ef;
  border-radius: 6px;
  background: #fff;
  padding: 0.35rem;
}

.template-preview-card {
  border: 1px solid #dce3f2;
  border-radius: 8px;
  background: #f8faff;
  padding: 1rem;
  margin-bottom: 1rem;
}

.template-preview-label {
  margin: 0;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #4c63dd;
  font-weight: 700;
}

.template-preview-card h3 {
  margin: 0.5rem 0;
}

.template-logo {
  max-width: 180px;
  max-height: 70px;
  object-fit: contain;
  display: block;
  margin: 0 auto 0.6rem;
}

.template-preview-meta {
  margin-top: 0.8rem;
}

.template-preview-meta p {
  margin: 0.2rem 0;
}

.template-preview-footer {
  margin-top: 0.8rem;
  color: #2e3d64;
  font-weight: 600;
}

textarea.form-control {
  resize: vertical;
}

.form-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.25rem;
}

.btn-primary,
.btn-secondary,
.btn-danger {
  border: none;
  border-radius: 6px;
  padding: 0.7rem 1rem;
  font-size: 0.95rem;
  cursor: pointer;
}

.btn-primary {
  background-color: #4760ff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2f4df2;
}

.btn-secondary {
  background-color: #e7eaf1;
  color: #243;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #d8dde8;
}

.btn-danger {
  background-color: #f03e3e;
  color: white;
}

.btn-danger:hover {
  background-color: #dd2c2c;
}

.btn-primary:disabled,
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.alert {
  margin-top: 1rem;
  padding: 0.8rem 1rem;
  border-radius: 6px;
}

.alert-error {
  background-color: #fff0f0;
  color: #9f2323;
  border: 1px solid #ffc9c9;
}

.alert-success {
  background-color: #ecfdf3;
  color: #1b6b40;
  border: 1px solid #b7efcc;
}

.question-form {
  border: 1px solid #ebedf3;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.question-card {
  border: 1px solid #ebedf3;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.question-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.question-actions {
  display: flex;
  gap: 0.5rem;
}

.edit-question-wrap {
  width: 100%;
}

.question-head h3 {
  margin: 0;
}

.question-meta {
  margin: 0.5rem 0 1rem;
  color: #667;
  font-size: 0.9rem;
}

.question-label {
  margin: 0 0 0.25rem;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #4c63dd;
  font-weight: 700;
}

.collapsed-note {
  color: #667;
  font-size: 0.9rem;
  margin-top: 0.75rem;
}

.subsection {
  border-top: 1px solid #eef1f7;
  margin-top: 1rem;
  padding-top: 1rem;
}

.subsection h4 {
  margin: 0 0 0.75rem;
}

.inline-form {
  margin-top: 0.75rem;
}

.media-list,
.answer-list {
  margin: 0;
  padding-left: 1.1rem;
}

.muted,
.empty-state {
  color: #667;
}

.correct-tag {
  margin-left: 0.5rem;
  background: #daf5e5;
  color: #1b6b40;
  border-radius: 999px;
  padding: 0.1rem 0.45rem;
  font-size: 0.75rem;
}

.answer-view-row,
.answer-edit-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.answer-edit-row {
  flex-wrap: wrap;
}

.inline-checkbox {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.preview-panel {
  border: 1px solid #d7deef;
  border-radius: 10px;
  padding: 1rem;
  margin-top: 1rem;
  background: #fbfcff;
}

.preview-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.preview-head h3 {
  margin: 0;
}

.preview-meta {
  color: #59617a;
  font-size: 0.9rem;
  margin-top: 0.6rem;
}

.preview-step-tag {
  margin-left: 0.5rem;
  background: #e9edff;
  color: #2f4df2;
  border-radius: 999px;
  padding: 0.15rem 0.5rem;
  font-size: 0.75rem;
}

.preview-content {
  border-top: 1px solid #e8ebf5;
  margin-top: 0.9rem;
  padding-top: 0.9rem;
}

.preview-read-box {
  background: #f5f8ff;
  border: 1px solid #e0e8ff;
  border-radius: 8px;
  padding: 0.8rem;
}

.preview-note {
  color: #5d6380;
  margin: 0.5rem 0 0;
}

.preview-media-wrap {
  margin-bottom: 0.8rem;
}

.preview-media-item {
  margin-bottom: 0.7rem;
}

.preview-image,
.preview-video,
.preview-audio {
  width: 100%;
}

.preview-answers {
  display: grid;
  gap: 0.5rem;
}

.preview-answer-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border: 1px solid #e5e8f2;
  border-radius: 8px;
  padding: 0.6rem;
}

.preview-actions {
  margin-top: 0.9rem;
  display: flex;
  justify-content: flex-end;
}

@media (max-width: 760px) {
  .quiz-create {
    padding: 1rem;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .question-head {
    flex-direction: column;
  }

  .builder-header,
  .preview-head {
    flex-direction: column;
    align-items: flex-start;
  }

  .certificate-summary-head,
  .certificate-summary-actions,
  .certificate-modal-head,
  .certificate-mode-switch {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
