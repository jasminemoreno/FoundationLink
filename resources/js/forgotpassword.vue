<template>
  <div class="page-wrapper">
    <div class="card">
      <div class="right-panel single">

        <!-- STEP 1: EMAIL -->
        <template v-if="step === 'email'">
          <img src="@/assets/icons/mail.png" class="right-logo" alt="" />
          <h1 class="welcome-title">Forgot Password?</h1>
          <p class="welcome-sub">Enter your email and we'll send you a code</p>

          <div class="input-group" :class="{ focused: focused }">
            <div class="input-icon"><img src="@/assets/icons/mail.png" alt="" /></div>
            <input
              v-model="email"
              type="email"
              placeholder="Email address"
              autocomplete="email"
              @focus="focused = true"
              @blur="focused = false"
              @keyup.enter="sendOtp"
            />
          </div>

          <p v-if="error" class="error-text">{{ error }}</p>

          <button class="btn-login" @click="sendOtp" :disabled="isLoading">
            <span v-if="!isLoading">Send Code</span>
            <span v-else class="spinner" />
          </button>
        </template>

        <!-- STEP 2: OTP -->
        <template v-else-if="step === 'otp'">
          <img src="@/assets/icons/mail.png" class="right-logo" alt="" />
          <h1 class="welcome-title">Enter Verification Code</h1>
          <p class="welcome-sub">We sent a 6-digit code to {{ email }}</p>

          <div class="input-group" :class="{ focused: focused }">
            <div class="input-icon"><img src="@/assets/icons/padlock.png" alt="" /></div>
            <input
              v-model="otp"
              type="text"
              inputmode="numeric"
              maxlength="6"
              placeholder="6-digit code"
              @focus="focused = true"
              @blur="focused = false"
              @keyup.enter="verifyOtp"
            />
          </div>

          <p v-if="error" class="error-text">{{ error }}</p>

          <button class="btn-login" @click="verifyOtp" :disabled="isLoading">
            <span v-if="!isLoading">Verify Code</span>
            <span v-else class="spinner" />
          </button>

          <p class="register-row">
            Didn't get a code?
            <a href="#" class="register-link" @click.prevent="sendOtp">Resend</a>
          </p>
        </template>

        <!-- STEP 3: NEW PASSWORD -->
        <template v-else-if="step === 'password'">
          <img src="@/assets/icons/padlock.png" class="right-logo" alt="" />
          <h1 class="welcome-title">Reset Password</h1>
          <p class="welcome-sub">Enter your new password below</p>

          <div class="input-group" :class="{ focused: focusedField === 'password' }">
            <div class="input-icon"><img src="@/assets/icons/padlock.png" alt="" /></div>
            <input
              v-model="password"
              type="password"
              placeholder="New password"
              autocomplete="new-password"
              @focus="focusedField = 'password'"
              @blur="focusedField = null"
            />
          </div>

          <div class="input-group" :class="{ focused: focusedField === 'confirm' }">
            <div class="input-icon"><img src="@/assets/icons/padlock.png" alt="" /></div>
            <input
              v-model="passwordConfirmation"
              type="password"
              placeholder="Confirm new password"
              autocomplete="new-password"
              @focus="focusedField = 'confirm'"
              @blur="focusedField = null"
              @keyup.enter="resetPassword"
            />
          </div>

          <p v-if="error" class="error-text">{{ error }}</p>

          <button class="btn-login" @click="resetPassword" :disabled="isLoading">
            <span v-if="!isLoading">Reset Password</span>
            <span v-else class="spinner" />
          </button>
        </template>

        <!-- STEP 4: SUCCESS -->
        <template v-else-if="step === 'success'">
          <img src="@/assets/icons/padlock.png" class="right-logo" alt="" />
          <h1 class="welcome-title">All Set!</h1>
          <p class="success-text">Your password has been reset. You can now log in with your new password.</p>
          <button class="btn-login" @click="router.push('/login')">Go to Login</button>
        </template>

        <p v-if="step !== 'success'" class="register-row">
          <router-link to="/login" class="register-link">Back to Login</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

const step = ref('email') // email -> otp -> password -> success
const email = ref('')
const otp = ref('')
const resetToken = ref('')
const password = ref('')
const passwordConfirmation = ref('')

const focused = ref(false)
const focusedField = ref(null)
const isLoading = ref(false)
const error = ref('')

async function sendOtp() {
  if (!email.value) {
    error.value = 'Please enter your email address.'
    return
  }
  isLoading.value = true
  error.value = ''
  try {
    await api.post('/forgot-password', { email: email.value })
    step.value = 'otp'
  } catch (err) {
    error.value = err.response?.data?.message || 'Something went wrong. Please try again.'
  } finally {
    isLoading.value = false
  }
}

async function verifyOtp() {
  if (!otp.value || otp.value.length !== 6) {
    error.value = 'Please enter the 6-digit code.'
    return
  }
  isLoading.value = true
  error.value = ''
  try {
    const { data } = await api.post('/verify-otp', { email: email.value, otp: otp.value })
    resetToken.value = data.reset_token
    step.value = 'password'
  } catch (err) {
    error.value = err.response?.data?.message || 'Invalid or expired code.'
  } finally {
    isLoading.value = false
  }
}

async function resetPassword() {
  if (!password.value || !passwordConfirmation.value) {
    error.value = 'Please fill in both fields.'
    return
  }
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Passwords do not match.'
    return
  }
  if (password.value.length < 8) {
    error.value = 'Password must be at least 8 characters.'
    return
  }
  isLoading.value = true
  error.value = ''
  try {
    await api.post('/reset-password', {
      email: email.value,
      reset_token: resetToken.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    })
    step.value = 'success'
  } catch (err) {
    error.value = err.response?.data?.message || 'Something went wrong. Please try again.'
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap');
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
.page-wrapper { font-family: 'Nunito', sans-serif; background-color: #c0e8db; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 32px 16px; }
.card { display: flex; width: min(480px, 100%); min-height: 480px; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0, 80, 40, 0.15), 0 4px 16px rgba(0,0,0,0.06); }
.right-panel.single { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 56px 48px; background: #fff; text-align: center; }
.right-logo { width: 64px; height: 64px; object-fit: contain; margin-bottom: 16px; }
.welcome-title { font-size: 26px; font-weight: 900; color: #111; letter-spacing: -0.3px; margin-bottom: 6px; }
.welcome-sub { font-size: 14px; font-weight: 500; color: #9aaea6; margin-bottom: 28px; text-align: center; }
.input-group { width: 100%; display: flex; align-items: center; border: 1.5px solid #ddeee6; border-radius: 10px; margin-bottom: 14px; overflow: hidden; transition: border-color 0.2s, box-shadow 0.2s; background: #fff; }
.input-group.focused { border-color: #2db870; box-shadow: 0 0 0 3px rgba(45, 184, 112, 0.12); }
.input-icon { width: 50px; height: 50px; background: #f0faf5; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border-right: 1.5px solid #ddeee6; }
.input-group.focused .input-icon { background: #e4f7ed; border-right-color: #2db870; }
.input-icon img { width: 20px; height: 20px; object-fit: contain; }
.input-group input { flex: 1; height: 50px; border: none; outline: none; padding: 0 16px; font-size: 14.5px; font-family: 'Nunito', sans-serif; font-weight: 600; color: #1a1a1a; background: transparent; text-align: center; }
.input-group input::placeholder { color: #c0d4ca; font-weight: 500; }
.error-text { width: 100%; font-size: 13px; font-weight: 600; color: #cc3333; background: #fff5f5; border: 1px solid #ffd0d0; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; }
.success-text { font-size: 14px; font-weight: 500; color: #1a8a52; background: #f0faf5; border: 1px solid #ddeee6; border-radius: 10px; padding: 16px; margin-bottom: 22px; line-height: 1.6; }
.btn-login { width: 100%; height: 52px; background: #1a8a52; color: #fff; border: none; border-radius: 10px; font-size: 15.5px; font-weight: 800; font-family: 'Nunito', sans-serif; letter-spacing: 0.3px; cursor: pointer; display: flex; align-items: center; justify-content: center; margin-bottom: 22px; transition: background 0.2s, transform 0.15s, box-shadow 0.2s; box-shadow: 0 4px 14px rgba(26, 138, 82, 0.3); }
.btn-login:hover:not(:disabled) { background: #157042; box-shadow: 0 6px 20px rgba(26, 138, 82, 0.4); transform: translateY(-1px); }
.btn-login:disabled { opacity: 0.65; cursor: not-allowed; }
.spinner { width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.35); border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.register-row { font-size: 13px; font-weight: 600; color: #9aaea6; }
.register-link { color: #1a8a52; font-weight: 800; text-decoration: none; background: none; border: none; cursor: pointer; }
.register-link:hover { text-decoration: underline; }
</style>