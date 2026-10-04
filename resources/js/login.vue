<template>
  <div class="page-wrapper">
    <img class="page-bg" src="@/assets/img/pic.png" alt="" />
    <div class="page-overlay" />
    <div class="mint mint-1" />
    <div class="mint mint-2" />

    <!-- TOP-LEFT: BRAND -->
    <header class="brand">
      <img src="@/assets/img/logo.png" class="brand-logo" alt="FoundationLink" />
      <span class="brand-name">Foundation<span class="brand-accent">Link</span></span>
    </header>

    <!-- LOGIN CARD -->
    <main class="content">
      <div class="login-card">
        <h1 class="welcome-title">Welcome Back</h1>
        <p class="welcome-sub">Login to continue to your account</p>

        <!-- EMAIL -->
        <label class="sr-only" for="login-email">Email address</label>
        <div class="input-group" :class="{ focused: focusedField === 'email' }">
          <span class="input-icon"><Mail :size="18" stroke-width="1.9" /></span>
          <input
            id="login-email"
            v-model="form.email"
            type="email"
            placeholder="Email address"
            autocomplete="email"
            @focus="focusedField = 'email'"
            @blur="focusedField = null"
            @keyup.enter="handleLogin"
          />
        </div>

        <!-- PASSWORD -->
        <label class="sr-only" for="login-password">Password</label>
        <div class="input-group" :class="{ focused: focusedField === 'password' }">
          <span class="input-icon"><Lock :size="18" stroke-width="1.9" /></span>
          <input
            id="login-password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Password"
            autocomplete="current-password"
            @focus="focusedField = 'password'"
            @blur="focusedField = null"
            @keyup.enter="handleLogin"
          />
          <button
            type="button"
            class="eye-btn"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            @mousedown.prevent
            @click="showPassword = !showPassword"
          >
            <Eye v-if="showPassword" :size="18" stroke-width="1.9" />
            <EyeOff v-else :size="18" stroke-width="1.9" />
          </button>
        </div>

        <!-- OPTIONS -->
        <div class="row-options">
          <router-link to="/forgot-password" class="forgot">Forgot Password?</router-link>
        </div>

        <!-- ERROR -->
        <p v-if="error" class="error-text">{{ error }}</p>

        <!-- LOGIN -->
        <button class="btn-login" @click="handleLogin" :disabled="isLoading">
          <template v-if="!isLoading">
            <span>Login</span>
            <ArrowRight :size="20" stroke-width="2" class="btn-arrow" />
          </template>
          <span v-else class="spinner" />
        </button>

        <div class="divider"><span>Don't have an account?</span></div>

        <!-- REGISTER -->
        <router-link to="/role" class="btn-register">
          <User :size="18" stroke-width="1.75" />
          <span>Register Here</span>
        </router-link>
      </div>
    </main>

    <!-- BOTTOM-LEFT: TAGLINE -->
    <aside class="hero-text">
      <h2>
        <span class="hero-line">Share More,</span>
        <span class="hero-line">
          Help More
          <Heart class="hero-heart" :size="30" fill="currentColor" stroke-width="0" />
        </span>
      </h2>
      <p>
        Together, we bring hope through transparent<br />and meaningful giving
      </p>
    </aside>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { Mail, Lock, Eye, EyeOff, ArrowRight, User, Heart } from 'lucide-vue-next'

const router = useRouter()

const form = reactive({
  email: '',
  password: ''
})

const focusedField = ref(null)
const showPassword = ref(false)
const isLoading = ref(false)
const error = ref('')

async function handleLogin() {
  if (isLoading.value) return

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

.page-wrapper {
  --green-900: #14493a;
  --green-700: #22694f;
  --green-800: #1a5741;
  --green-400: #4fae87;
  --border: #dde5e1;

  position: relative;
  font-family: 'Inter', sans-serif;
  min-height: 100vh;
  overflow: hidden;
  background: #cfe6da;
}

.sr-only {
  position: absolute;
  width: 1px; height: 1px;
  margin: -1px; padding: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* BACKGROUND */
.page-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* light glow behind the brand + soft dark fade behind the tagline */
.page-overlay {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse 55% 38% at 10% 6%, rgba(255, 255, 255, 0.6), transparent 70%),
    linear-gradient(to top, rgba(6, 24, 17, 0.62) 0%, rgba(6, 24, 17, 0.24) 30%, transparent 58%);
}

/* mint curves, bottom-right */
.mint {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}
.mint-1 {
  width: 640px; height: 640px;
  right: -190px; bottom: -320px;
  background: rgba(186, 222, 204, 0.78);
}
.mint-2 {
  width: 420px; height: 420px;
  right: 130px; bottom: -270px;
  background: rgba(150, 205, 180, 0.5);
}

/* ── TOP-LEFT: BRAND ── */
.brand {
  position: absolute;
  top: 44px;
  left: clamp(24px, 5vw, 76px);
  z-index: 3;
  display: flex;
  align-items: center;
  gap: 14px;
}

.brand-logo {
  width: 84px;
  height: 84px;
  object-fit: contain;
}

.brand-name {
  font-size: 40px;
  font-weight: 800;
  color: var(--green-900);
  letter-spacing: -1px;
  line-height: 1.05;
}

.brand-accent { color: var(--green-400); }

/* ── BOTTOM-LEFT: TAGLINE ── */
.hero-text {
  position: absolute;
  left: clamp(24px, 5vw, 76px);
  bottom: 56px;
  z-index: 3;
  max-width: 560px;
  color: #fff;
  text-shadow: 0 2px 16px rgba(0, 0, 0, 0.35);
}

.hero-text h2 {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: clamp(30px, 3.4vw, 46px);
  font-weight: 700;
  line-height: 1.15;
  letter-spacing: -0.8px;
}

.hero-line {
  display: flex;
  align-items: center;
  gap: 12px;
}

.hero-heart {
  color: #8fe0bd;
  flex-shrink: 0;
}

.hero-text p {
  margin-top: 14px;
  font-size: 17px;
  font-weight: 400;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.92);
}

/* ── CARD ── */
.content {
  position: relative;
  z-index: 2;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 48px clamp(24px, 6vw, 100px);
}

.login-card {
  width: 500px;
  background: rgba(255, 255, 255, 0.94);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 24px;
  padding: 50px 46px 42px;
  box-shadow: 0 24px 70px rgba(10, 40, 28, 0.22);
  animation: rise 0.6s ease both;
}

@keyframes rise {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}

.welcome-title {
  font-size: 32px;
  font-weight: 700;
  color: var(--green-900);
  letter-spacing: -0.5px;
  text-align: center;
  margin-bottom: 10px;
}

.welcome-sub {
  font-size: 15px;
  font-weight: 400;
  line-height: 1.6;
  color: #6b7770;
  text-align: center;
  margin-bottom: 30px;
}

/* INPUTS */
.input-group {
  position: relative;
  display: flex;
  align-items: center;
  height: 56px;
  border: 1.5px solid var(--border);
  border-radius: 14px;
  background: #fff;
  margin-bottom: 16px;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.input-group:hover { border-color: #c5d2cb; }

.input-group.focused {
  border-color: var(--green-700);
  box-shadow: 0 0 0 4px rgba(34, 105, 79, 0.13);
}

.input-icon {
  width: 38px;
  height: 38px;
  margin-left: 9px;
  flex-shrink: 0;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--green-700);
  background: #eaf5ef;
  transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
}
.input-group.focused .input-icon {
  background: var(--green-700);
  color: #fff;
  box-shadow: 0 4px 10px rgba(34, 105, 79, 0.3);
}

.input-group input {
  flex: 1;
  min-width: 0;
  height: 100%;
  border: none;
  outline: none;
  background: transparent;
  padding: 0 16px;
  font-family: 'Inter', sans-serif;
  font-size: 15px;
  font-weight: 500;
  color: #16211b;
}
.input-group input::placeholder { color: #98a6a0; font-weight: 400; }

/* show / hide password */
.eye-btn {
  width: 38px;
  height: 38px;
  margin-right: 9px;
  flex-shrink: 0;
  border: none;
  border-radius: 11px;
  background: transparent;
  color: #7b8a83;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease;
}
.eye-btn:hover {
  background: #eaf5ef;
  color: var(--green-700);
}
.input-group.focused .eye-btn { color: var(--green-700); }
.eye-btn:focus-visible {
  outline: 2px solid var(--green-700);
  outline-offset: 2px;
}

/* FORGOT */
.row-options {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  margin: 4px 0 22px;
}

.forgot {
  font-size: 14.5px;
  font-weight: 500;
  color: var(--green-700);
  text-decoration: underline;
  text-underline-offset: 3px;
  transition: color 0.15s ease;
}
.forgot:hover { color: var(--green-900); }
.forgot:focus-visible {
  outline: 2px solid var(--green-700);
  outline-offset: 3px;
  border-radius: 4px;
}

/* ERROR */
.error-text {
  font-size: 13.5px;
  font-weight: 500;
  color: #b3261e;
  background: #fdf3f2;
  border: 1px solid #f3d4d1;
  border-left: 4px solid #d93025;
  border-radius: 10px;
  padding: 10px 14px;
  margin-bottom: 16px;
}

/* BUTTONS */
.btn-login {
  width: 100%;
  height: 58px;
  border: none;
  border-radius: 999px;
  background: var(--green-700);
  color: #fff;
  font-family: 'Inter', sans-serif;
  font-size: 16.5px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  box-shadow: 0 10px 24px rgba(34, 105, 79, 0.28);
  transition: background 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
}
.btn-login:hover:not(:disabled) {
  background: var(--green-800);
  transform: translateY(-1px);
  box-shadow: 0 14px 30px rgba(34, 105, 79, 0.34);
}
.btn-login:hover:not(:disabled) .btn-arrow { transform: translateX(3px); }
.btn-login:active:not(:disabled) { transform: translateY(0); }
.btn-login:focus-visible { outline: 2px solid var(--green-900); outline-offset: 3px; }
.btn-login:disabled { opacity: 0.65; cursor: not-allowed; }
.btn-arrow { transition: transform 0.15s ease; }

.spinner {
  width: 20px; height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.divider {
  display: flex;
  align-items: center;
  gap: 16px;
  margin: 24px 0 18px;
  font-size: 13.5px;
  color: #7b8a83;
}
.divider::before,
.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #e2e9e5;
}

.btn-register {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  height: 56px;
  border: 1.5px solid var(--green-700);
  border-radius: 999px;
  color: var(--green-700);
  font-size: 15.5px;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.15s ease, color 0.15s ease;
}
.btn-register:hover { background: var(--green-700); color: #fff; }
.btn-register:focus-visible { outline: 2px solid var(--green-900); outline-offset: 3px; }

/* RESPONSIVE */
@media (max-width: 980px) {
  .page-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 28px;
    padding: 28px 20px 40px;
  }

  .brand,
  .hero-text {
    position: relative;
    inset: auto;
  }

  .brand { order: 1; }
  .brand-logo { width: 64px; height: 64px; }
  .brand-name { font-size: 30px; }

  .content {
    order: 2;
    width: 100%;
    min-height: 0;
    padding: 0;
    justify-content: center;
  }
  .login-card { width: 100%; max-width: 480px; }

  .hero-text {
    order: 3;
    text-align: center;
    padding: 0 8px;
  }
  .hero-line { justify-content: center; }

  .mint-1 { width: 460px; height: 460px; right: -200px; bottom: -260px; }
  .mint-2 { display: none; }
}

@media (max-width: 480px) {
  .login-card { padding: 36px 22px 30px; border-radius: 20px; }
  .welcome-title { font-size: 28px; }
}

@media (prefers-reduced-motion: reduce) {
  .login-card { animation: none; }
  .btn-login, .btn-arrow { transition: none; }
}
</style>