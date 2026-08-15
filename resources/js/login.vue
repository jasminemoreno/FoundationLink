<template>
  <div class="page-wrapper">
    <img class="page-bg" src="@/assets/img/bg2.jpg" alt="" />
    <div class="page-overlay" />

    <div class="content">

      <!-- LEFT: BRAND -->
      <div class="brand-side">
        <div class="brand">
          <img src="@/assets/img/logo.png" class="brand-logo" alt="FoundationLink" />
          <span class="brand-name">Foundation<span class="brand-accent">Link</span></span>
        </div>

        <div class="tagline">
          <span class="tagline-line">Share More,</span>
          <span class="tagline-line">
            Help More
            <Heart class="tagline-heart" :size="28" fill="currentColor" stroke-width="0" />
          </span>
        </div>

        <p class="brand-desc">
          Together, we bring hope through transparent<br />and meaningful giving
        </p>
      </div>

      <!-- RIGHT: LOGIN CARD -->
      <div class="form-side">
        <div class="login-card">
          <h1 class="welcome-title">Welcome Back</h1>
          <p class="welcome-sub">Login to continue to your account</p>

          <!-- EMAIL -->
          <div class="input-group" :class="{ focused: focusedField === 'email' }">
            <div class="input-icon">
              <Mail :size="17" stroke-width="1.75" />
            </div>
            <input
              v-model="form.email"
              type="email"
              placeholder="Email address"
              autocomplete="email"
              @focus="focusedField = 'email'"
              @blur="focusedField = null"
            />
          </div>

          <!-- PASSWORD -->
          <div class="input-group" :class="{ focused: focusedField === 'password' }">
            <div class="input-icon">
              <Lock :size="17" stroke-width="1.75" />
            </div>
            <input
              v-model="form.password"
              type="password"
              placeholder="Password"
              autocomplete="current-password"
              @focus="focusedField = 'password'"
              @blur="focusedField = null"
            />
          </div>

          <!-- OPTIONS -->
          <div class="row-options">
            <router-link to="/forgot-password" class="forgot">Forgot Password?</router-link>
          </div>

          <!-- ERROR -->
          <p v-if="error" class="error-text">{{ error }}</p>

          <!-- BUTTON -->
          <button class="btn-login" @click="handleLogin" :disabled="isLoading">
            <span v-if="!isLoading">Login</span>
            <span v-else class="spinner" />
          </button>

          <p class="register-row">
            Don't have an account?
            <router-link to="/role" class="register-link">Register Here</router-link>
          </p>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { Mail, Lock, Heart } from 'lucide-vue-next'

const router = useRouter()

const form = reactive({
  email: '',
  password: ''
})

const focusedField = ref(null)
const isLoading = ref(false)
const error = ref('')

async function handleLogin() {
  if (!form.email || !form.password) {
    error.value = 'Please fill in all fields.'
    return
  }

  isLoading.value = true
  error.value = ''

  try {
    const { data } = await api.post('/login', {
      email: form.email,
      password: form.password
    })

    sessionStorage.setItem('token', data.token)
    sessionStorage.setItem('user', JSON.stringify(data.user))

    const role = data.user?.role

    if (role === 'superadmin') {
      router.push('/admin/dashboard')
    }
    else if (role === 'foundation_admin') {
      router.push('/foundation/dashboard')
    }
    else if (role === 'donor') {
      router.push('/donor/dashboard')
    }
    else {
      error.value = 'Invalid user role.'
    }

  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Login failed. Please try again.'
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* PAGE */
.page-wrapper {
  position: relative;
  font-family: 'Inter', sans-serif;
  min-height: 100vh;
  overflow: hidden;
}

.page-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Dark/green transparent cover over the photo for readability */
.page-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, rgba(6, 45, 27, 0.82) 0%, rgba(6, 45, 27, 0.55) 45%, rgba(6, 45, 27, 0.75) 100%);
}

.content {
  position: relative;
  z-index: 2;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 40px;
  padding: 48px 220px 48px 160px;
}

/* ── LEFT: BRAND ── */
.brand-side {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  max-width: 500px;
  color: #fff;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 48px;
}

.brand-logo {
  width: 96px;
  height: 96px;
  object-fit: contain;
}

.brand-name {
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.2px;
}

.brand-accent {
  color: #8fe0bd;
  font-weight: 700;
}

.tagline {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 22px;
}

.tagline-line {
  font-size: clamp(34px, 4.2vw, 52px);
  font-weight: 700;
  line-height: 1.15;
  letter-spacing: -0.6px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.tagline-heart {
  color: #8fe0bd;
  flex-shrink: 0;
}

.brand-desc {
  font-size: 16px;
  font-weight: 400;
  color: rgba(255, 255, 255, 0.78);
  line-height: 1.7;
}

/* ── RIGHT: LOGIN CARD ── */
.form-side {
  flex-shrink: 0;
  display: flex;
  justify-content: center;
}

.login-card {
  width: 420px;
  background: #fff;
  border-radius: 18px;
  padding: 44px 40px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
}

.welcome-title {
  font-size: 26px;
  font-weight: 700;
  color: #111815;
  letter-spacing: -0.3px;
  margin-bottom: 6px;
  text-align: center;
}

.welcome-sub {
  font-size: 14px;
  font-weight: 400;
  color: #6b7d74;
  margin-bottom: 32px;
  text-align: center;
}

/* INPUT */
.input-group {
  width: 100%;
  display: flex;
  align-items: center;
  border: 1.5px solid #dfe8e3;
  border-radius: 10px;
  margin-bottom: 16px;
  overflow: hidden;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  background: #fff;
}

.input-group:hover {
  border-color: #c3d5cb;
}

.input-group.focused {
  border-color: #1a8a52;
  box-shadow: 0 0 0 3px rgba(26, 138, 82, 0.1);
}

.input-icon {
  width: 46px;
  height: 48px;
  background: #f5f9f7;
  color: #5c7568;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border-right: 1.5px solid #dfe8e3;
  transition: border-color 0.15s ease, color 0.15s ease;
}

.input-group.focused .input-icon {
  border-right-color: #1a8a52;
  color: #1a8a52;
}

.input-group input {
  flex: 1;
  height: 48px;
  border: none;
  outline: none;
  padding: 0 15px;
  font-size: 14px;
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  color: #16211b;
  background: transparent;
}

.input-group input::placeholder {
  color: #a7b8af;
  font-weight: 400;
}

/* OPTIONS */
.row-options {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  margin-bottom: 22px;
}

.forgot {
  font-size: 13px;
  font-weight: 600;
  color: #1a8a52;
  text-decoration: none;
  transition: color 0.15s ease;
}
.forgot:hover { color: #0f6b3c; text-decoration: underline; }
.forgot:focus-visible {
  outline: 2px solid #1a8a52;
  outline-offset: 3px;
  border-radius: 4px;
}

/* ERROR */
.error-text {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 500;
  color: #b3261e;
  background: #fdf3f2;
  border: 1px solid #f3d4d1;
  border-radius: 8px;
  padding: 10px 14px;
  margin-bottom: 16px;
}

/* BUTTON */
.btn-login {
  width: 100%;
  height: 50px;
  background: #157042;
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  font-family: 'Inter', sans-serif;
  letter-spacing: 0.1px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
  transition: background 0.15s ease;
}

.btn-login:hover:not(:disabled) {
  background: #0f5c36;
}

.btn-login:active:not(:disabled) {
  background: #0c4a2b;
}

.btn-login:focus-visible {
  outline: 2px solid #0f5c36;
  outline-offset: 3px;
}

.btn-login:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* REGISTER */
.register-row {
  font-size: 13px;
  font-weight: 400;
  color: #6b7d74;
  text-align: center;
}

.register-link {
  color: #157042;
  font-weight: 600;
  text-decoration: none;
}
.register-link:hover { text-decoration: underline; }
.register-link:focus-visible {
  outline: 2px solid #157042;
  outline-offset: 3px;
  border-radius: 4px;
}

/* RESPONSIVE */
@media (max-width: 980px) {
  .content {
    flex-direction: column;
    justify-content: center;
    padding: 40px 24px;
    gap: 48px;
  }
  .brand-side {
    align-items: center;
    text-align: center;
    max-width: 100%;
  }
  .brand { justify-content: center; }
  .tagline-line { justify-content: center; }
}

@media (max-width: 480px) {
  .login-card { width: 100%; padding: 32px 24px; }
  .tagline-line { font-size: 30px; }
}
</style>