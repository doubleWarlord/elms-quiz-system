<template>
  <section class="manage-users">
    <div class="card form-card">
      <h3>Create User</h3>
      <form @submit.prevent="createUser" class="form">
        <div class="row">
          <div class="field">
            <label>Name</label>
            <input v-model="form.name" type="text" required placeholder="Full name" />
          </div>
          <div class="field">
            <label>Email</label>
            <input v-model="form.email" type="email" required placeholder="user@example.com" />
          </div>
        </div>
        <div class="row">
          <div class="field">
            <label>Password</label>
            <input v-model="form.password" type="password" required minlength="8" placeholder="Min 8 characters" />
          </div>
          <div class="field">
            <label>Role</label>
            <select v-model="form.role" required>
              <option value="student">Student</option>
              <option value="teacher">Teacher</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div v-if="formError" class="alert-error">{{ formError }}</div>
        <div v-if="formSuccess" class="alert-success">{{ formSuccess }}</div>
        <button type="submit" :disabled="submitting">
          {{ submitting ? 'Creating...' : 'Create User' }}
        </button>
      </form>
    </div>

    <div class="card">
      <h3>All Users <span class="badge">{{ users.length }}</span></h3>
      <div v-if="loading" class="state">Loading users...</div>
      <div v-else-if="!users.length" class="state">No users found.</div>
      <table v-else class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Joined</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td class="muted">{{ user.id }}</td>
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td><span class="role-badge" :class="user.role">{{ user.role }}</span></td>
            <td class="muted">{{ formatDate(user.created_at) }}</td>
            <td>
              <button class="btn-edit" @click="startEdit(user)">Edit</button>
              <button
                class="btn-delete"
                :disabled="user.id === currentUserId"
                :title="user.id === currentUserId ? 'Cannot delete yourself' : 'Delete user'"
                @click="deleteUser(user)"
              >Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Edit Modal -->
  <div v-if="editTarget" class="modal-backdrop" @click.self="cancelEdit">
    <div class="modal">
      <h3>Edit User</h3>
      <form @submit.prevent="saveEdit" class="form">
        <div class="row">
          <div class="field">
            <label>Name</label>
            <input v-model="editForm.name" type="text" required />
          </div>
          <div class="field">
            <label>Email</label>
            <input v-model="editForm.email" type="email" required />
          </div>
        </div>
        <div class="row">
          <div class="field">
            <label>New Password <span class="hint">(leave blank to keep)</span></label>
            <input v-model="editForm.password" type="password" minlength="8" placeholder="Optional" />
          </div>
          <div class="field">
            <label>Role</label>
            <select v-model="editForm.role" required>
              <option value="student">Student</option>
              <option value="teacher">Teacher</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div v-if="editError" class="alert-error">{{ editError }}</div>
        <div class="modal-actions">
          <button type="submit" :disabled="editSaving">{{ editSaving ? 'Saving...' : 'Save Changes' }}</button>
          <button type="button" class="btn-cancel" @click="cancelEdit">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';

const users = ref([]);
const loading = ref(true);
const currentUserId = ref(null);

const form = ref({ name: '', email: '', password: '', role: 'student' });
const submitting = ref(false);
const formError = ref('');
const formSuccess = ref('');

const formatDate = (dateStr) =>
  new Date(dateStr).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

const fetchUsers = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/admin/users');
    users.value = response.data;
  } finally {
    loading.value = false;
  }
};

const createUser = async () => {
  submitting.value = true;
  formError.value = '';
  formSuccess.value = '';
  try {
    const response = await axios.post('/admin/users', form.value);
    users.value.unshift(response.data);
    formSuccess.value = `User "${response.data.name}" created successfully.`;
    form.value = { name: '', email: '', password: '', role: 'student' };
  } catch (err) {
    const errors = err.response?.data?.errors;
    formError.value = errors
      ? Object.values(errors).flat().join(' ')
      : err.response?.data?.message || 'Failed to create user.';
  } finally {
    submitting.value = false;
  }
};

const deleteUser = async (user) => {
  if (!confirm(`Delete "${user.name}"? This cannot be undone.`)) return;
  try {
    await axios.delete(`/admin/users/${user.id}`);
    users.value = users.value.filter((u) => u.id !== user.id);
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete user.');
  }
};

const editTarget = ref(null);
const editForm = ref({});
const editSaving = ref(false);
const editError = ref('');

const startEdit = (user) => {
  editTarget.value = user;
  editForm.value = { name: user.name, email: user.email, password: '', role: user.role };
  editError.value = '';
};

const cancelEdit = () => { editTarget.value = null; };

const saveEdit = async () => {
  editSaving.value = true;
  editError.value = '';
  try {
    const response = await axios.put(`/admin/users/${editTarget.value.id}`, editForm.value);
    const idx = users.value.findIndex((u) => u.id === editTarget.value.id);
    if (idx !== -1) users.value[idx] = response.data;
    editTarget.value = null;
  } catch (err) {
    const errors = err.response?.data?.errors;
    editError.value = errors
      ? Object.values(errors).flat().join(' ')
      : err.response?.data?.message || 'Failed to save changes.';
  } finally {
    editSaving.value = false;
  }
};

onMounted(async () => {
  const savedUser = localStorage.getItem('user');
  if (savedUser) {
    try {
      currentUserId.value = JSON.parse(savedUser).id;
    } catch (error) {
      localStorage.removeItem('user');
    }
  }

  if (!currentUserId.value) {
    const me = await axios.get('/auth/profile');
    currentUserId.value = me.data.id;
    localStorage.setItem('user', JSON.stringify(me.data));
  }

  await fetchUsers();
});
</script>

<style scoped>
.manage-users { display: flex; flex-direction: column; gap: 1rem; }
.card { background: #fff; border-radius: 12px; padding: 1.2rem; box-shadow: 0 1px 4px rgba(27,39,53,.08); }
.card h3 { margin: 0 0 1rem; font-size: 1rem; color: #20293a; }
.badge { background: #e8eef7; color: #3a5080; border-radius: 9999px; padding: .1rem .55rem; font-size: .8rem; font-weight: 600; }
.form .row { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; margin-bottom: .8rem; }
.field label { display: block; font-size: .85rem; color: #5d7088; margin-bottom: .3rem; }
.field input, .field select { width: 100%; padding: .6rem .75rem; border: 1px solid #cdd5e0; border-radius: 6px; font-size: .95rem; }
.field input:focus, .field select:focus { outline: none; border-color: #667eea; }
.form button[type='submit'] { margin-top: .5rem; background: #667eea; color: #fff; border: none; border-radius: 6px; padding: .65rem 1.4rem; font-size: .95rem; cursor: pointer; }
.form button[type='submit']:disabled { opacity: .6; cursor: not-allowed; }
.alert-error { color: #c53030; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 6px; padding: .5rem .8rem; margin-bottom: .5rem; font-size: .9rem; }
.alert-success { color: #276749; background: #f0fff4; border: 1px solid #c6f6d5; border-radius: 6px; padding: .5rem .8rem; margin-bottom: .5rem; font-size: .9rem; }
.table { width: 100%; border-collapse: collapse; font-size: .9rem; }
.table th, .table td { text-align: left; padding: .6rem .5rem; border-bottom: 1px solid #e8eef7; }
.table th { color: #5d7088; font-weight: 600; }
.muted { color: #8898aa; font-size: .85rem; }
.role-badge { display: inline-block; border-radius: 9999px; padding: .15rem .6rem; font-size: .78rem; font-weight: 600; text-transform: capitalize; }
.role-badge.admin { background: #ffeeba; color: #7a4100; }
.role-badge.teacher { background: #bee3f8; color: #1a365d; }
.role-badge.student { background: #c6f6d5; color: #1c4532; }
.btn-delete { background: none; border: 1px solid #fed7d7; color: #c53030; border-radius: 4px; padding: .25rem .6rem; cursor: pointer; font-size: .82rem; }
.btn-delete:hover:not(:disabled) { background: #fff5f5; }
.btn-delete:disabled { opacity: .35; cursor: not-allowed; }
.btn-edit { background: none; border: 1px solid #bee3f8; color: #2b6cb0; border-radius: 4px; padding: .25rem .6rem; cursor: pointer; font-size: .82rem; margin-right: .3rem; }
.btn-edit:hover { background: #ebf8ff; }
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal { background: #fff; border-radius: 12px; padding: 1.8rem; width: 520px; max-width: 95vw; box-shadow: 0 8px 30px rgba(0,0,0,.18); }
.modal h3 { margin: 0 0 1.2rem; }
.modal-actions { display: flex; gap: .8rem; margin-top: .8rem; }
.modal-actions button[type='submit'] { background: #667eea; color: #fff; border: none; border-radius: 6px; padding: .6rem 1.2rem; cursor: pointer; font-size: .95rem; }
.modal-actions button[type='submit']:disabled { opacity: .6; cursor: not-allowed; }
.btn-cancel { background: #e2e8f0; color: #333; border: none; border-radius: 6px; padding: .6rem 1.2rem; cursor: pointer; font-size: .95rem; }
.hint { color: #8898aa; font-size: .78rem; font-weight: 400; }
.state { color: #5d7088; padding: .5rem 0; }
</style>
