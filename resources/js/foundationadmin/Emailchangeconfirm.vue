<template>
  <div class="confirm-page">
    <div class="confirm-card">
      <div class="confirm-icon" :class="iconClass">
        <svg v-if="status === 'loading' || status === 'working'" class="spin" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" opacity="0.25"/>
          <path d="M12 2a10 10 0 0 1 10 10"/>
        </svg>
        <svg v-else-if="status === 'ready'" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <rect x="3" y="5" width="18" height="14" rx="2"/>
          <polyline points="3 7 12 13 21 7"/>
        </svg>
        <svg v-else-if="status === 'success'" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        <svg v-else width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </div>

      <h2>{{ heading }}</h2>

      <!-- fallback: link opened without a button choice, so ask here -->
      <template v-if="showChoice">
        <p>
          You asked to change the email on your FoundationLink account to
          <strong class="new-email">{{ newEmail }}</strong>.
          Is this you?
        </p>

        <div class="btn-row">
          <button
            type="button"
            class="btn-no"
            :disabled="status === 'working'"
            @click="decline"
          >
            No, cancel
          </button>
          <button
            type="button"
            class="btn-yes"
            :disabled="status === 'working'"
            @click="confirm"
          >
            Yes, confirm
          </button>
        </div>
      </template>

      <!-- loading / success / cancelled / error -->
      <template v-else>
        <p>{{ message }}</p>

        <router-link v-if="status !== 'loading' && status !== 'working'" to="/login" class="btn-back">
          Back to Login
        </router-link>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const token = route.params.token

// loading | ready | working | success | cancelled | error
const status     = ref('loading')
const message    = ref('')
const newEmail   = ref('')
const showChoice = ref(false)

const heading = computed(() => {
  switch (status.value) {
    case 'loading':   return 'Checking your link…'
    case 'ready':     return 'Confirm email change'
    case 'working':   return showChoice.value ? 'Confirm email change' : 'Please wait…'
    case 'success':   return 'Email updated'
    case 'cancelled': return 'Change cancelled'
    default:          return 'Link invalid or expired'
  }
})

const iconClass = computed(() => {
  if (status.value === 'working') return 'loading'
  if (status.value === 'cancelled') return 'cancelled'
  return status.value
})

onMounted(async () => {
  // The buttons in the email open this page with ?action=confirm or ?action=cancel
  const action = route.query.action

  if (action === 'confirm') return confirm()
  if (action === 'cancel')  return decline()

  // No action in the link: show the two buttons here instead.
  // This lookup is read-only — it changes nothing.
  try {
    const res = await api.get(`/foundation/profile/email/confirm/${token}`)
    newEmail.value = res.data.new_email
    showChoice.value = true
    status.value = 'ready'
  } catch (err) {
    status.value = 'error'
    message.value = err.response?.data?.message || 'This verification link is invalid or has expired.'
  }
})

// "Yes, confirm" — the backend swaps pending_email into email.
async function confirm() {
  status.value = 'working'
  try {
    const res = await api.post(`/foundation/profile/email/confirm/${token}`)
    message.value = res.data.message
    showChoice.value = false
    status.value = 'success'
  } catch (err) {
    message.value = err.response?.data?.message || 'Could not confirm the email change. Please try again.'
    showChoice.value = false
    status.value = 'error'
  }
}

// "No, cancel" — the backend clears the pending change.
async function decline() {
  status.value = 'working'
  try {
    const res = await api.post(`/foundation/profile/email/decline/${token}`)
    message.value = res.data.message
    showChoice.value = false
    status.value = 'cancelled'
  } catch (err) {
    message.value = err.response?.data?.message || 'Could not cancel the email change. Please try again.'
    showChoice.value = false
    status.value = 'error'
  }
}
</script>

<style scoped>
.confirm-page {
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  background: #f0f4f9; padding: 24px;
}
.confirm-card {
  background: white; border-radius: 20px; padding: 40px 32px;
  max-width: 400px; width: 100%; text-align: center;
  box-shadow: 0 4px 20px rgba(15,45,82,0.08);
}

.confirm-icon {
  width: 60px; height: 60px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 18px;
}
.confirm-icon.loading   { background: #eff6ff; color: #1a56c4; }
.confirm-icon.ready     { background: #eff6ff; color: #1a56c4; }
.confirm-icon.success   { background: #f0fdf4; color: #059669; }
.confirm-icon.cancelled { background: #f1f5f9; color: #64748b; }
.confirm-icon.error     { background: #fef2f2; color: #dc2626; }
.confirm-icon .spin { animation: spin 0.9s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.confirm-card h2 { font-size: 1.1rem; font-weight: 800; color: #133c8a; margin: 0 0 8px; }
.confirm-card p  { font-size: 0.87rem; color: #64748b; line-height: 1.5; margin: 0 0 22px; }
.new-email { color: #133c8a; word-break: break-all; }

.btn-row { display: flex; gap: 10px; }
.btn-yes, .btn-no {
  flex: 1; padding: 11px 14px; border-radius: 10px;
  font-size: 0.85rem; font-weight: 700; cursor: pointer;
}
.btn-yes { border: none; background: #1a56c4; color: white; }
.btn-yes:hover:not(:disabled) { background: #133c8a; }
.btn-no { border: 1px solid #e2e8f0; background: white; color: #64748b; }
.btn-no:hover:not(:disabled) { background: #f8fafc; }
.btn-yes:disabled, .btn-no:disabled { opacity: 0.65; cursor: not-allowed; }

.btn-back {
  display: inline-block; padding: 10px 22px; border-radius: 10px;
  background: #1a56c4; color: white; text-decoration: none;
  font-size: 0.85rem; font-weight: 700;
}
.btn-back:hover { background: #133c8a; }
</style>