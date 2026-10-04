<template>
  <div class="page">
    <div class="container">

      <div class="page-head">
        <h1>My Profile</h1>
        <p>Manage your personal account information</p>
      </div>

      <div class="grid">

        <!-- PHOTO + BASIC INFO -->
        <div class="panel">
          <h3 class="panel-title">Personal Information</h3>

          <div class="photo-row">
            <div class="upload-avatar" @click="$refs.photoInput.click()">
              <img v-if="photoPreview" :src="photoPreview" alt="avatar" />
              <span v-else class="avatar-initials">{{ initials }}</span>
              <div class="upload-overlay">
                <svg width="16" height="16" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                  <rect x="3" y="3" width="18" height="18" rx="2"/>
                  <circle cx="8.5" cy="8.5" r="1.5"/>
                  <polyline points="21 15 16 10 5 21"/>
                </svg>
              </div>
            </div>
            <input ref="photoInput" type="file" accept="image/*" style="display:none" @change="onPhotoFile" />
            <div class="photo-hint">
              <strong>Profile Photo</strong>
              <span>Click the photo to upload a new one</span>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>First Name</label>
              <input v-model="form.first_name" type="text" />
            </div>
            <div class="form-group">
              <label>Last Name</label>
              <input v-model="form.last_name" type="text" />
            </div>
          </div>

          <!-- EMAIL (with change-verification flow) -->
          <div class="form-group">
            <label>Email</label>
            <input :value="form.email" type="email" disabled />

            <!-- pending change awaiting confirmation -->
            <div v-if="form.pending_email" class="email-pending">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
              </svg>
              <span>Verification sent to <strong>{{ form.pending_email }}</strong> — check that inbox to confirm.</span>
              <button type="button" class="link-btn" @click="cancelPendingEmail" :disabled="cancellingEmail">
                Cancel
              </button>
            </div>

            <!-- trigger -->
            <button
              v-else-if="!emailEditing"
              type="button"
              class="link-btn"
              @click="emailEditing = true"
            >
              Change email
            </button>
            <p v-if="!emailEditing && !form.pending_email" class="field-hint">
              Changing your email requires confirming a link sent to the new address before it takes effect.
            </p>

            <!-- edit box -->
            <div v-if="emailEditing && !form.pending_email" class="email-edit-box">
              <div class="form-group">
                <label>New Email</label>
                <input v-model="newEmail" type="email" placeholder="new.email@example.com" />
              </div>
              <div class="form-group" style="margin-bottom: 10px;">
                <label>Current Password</label>
                <input v-model="emailPassword" type="password" />
              </div>

              <p v-if="emailError" class="error-text">{{ emailError }}</p>

              <div class="email-edit-actions">
                <button type="button" class="btn-cancel-sm" @click="closeEmailEdit">Cancel</button>
                <button type="button" class="btn-save-sm" @click="requestEmailChange" :disabled="sendingEmailRequest">
                  <span v-if="!sendingEmailRequest">Send Verification Link</span>
                  <Spinner v-else :size="14" color="#fff" track="rgba(255,255,255,0.35)" />
                </button>
              </div>
            </div>

            <p v-if="emailSuccess" class="success-text">{{ emailSuccess }}</p>
          </div>

          <div class="form-group">
            <label>Phone</label>
            <input v-model="form.phone" type="text" placeholder="e.g. 09171234567" />
          </div>

          
          <p v-if="infoError" class="error-text">{{ infoError }}</p>
          <p v-if="infoSuccess" class="success-text">{{ infoSuccess }}</p>

          <button class="btn-save" @click="saveInfo" :disabled="savingInfo">
            <span v-if="!savingInfo">Save Changes</span>
            <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
          </button>
        </div>

        <!-- PASSWORD -->
        <div class="panel">
          <h3 class="panel-title">Change Password</h3>

          <div class="form-group">
            <label>Current Password</label>
            <input v-model="passwordForm.current_password" type="password" />
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input v-model="passwordForm.new_password" type="password" placeholder="At least 12 characters" />
            <p class="field-hint">Must be at least 12 characters and include a number and a special character.</p>
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input v-model="passwordForm.new_password_confirmation" type="password" />
          </div>

          <p v-if="passwordError" class="error-text">{{ passwordError }}</p>
          <p v-if="passwordSuccess" class="success-text">{{ passwordSuccess }}</p>

          <button class="btn-save" @click="savePassword" :disabled="savingPassword">
            <span v-if="!savingPassword">Update Password</span>
            <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
          </button>
        </div>

      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import Spinner from '../components/foundation-admin/spinner.vue'

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  pending_email: '',
  phone: '',
  
})

const photoPreview = ref(null)
const photoFile     = ref(null)

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const savingInfo      = ref(false)
const infoError       = ref('')
const infoSuccess     = ref('')

const savingPassword    = ref(false)
const passwordError     = ref('')
const passwordSuccess   = ref('')

/* ── EMAIL CHANGE ── */
const emailEditing        = ref(false)
const newEmail            = ref('')
const emailPassword       = ref('')
const emailError          = ref('')
const emailSuccess        = ref('')
const sendingEmailRequest = ref(false)
const cancellingEmail     = ref(false)

const initials = computed(() => {
  if (!form.value.first_name) return 'FA'
  return [form.value.first_name, form.value.last_name]
    .filter(Boolean)
    .map(w => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

function getImage(path) {
  if (!path) return null
  return `http://127.0.0.1:8000/storage/${path}`
}

async function loadProfile() {
  try {
    const res = await api.get('/foundation/profile')
    const u = res.data
    form.value = {
      first_name:    u.first_name    || '',
      last_name:     u.last_name     || '',
      email:         u.email         || '',
      pending_email: u.pending_email || '',
      phone:         u.phone         || '',
      
    }
    photoPreview.value = getImage(u.profile_photo)
  } catch (err) {
    console.error('Failed to load profile:', err)
  }
}

onMounted(loadProfile)

function onPhotoFile(e) {
  const f = e.target.files[0]
  if (!f) return
  photoFile.value = f
  photoPreview.value = URL.createObjectURL(f)
}

function syncSessionUser(updatedUser) {
  sessionStorage.setItem('user', JSON.stringify(updatedUser))
  window.dispatchEvent(new CustomEvent('user-updated'))
}

async function saveInfo() {
  infoError.value = ''
  infoSuccess.value = ''
  savingInfo.value = true
  try {
    let updatedUser = null

    const res = await api.post('/foundation/profile', {
      first_name: form.value.first_name,
      last_name: form.value.last_name,
      phone: form.value.phone || '',
      address: form.value.address || '',
    })
    updatedUser = res.data.user

    if (photoFile.value) {
      const fd = new FormData()
      fd.append('profile_photo', photoFile.value)
      const photoRes = await api.post('/foundation/profile/photo', fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      updatedUser = photoRes.data.user
      photoFile.value = null
    }

    syncSessionUser(updatedUser)
    infoSuccess.value = 'Profile updated successfully.'
  } catch (err) {
    const errors = err.response?.data?.errors
    infoError.value = errors
      ? Object.values(errors).flat().join(' · ')
      : err.response?.data?.message || 'Failed to update profile.'
  } finally {
    savingInfo.value = false
  }
}

async function savePassword() {
  passwordError.value = ''
  passwordSuccess.value = ''

  if (!passwordForm.value.current_password) {
    passwordError.value = 'Please enter your current password.'
    return
  }
  if (passwordForm.value.new_password.length < 12) {
    passwordError.value = 'New password must be at least 12 characters.'
    return
  }
  if (!/[0-9]/.test(passwordForm.value.new_password)) {
    passwordError.value = 'New password must include at least one number.'
    return
  }
  if (!/[^A-Za-z0-9]/.test(passwordForm.value.new_password)) {
    passwordError.value = 'New password must include at least one special character.'
    return
  }
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    passwordError.value = 'New passwords do not match.'
    return
  }

  savingPassword.value = true
  try {
    await api.post('/foundation/profile', {
      current_password: passwordForm.value.current_password,
      new_password: passwordForm.value.new_password,
      new_password_confirmation: passwordForm.value.new_password_confirmation,
    })

    passwordSuccess.value = 'Password updated successfully.'
    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  } catch (err) {
    const errors = err.response?.data?.errors
    passwordError.value = errors
      ? Object.values(errors).flat().join(' · ')
      : err.response?.data?.message || 'Failed to update password.'
  } finally {
    savingPassword.value = false
  }
}

/* ── EMAIL CHANGE ── */
function closeEmailEdit() {
  emailEditing.value = false
  newEmail.value = ''
  emailPassword.value = ''
  emailError.value = ''
}

async function requestEmailChange() {
  emailError.value = ''
  emailSuccess.value = ''

  if (!newEmail.value) {
    emailError.value = 'Please enter a new email address.'
    return
  }
  if (!emailPassword.value) {
    emailError.value = 'Please enter your current password.'
    return
  }

  sendingEmailRequest.value = true
  try {
    const res = await api.post('/foundation/profile/email/request', {
      new_email: newEmail.value,
      current_password: emailPassword.value,
    })
    form.value.pending_email = res.data.pending_email
    emailSuccess.value = res.data.message
    closeEmailEdit()
  } catch (err) {
    const errors = err.response?.data?.errors
    emailError.value = errors
      ? Object.values(errors).flat().join(' · ')
      : err.response?.data?.message || 'Failed to request email change.'
  } finally {
    sendingEmailRequest.value = false
  }
}

async function cancelPendingEmail() {
  cancellingEmail.value = true
  try {
    await api.post('/foundation/profile/email/cancel')
    form.value.pending_email = ''
    emailSuccess.value = ''
  } catch (err) {
    console.error('Failed to cancel pending email change:', err)
  } finally {
    cancellingEmail.value = false
  }
}
</script>

<style scoped>
.page { background: #f0f4f9; min-height: 100%; box-sizing: border-box; }
.container { max-width: 900px; }

.page-head { margin-bottom: 24px; }
.page-head h1 { font-size: 1.3rem; font-weight: 800; color: #133c8a; margin: 0 0 4px; }
.page-head p  { font-size: 0.85rem; color: #64748b; margin: 0; }

.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; align-items: start; }

.panel { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 1px 4px rgba(15,45,82,0.06); }
.panel-title { font-size: 1rem; font-weight: 800; color: #133c8a; margin: 0 0 18px; }

.photo-row { display: flex; align-items: center; gap: 16px; margin-bottom: 22px; }

.upload-avatar {
  width: 72px; height: 72px; border-radius: 50%;
  background: linear-gradient(135deg, #1a56c4, #133c8a);
  display: flex; align-items: center; justify-content: center;
  position: relative; cursor: pointer; overflow: hidden; flex-shrink: 0;
  border: 3px solid #eef4ff;
}
.upload-avatar img { width: 100%; height: 100%; object-fit: cover; }
.avatar-initials { color: white; font-size: 1.3rem; font-weight: 800; }

.upload-overlay {
  position: absolute; inset: 0; background: rgba(15,45,82,0.5);
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.2s;
}
.upload-avatar:hover .upload-overlay { opacity: 1; }

.photo-hint { display: flex; flex-direction: column; gap: 2px; }
.photo-hint strong { font-size: 0.87rem; color: #133c8a; }
.photo-hint span { font-size: 0.76rem; color: #94a3b8; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
.form-group input {
  width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px;
  font-size: 0.87rem; outline: none; box-sizing: border-box; font-family: inherit; transition: border-color 0.2s;
}
.form-group input:focus { border-color: #1a56c4; box-shadow: 0 0 0 3px rgba(26,86,196,0.08); }
.form-group input:disabled { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }

.field-hint { font-size: 0.75rem; color: #94a3b8; margin: 6px 0 0; }

.error-text { font-size: 0.82rem; color: #cc3333; background: #fff5f5; border: 1px solid #ffd0d0; border-radius: 8px; padding: 9px 13px; margin-bottom: 12px; }
.success-text { font-size: 0.82rem; color: #059669; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 9px 13px; margin-bottom: 12px; }

.btn-save {
  width: 100%; padding: 11px; border: none; border-radius: 10px;
  background: #1a56c4; color: white; font-size: 0.87rem; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; transition: background 0.2s;
}
.btn-save:hover:not(:disabled) { background: #133c8a; }
.btn-save:disabled { opacity: 0.65; cursor: not-allowed; }

/* EMAIL CHANGE */
.link-btn {
  margin-top: 8px; background: none; border: none; padding: 0;
  font-size: 0.78rem; font-weight: 700; color: #1a56c4; cursor: pointer;
}
.link-btn:hover { text-decoration: underline; }
.link-btn:disabled { opacity: 0.6; cursor: not-allowed; }

.email-pending {
  display: flex; align-items: flex-start; gap: 8px;
  margin-top: 8px; padding: 10px 12px;
  background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;
  font-size: 0.78rem; color: #1e3a8a; line-height: 1.5;
}
.email-pending svg { flex-shrink: 0; margin-top: 1px; }
.email-pending .link-btn { margin-top: 0; margin-left: auto; color: #1e3a8a; flex-shrink: 0; }

.email-edit-box {
  margin-top: 12px; padding: 14px;
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
}
.email-edit-box .form-group:last-of-type { margin-bottom: 0; }
.email-edit-actions { display: flex; gap: 8px; justify-content: flex-end; }

.btn-cancel-sm, .btn-save-sm {
  padding: 8px 16px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.btn-cancel-sm { border: 1px solid #e2e8f0; background: white; color: #64748b; }
.btn-save-sm { border: none; background: #1a56c4; color: white; min-width: 150px; }
.btn-save-sm:hover:not(:disabled) { background: #133c8a; }
.btn-save-sm:disabled { opacity: 0.65; cursor: not-allowed; }

@media (max-width: 760px) {
  .grid { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
}
</style>