<template>
  <div class="page">
    <div class="container">

      <div class="page-head">
        <h1>My Profile</h1>
        <p>Manage your account information</p>
      </div>

      <div v-if="isLoading" class="loading-wrap">
        <Spinner :size="32" />
      </div>

      <div v-else class="profile-layout">

        <!-- LEFT: AVATAR CARD -->
        <div class="side-col">
          <div class="avatar-card">

            <div class="avatar-wrap" @click="triggerPhotoPick">
              <img
                v-if="user.profile_photo"
                :src="getImage(user.profile_photo)"
                class="avatar-img"
                alt="Profile photo"
              />
              <div v-else class="avatar-circle" :style="{ background: avatarColor }">
                {{ initials }}
              </div>

              <div class="avatar-overlay">
                <svg width="18" height="18" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                  <circle cx="12" cy="13" r="4"/>
                </svg>
              </div>

              <div v-if="photoUploading" class="avatar-loading">
                <Spinner :size="18" color="#fff" track="rgba(255,255,255,0.35)" />
              </div>
            </div>

            <input
              ref="photoInput"
              type="file"
              accept="image/*"
              class="hidden-input"
              @change="onPhotoSelected"
            />

            <p v-if="photoError" class="photo-error-text">{{ photoError }}</p>

            <div class="avatar-name">{{ user.first_name }} {{ user.last_name }}</div>
            <div class="avatar-email">{{ user.email }}</div>
            <span class="avatar-role">Donor</span>

            <div class="avatar-stats">
              <div class="as-item">
                <span class="as-val">{{ donorStats.total_donations ?? 0 }}</span>
                <span class="as-label">Donations</span>
              </div>
              <div class="as-item">
                <span class="as-val">₱{{ formatMoney(donorStats.total_donated) }}</span>
                <span class="as-label">Contributed</span>
              </div>
            </div>
          </div>

          <!-- QUICK LINKS -->
          <div class="quick-links">
            <router-link to="/donor/donations"     class="ql-item">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              My Donations
            </router-link>
            <router-link to="/donor/campaigns"     class="ql-item">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              Browse Campaigns
            </router-link>
            <router-link to="/donor/notifications" class="ql-item">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              Notifications
            </router-link>
          </div>
        </div>

        <!-- RIGHT: FORMS -->
        <div class="main-col">

          <!-- PERSONAL INFO -->
          <div class="panel">
            <div class="panel-head">
              <h3 class="panel-title">Personal Information</h3>
            </div>
            <div class="panel-body">

              <div class="form-row">
                <div class="form-group">
                  <label>First Name</label>
                  <input v-model="form.first_name" type="text" placeholder="First name" />
                </div>
                <div class="form-group">
                  <label>Last Name</label>
                  <input v-model="form.last_name" type="text" placeholder="Last name" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Phone</label>
                  <input v-model="form.phone" type="tel" placeholder="Phone number" />
                </div>
                <div class="form-group">
                  <label>Gender</label>
                  <select v-model="form.gender">
                    <option value="">Prefer not to say</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="prefer_not_to_say">Prefer not to say</option>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Birthdate</label>
                  <input v-model="form.birthdate" type="date" />
                </div>
                <div class="form-group">
                  <label>Email</label>
                  <input :value="user.email" type="email" disabled class="disabled-input" />
                </div>
              </div>

              <div class="form-group">
                <label>Address</label>
                <input v-model="form.address" type="text" placeholder="Your address" />
              </div>

              <p v-if="infoSuccess" class="success-text">{{ infoSuccess }}</p>
              <p v-if="infoError"   class="error-text">{{ infoError }}</p>

              <div class="form-actions">
                <button class="btn-save" @click="saveInfo" :disabled="infoSaving">
                  <span v-if="!infoSaving">Save Changes</span>
                  <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
                </button>
              </div>

            </div>
          </div>

          <!-- CHANGE EMAIL -->
          <div class="panel" style="margin-top: 20px;">
            <div class="panel-head">
              <h3 class="panel-title">Change Email</h3>
            </div>
            <div class="panel-body">

              <div v-if="user.pending_email" class="pending-box">
                <div class="pending-text">
                  <strong>Waiting for confirmation</strong>
                  <span>
                    We sent a link to {{ user.pending_email }}. Your email won't change until you click it.
                  </span>
                </div>
                <button
                  class="btn-cancel-change"
                  type="button"
                  @click="cancelEmailChange"
                  :disabled="emailCancelling"
                >
                  Cancel
                </button>
              </div>

              <div class="form-group">
                <label>New Email</label>
                <input
                  v-model="emailForm.new_email"
                  type="email"
                  placeholder="Enter your new email address"
                />
              </div>

              <div class="form-group">
                <label>Current Password</label>
                <div class="pass-wrap">
                  <input
                    v-model="emailForm.current_password"
                    :type="showEmailPass ? 'text' : 'password'"
                    placeholder="Confirm it's you"
                  />
                  <button class="eye-btn" type="button" @click="showEmailPass = !showEmailPass">
                    👁
                  </button>
                </div>
              </div>

              <p v-if="emailSuccess" class="success-text">{{ emailSuccess }}</p>
              <p v-if="emailError"   class="error-text">{{ emailError }}</p>

              <div class="form-actions">
                <button class="btn-save" @click="requestEmailChange" :disabled="emailSaving">
                  <span v-if="!emailSaving">Send Verification Link</span>
                  <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
                </button>
              </div>

            </div>
          </div>

          <!-- CHANGE PASSWORD -->
          <div class="panel" style="margin-top: 20px;">
            <div class="panel-head">
              <h3 class="panel-title">Change Password</h3>
            </div>
            <div class="panel-body">

              <div class="form-group">
                <label>New Password</label>
                <div class="pass-wrap">
                  <input
                    v-model="passForm.password"
                    :type="showPass ? 'text' : 'password'"
                    placeholder="Min 8 characters"
                  />
                  <button class="eye-btn" type="button" @click="showPass = !showPass">
                    👁
                  </button>
                </div>
              </div>

              <div class="form-group">
                <label>Confirm New Password</label>
                <div class="pass-wrap">
                  <input
                    v-model="passForm.password_confirmation"
                    :type="showConfirm ? 'text' : 'password'"
                    placeholder="Repeat password"
                  />
                  <button class="eye-btn" type="button" @click="showConfirm = !showConfirm">
                    👁
                  </button>
                </div>
              </div>

              <p v-if="passSuccess" class="success-text">{{ passSuccess }}</p>
              <p v-if="passError"   class="error-text">{{ passError }}</p>

              <div class="form-actions">
                <button class="btn-save" @click="savePassword" :disabled="passSaving">
                  <span v-if="!passSaving">Update Password</span>
                  <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
                </button>
              </div>

            </div>
          </div>

          <!-- DANGER ZONE -->
          <div class="panel danger-panel" style="margin-top: 20px;">
            <div class="panel-head">
              <h3 class="panel-title" style="color:#dc2626;">Account</h3>
            </div>
            <div class="panel-body">
              <div class="danger-row">
                <div>
                  <div class="danger-title">Log out of all devices</div>
                  <div class="danger-desc">This will end all your active sessions.</div>
                </div>
                <button class="btn-logout" @click="logout">Logout</button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import Spinner from '../components/donor/spinner.vue'
import { getImage, formatMoney } from '../composable/donor.js'

const router    = useRouter()
const isLoading = ref(false)
const user      = ref({})
const donorStats = ref({ total_donations: 0, total_donated: 0 })

const infoSaving = ref(false)
const infoSuccess = ref('')
const infoError   = ref('')

const passSaving = ref(false)
const passSuccess = ref('')
const passError   = ref('')
const showPass    = ref(false)
const showConfirm = ref(false)

const emailSaving     = ref(false)
const emailCancelling = ref(false)
const emailSuccess    = ref('')
const emailError      = ref('')
const showEmailPass   = ref(false)

const photoInput     = ref(null)
const photoUploading = ref(false)
const photoError     = ref('')

const form = reactive({
  first_name: '',
  last_name:  '',
  phone:      '',
  gender:     '',
  birthdate:  '',
  address:    '',
})

const passForm = reactive({
  password:              '',
  password_confirmation: '',
})

const emailForm = reactive({
  new_email:        '',
  current_password: '',
})

/* ── LOAD ── */
async function load() {
  isLoading.value = true
  try {
    const [profileRes, donationsRes] = await Promise.all([
      api.get('/donor/profile'),
      api.get('/donor/donations'),
    ])

    user.value = profileRes.data

    const donations = Array.isArray(donationsRes.data) ? donationsRes.data : []
    donorStats.value = {
      total_donations: donations.length,
      total_donated:   donations
        .filter(d => d.type === 'monetary')
        .reduce((s, d) => s + Number(d.amount || 0), 0),
    }

    // fill form
    form.first_name = user.value.first_name || ''
    form.last_name  = user.value.last_name  || ''
    form.phone      = user.value.phone      || ''
    form.gender     = user.value.gender     || ''
    form.birthdate  = user.value.birthdate  ? user.value.birthdate.substring(0, 10) : ''
    form.address    = user.value.address    || ''

  } catch (err) {
    console.error(err)
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

/* ── SAVE INFO ── */
async function saveInfo() {
  infoSuccess.value = ''
  infoError.value   = ''
  infoSaving.value  = true
  try {
    const res = await api.post('/donor/profile', {
      first_name: form.first_name,
      last_name:  form.last_name,
      phone:      form.phone      || null,
      gender:     form.gender     || null,
      birthdate:  form.birthdate  || null,
      address:    form.address    || null,
    })
    user.value = res.data.user

    // update session storage + notify other components (e.g. topbar)
    updateStoredUser(res.data.user)

    infoSuccess.value = 'Profile updated successfully!'
    setTimeout(() => infoSuccess.value = '', 3000)
  } catch (err) {
    infoError.value = err.response?.data?.message || 'Failed to update profile.'
  } finally {
    infoSaving.value = false
  }
}

/* ── REQUEST EMAIL CHANGE ── */
async function requestEmailChange() {
  emailSuccess.value = ''
  emailError.value   = ''

  const newEmail = emailForm.new_email.trim()

  if (!newEmail) {
    emailError.value = 'Please enter your new email address.'
    return
  }
  if (!/^\S+@\S+\.\S+$/.test(newEmail)) {
    emailError.value = 'Please enter a valid email address.'
    return
  }
  if (!emailForm.current_password) {
    emailError.value = 'Please enter your current password.'
    return
  }

  emailSaving.value = true
  try {
    const res = await api.post('/donor/profile/email/request', {
      new_email:        newEmail,
      current_password: emailForm.current_password,
    })

    // show the "waiting for confirmation" box without touching the live email
    user.value = { ...user.value, pending_email: res.data.pending_email }

    emailForm.new_email        = ''
    emailForm.current_password = ''

    emailSuccess.value = res.data.message
    setTimeout(() => emailSuccess.value = '', 6000)
  } catch (err) {
    emailError.value = err.response?.data?.message || 'Failed to request email change.'
  } finally {
    emailSaving.value = false
  }
}

/* ── CANCEL PENDING EMAIL CHANGE ── */
async function cancelEmailChange() {
  emailSuccess.value = ''
  emailError.value   = ''
  emailCancelling.value = true
  try {
    await api.post('/donor/profile/email/cancel')
    user.value = { ...user.value, pending_email: null }
  } catch (err) {
    emailError.value = err.response?.data?.message || 'Failed to cancel email change.'
  } finally {
    emailCancelling.value = false
  }
}

/* ── SAVE PASSWORD ── */
async function savePassword() {
  passSuccess.value = ''
  passError.value   = ''

  if (!passForm.password) {
    passError.value = 'Please enter a new password.'
    return
  }
  if (passForm.password.length < 8) {
    passError.value = 'Password must be at least 8 characters.'
    return
  }
  if (passForm.password !== passForm.password_confirmation) {
    passError.value = 'Passwords do not match.'
    return
  }

  passSaving.value = true
  try {
    await api.post('/donor/profile', {
      password:              passForm.password,
      password_confirmation: passForm.password_confirmation,
    })
    passSuccess.value = 'Password updated successfully!'
    passForm.password              = ''
    passForm.password_confirmation = ''
    setTimeout(() => passSuccess.value = '', 3000)
  } catch (err) {
    passError.value = err.response?.data?.message || 'Failed to update password.'
  } finally {
    passSaving.value = false
  }
}

/* ── PROFILE PHOTO UPLOAD ── */
function triggerPhotoPick() {
  if (photoUploading.value) return
  photoInput.value?.click()
}

async function onPhotoSelected(e) {
  const file = e.target.files?.[0]
  if (!file) return

  photoError.value = ''

  if (!file.type.startsWith('image/')) {
    photoError.value = 'Please select an image file.'
    e.target.value = ''
    return
  }
  if (file.size > 4 * 1024 * 1024) {
    photoError.value = 'Image must be under 4MB.'
    e.target.value = ''
    return
  }

  photoUploading.value = true
  try {
    const formData = new FormData()
    formData.append('profile_photo', file)

    const res = await api.post('/donor/profile/photo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    user.value = res.data.user
    updateStoredUser(res.data.user)
  } catch (err) {
    photoError.value = err.response?.data?.message || 'Failed to upload photo.'
  } finally {
    photoUploading.value = false
    e.target.value = ''
  }
}

/* ── SYNC USER ACROSS APP (e.g. Topbar) ── */
function updateStoredUser(updatedUser) {
  sessionStorage.setItem('user', JSON.stringify(updatedUser))
  window.dispatchEvent(new CustomEvent('user-updated', { detail: updatedUser }))
}

/* ── LOGOUT ── */
async function logout() {
  try { await api.post('/logout') } catch {}
  sessionStorage.clear()
  router.push('/login')
}

/* ── HELPERS ── */
const initials = computed(() => {
  return ((user.value.first_name?.[0] || '') + (user.value.last_name?.[0] || '')).toUpperCase() || 'D'
})

const avatarColor = computed(() => {
  const colors = ['#1a8a52','#059669','#0284c7','#7c3aed','#db2777']
  const id = user.value.id || 1
  return colors[id % colors.length]
})
</script>

<style scoped>
.page { background: #c8f0e0; min-height: 100vh; padding-bottom: 40px; }
.container { max-width: 1100px; margin: 0 auto; padding: 32px 24px; }

.page-head { margin-bottom: 24px; }
.page-head h1 { font-size: 1.5rem; font-weight: 900; color: #0e5c36; margin: 0 0 4px; }
.page-head p  { font-size: 0.85rem; color: #475569; margin: 0; }

.loading-wrap { display: flex; justify-content: center; padding: 60px; }

.profile-layout { display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: start; }

/* SIDE */
.side-col { display: flex; flex-direction: column; gap: 16px; }

.avatar-card { background: white; border-radius: 18px; padding: 24px; text-align: center; box-shadow: 0 2px 8px rgba(0,80,40,0.08); }

.avatar-wrap {
  position: relative;
  width: 72px;
  height: 72px;
  margin: 0 auto 12px;
  border-radius: 20px;
  cursor: pointer;
  overflow: hidden;
}
.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 20px;
  display: block;
}
.avatar-circle { width: 100%; height: 100%; border-radius: 20px; color: white; font-size: 1.4rem; font-weight: 900; display: flex; align-items: center; justify-content: center; }

.avatar-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.15s;
  border-radius: 20px;
}
.avatar-wrap:hover .avatar-overlay { opacity: 1; }

.avatar-loading {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 20px;
}

.hidden-input { display: none; }

.photo-error-text {
  font-size: 0.72rem;
  color: #dc2626;
  margin: -4px 0 10px;
}

.avatar-name  { font-size: 1rem; font-weight: 800; color: #0F2D52; }
.avatar-email { font-size: 0.78rem; color: #94a3b8; margin-top: 3px; margin-bottom: 8px; }
.avatar-role  { font-size: 0.72rem; font-weight: 700; background: #f0fdf4; color: #1a8a52; padding: 3px 12px; border-radius: 20px; }

.avatar-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; }
.as-item  { background: #f8fafc; border-radius: 10px; padding: 10px; }
.as-val   { display: block; font-size: 1rem; font-weight: 800; color: #0F2D52; }
.as-label { font-size: 0.7rem; color: #94a3b8; }

.quick-links { background: white; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,80,40,0.08); }
.ql-item { display: flex; align-items: center; gap: 10px; padding: 13px 16px; font-size: 0.85rem; font-weight: 600; color: #475569; text-decoration: none; border-bottom: 1px solid #f1f5f9; transition: all 0.15s; }
.ql-item:last-child { border-bottom: none; }
.ql-item:hover { background: #f0fdf4; color: #1a8a52; }

/* MAIN */
.main-col { display: flex; flex-direction: column; }

.panel { background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,80,40,0.08); overflow: hidden; }
.panel-head  { padding: 16px 22px 12px; border-bottom: 1px solid #f1f5f9; }
.panel-title { font-size: 0.95rem; font-weight: 800; color: #0F2D52; margin: 0; }
.panel-body  { padding: 22px; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 0.82rem; font-weight: 700; color: #475569; margin-bottom: 6px; }
.form-group input,
.form-group select {
  width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 0.87rem; outline: none; box-sizing: border-box; font-family: inherit; transition: border-color 0.2s;
}
.form-group input:focus,
.form-group select:focus { border-color: #1a8a52; box-shadow: 0 0 0 3px rgba(26,138,82,0.1); }
.disabled-input { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }

.pass-wrap { position: relative; }
.pass-wrap input { padding-right: 44px; }
.eye-btn { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); border: none; background: transparent; cursor: pointer; font-size: 1rem; }

.success-text { font-size: 0.82rem; color: #059669; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 9px 13px; margin-bottom: 8px; }
.error-text   { font-size: 0.82rem; color: #cc3333; background: #fff5f5; border: 1px solid #ffd0d0; border-radius: 8px; padding: 9px 13px; margin-bottom: 8px; }

.form-actions { display: flex; justify-content: flex-end; }
.btn-save { padding: 10px 24px; border: none; background: #1a8a52; color: white; border-radius: 10px; font-size: 0.88rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; min-width: 140px; transition: background 0.2s; }
.btn-save:hover:not(:disabled) { background: #157042; }
.btn-save:disabled { opacity: 0.65; cursor: not-allowed; }

/* PENDING EMAIL CHANGE (new — used only by the Change Email panel) */
.pending-box { display: flex; justify-content: space-between; align-items: center; gap: 14px; background: #fefce8; border: 1px solid #fde68a; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; }
.pending-text { display: flex; flex-direction: column; gap: 2px; font-size: 0.8rem; color: #92400e; line-height: 1.5; }
.pending-text strong { font-size: 0.82rem; }
.btn-cancel-change { padding: 7px 16px; border: 1.5px solid #d97706; background: white; color: #92400e; border-radius: 8px; font-size: 0.8rem; font-weight: 700; cursor: pointer; white-space: nowrap; transition: background 0.15s; }
.btn-cancel-change:hover:not(:disabled) { background: #fffbeb; }
.btn-cancel-change:disabled { opacity: 0.65; cursor: not-allowed; }

/* DANGER */
.danger-panel .panel-head { background: #fff5f5; }
.danger-row   { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
.danger-title { font-size: 0.88rem; font-weight: 700; color: #dc2626; margin-bottom: 3px; }
.danger-desc  { font-size: 0.78rem; color: #94a3b8; }
.btn-logout   { padding: 9px 20px; border: 1.5px solid #dc2626; background: white; color: #dc2626; border-radius: 10px; font-size: 0.85rem; font-weight: 700; cursor: pointer; white-space: nowrap; transition: all 0.15s; }
.btn-logout:hover { background: #fef2f2; }

@media (max-width: 768px) {
  .profile-layout { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
}
</style>