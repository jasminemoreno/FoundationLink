<template>
  <div class="page-wrapper">
    <div class="card">

      <!-- LEFT -->
      <div class="left-panel">
        <img class="left-bg" src="@/assets/img/bg.jpg" alt="" />
        <div class="left-overlay" />
        <div class="left-content">
          <div class="brand">
            <img src="@/assets/img/logo.png" class="brand-logo" alt="FoundationLink" />
            <span class="brand-name">Foundation<span class="brand-accent">Link</span></span>
          </div>
          <div class="tagline">
            <span class="tagline-line">Share More,</span>
            <span class="tagline-line">
              Help More
              <img src="@/assets/icons/hearth.png" class="tagline-heart" alt="" />
            </span>
          </div>
          <p class="left-desc">
            Together, we bring hope through transparent<br />and meaningful giving
          </p>
        </div>
      </div>

      <!-- RIGHT -->
      <div class="right-panel">
        <h1 class="form-title">Create an Account</h1>
        <p class="form-sub">Sign Up to get started</p>

        <!-- STEP INDICATOR -->
        <div class="steps">
          <div class="step-track">
            <div class="step-circle" :class="{ active: step >= 1, done: step > 1 }">{{ step > 1 ? '✓' : '1' }}</div>
            <div class="step-connector" :class="{ active: step > 1 }"></div>
            <div class="step-circle" :class="{ active: step >= 2, done: step > 2 }">{{ step > 2 ? '✓' : '2' }}</div>
            <div class="step-connector" :class="{ active: step > 2 }"></div>
            <div class="step-circle" :class="{ active: step >= 3 }">3</div>
          </div>
          <div class="step-labels">
            <span :class="{ active: step >= 1 }">Personal</span>
            <span></span>
            <span :class="{ active: step >= 2 }">Account</span>
            <span></span>
            <span :class="{ active: step >= 3 }">Confirm</span>
          </div>
        </div>

        <!-- STEP 1: PERSONAL INFO -->
        <div v-if="step === 1" class="step-form">
          <div class="input-row">
            <div class="input-group" :class="{ focused: focused === 'first_name' }">
              <div class="input-icon">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
              </div>
              <input
                v-model="form.first_name"
                type="text"
                placeholder="First Name"
                @focus="focused = 'first_name'"
                @blur="focused = null"
              />
            </div>
            <div class="input-group" :class="{ focused: focused === 'last_name' }">
              <div class="input-icon">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
              </div>
              <input
                v-model="form.last_name"
                type="text"
                placeholder="Last Name"
                @focus="focused = 'last_name'"
                @blur="focused = null"
              />
            </div>
          </div>

          <div class="input-group" :class="{ focused: focused === 'phone' }">
            <div class="input-icon">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.6 19.79 19.79 0 0 1 1.61 5 2 2 0 0 1 3.6 3h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10.6a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.5 18v1.42"/>
              </svg>
            </div>
            <input
              v-model="form.phone"
              type="tel"
              placeholder="Phone Number (09XXXXXXXXX)"
              @focus="focused = 'phone'"
              @blur="focused = null"
            />
          </div>

          <div class="input-row">
            <div class="input-group select-group" :class="{ focused: focused === 'gender' }">
              <div class="input-icon">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10"/>
                  <path d="M12 8v4l3 3"/>
                </svg>
              </div>
              <select
                v-model="form.gender"
                @focus="focused = 'gender'"
                @blur="focused = null"
              >
                <option value="" disabled>Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="prefer_not_to_say">Prefer not to say</option>
              </select>
            </div>

            <div class="input-group" :class="{ focused: focused === 'birthdate' }">
              <div class="input-icon">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <rect x="3" y="4" width="18" height="18" rx="2"/>
                  <line x1="16" y1="2" x2="16" y2="6"/>
                  <line x1="8" y1="2" x2="8" y2="6"/>
                  <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
              </div>
              <input
                v-model="form.birthdate"
                type="date"
                placeholder="Birthdate"
                @focus="focused = 'birthdate'"
                @blur="focused = null"
              />
            </div>
          </div>

          <div class="input-group" :class="{ focused: focused === 'address' }">
            <div class="input-icon">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
              </svg>
            </div>
            <input
              v-model="form.address"
              type="text"
              placeholder="Address"
              @focus="focused = 'address'"
              @blur="focused = null"
            />
          </div>

          <p v-if="stepError" class="error-text">{{ stepError }}</p>

          <button class="btn-next" @click="nextStep">
            <span>Next</span>
            <svg class="btn-next-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
          </button>
        </div>

        <!-- STEP 2: ACCOUNT -->
        <div v-if="step === 2" class="step-form">
          <div class="input-group" :class="{ focused: focused === 'email' }">
            <div class="input-icon">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
              </svg>
            </div>
            <input
              v-model="form.email"
              type="email"
              placeholder="Email address"
              @focus="focused = 'email'"
              @blur="focused = null"
            />
          </div>

          <div class="input-group" :class="{ focused: focused === 'password' }">
            <div class="input-icon">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </div>
            <input
              v-model="form.password"
              :type="showPass ? 'text' : 'password'"
              placeholder="Password (min 12 characters)"
              @focus="focused = 'password'; passwordFocused = true"
              @blur="focused = null"
            />
            <button class="eye-btn" type="button" @click="showPass = !showPass">
              <svg v-if="!showPass" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              <svg v-else width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>

          <!-- PASSWORD CHECKLIST -->
          <ul v-if="passwordFocused || form.password" class="password-checklist">
            <li :class="{ met: passwordChecks.length }">
              <span class="check-icon">{{ passwordChecks.length ? '✓' : '○' }}</span>
              At least 12 characters
            </li>
            <li :class="{ met: passwordChecks.number }">
              <span class="check-icon">{{ passwordChecks.number ? '✓' : '○' }}</span>
              At least 1 number
            </li>
            <li :class="{ met: passwordChecks.special }">
              <span class="check-icon">{{ passwordChecks.special ? '✓' : '○' }}</span>
              At least 1 special character (e.g. ! @ # $ %)
            </li>
          </ul>

          <div class="input-group" :class="{ focused: focused === 'password_confirmation' }">
            <div class="input-icon">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </div>
            <input
              v-model="form.password_confirmation"
              :type="showConfirm ? 'text' : 'password'"
              placeholder="Confirm password"
              @focus="focused = 'password_confirmation'"
              @blur="focused = null"
            />
            <button class="eye-btn" type="button" @click="showConfirm = !showConfirm">
              <svg v-if="!showConfirm" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              <svg v-else width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>

          <p v-if="stepError" class="error-text">{{ stepError }}</p>

          <div class="btn-row">
            <button class="btn-back" @click="step--">← Back</button>
            <button class="btn-next" @click="nextStep">
              <span>Next</span>
              <svg class="btn-next-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M5 12h14M13 6l6 6-6 6"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- STEP 3: CONFIRM -->
        <div v-if="step === 3" class="step-form">
          <div class="confirm-card">
            <div class="confirm-avatar">
              {{ form.first_name[0] }}{{ form.last_name[0] }}
            </div>
            <div class="confirm-name">{{ form.first_name }} {{ form.last_name }}</div>
            <div class="confirm-email">{{ form.email }}</div>
          </div>

          <div class="confirm-grid">
            <div class="cg-item">
              <span class="cg-label">Phone</span>
              <span>{{ form.phone || '—' }}</span>
            </div>
            <div class="cg-item">
              <span class="cg-label">Gender</span>
              <span>{{ genderLabel }}</span>
            </div>
            <div class="cg-item">
              <span class="cg-label">Birthdate</span>
              <span>{{ form.birthdate || '—' }}</span>
            </div>
            <div class="cg-item">
              <span class="cg-label">Address</span>
              <span>{{ form.address || '—' }}</span>
            </div>
          </div>

          <label class="terms-row">
            <input type="checkbox" v-model="agreedToTerms" />
            <span>
              I agree to the
              <button type="button" class="terms-link" @click.prevent="showTerms = true">Terms of Service</button>
              and
              <button type="button" class="terms-link" @click.prevent="showPrivacy = true">Privacy Policy</button>
            </span>
          </label>

          <p v-if="stepError" class="error-text">{{ stepError }}</p>

          <div class="btn-row">
            <button class="btn-back" @click="step--">← Back</button>
            <button class="btn-signup" @click="register" :disabled="isLoading || !agreedToTerms">
              <span v-if="!isLoading">Sign Up</span>
              <span v-else class="spinner" />
            </button>
          </div>
        </div>

        <p class="login-row">
          Already have an account?
          <router-link to="/login" class="login-link">Log in</router-link>
        </p>

      </div>
    </div>

    <LegalModal v-model="showTerms" title="Terms of Service" :sections="termsSections" />
    <LegalModal v-model="showPrivacy" title="Privacy Policy" :sections="privacySections" />
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import LegalModal from '../components/donor/legalmodal.vue'

const router    = useRouter()
const step      = ref(1)
const focused   = ref(null)
const isLoading = ref(false)
const stepError = ref('')
const showPass    = ref(false)
const showConfirm = ref(false)
const agreedToTerms = ref(false)
const passwordFocused = ref(false)

/* ── LEGAL MODALS ── */
const showTerms   = ref(false)
const showPrivacy = ref(false)

const termsSections = [
  {
    heading: '1. Acceptance of Terms',
    body: 'By creating an account or using FoundationLink, you agree to these Terms of Service. If you do not agree, please do not use the platform.'
  },
  {
    heading: '2. What FoundationLink Does',
    body: 'FoundationLink is a platform that connects donors with verified foundations and their campaigns. We facilitate the recording and tracking of monetary and item donations. FoundationLink does not directly process or hold donated funds — payment arrangements for monetary donations are made directly between donors and foundations through the payment details each foundation provides.'
  },
  {
    heading: '3. Account Responsibilities',
    body: "You are responsible for keeping your account credentials secure and for the accuracy of the information you provide. Donors must be truthful about donation details; foundation admins must be truthful about their organization's identity and the campaigns they create."
  },
  {
    heading: '4. Foundation Verification',
    body: 'Foundations undergo a verification process before they can create campaigns or receive donations. Verification confirms the information and documents submitted were reviewed by our administrators, but FoundationLink cannot guarantee the ongoing conduct of any foundation after verification.'
  },
  {
    heading: '5. Donations',
    body: 'All donations made through the platform are voluntary and, once submitted, are generally non-refundable. Cancellation or refund of a donation is at the discretion of the receiving foundation. FoundationLink is not responsible for how a foundation ultimately uses donated funds or items beyond what is disclosed on the campaign.'
  },
  {
    heading: '6. Prohibited Conduct',
    body: 'Users may not use FoundationLink for fraudulent campaigns, misrepresentation of organizational identity, harassment, or any activity that violates applicable laws. FoundationLink reserves the right to suspend or terminate accounts that violate these terms.'
  },
  {
    heading: '7. Changes to These Terms',
    body: 'These terms may be updated from time to time. Continued use of the platform after changes are posted constitutes acceptance of the revised terms.'
  },
  {
    heading: '8. Contact',
    body: 'Questions about these Terms can be sent to',
    link: { text: 'support@foundationlink.com', href: 'mailto:support@foundationlink.com' }
  },
]

const privacySections = [
  {
    heading: '1. Information We Collect',
    body: "When you register or use FoundationLink, we collect information you provide directly, such as your name, email, phone number, address, gender, and birthdate (for donors), or your foundation's name, description, address, and verification documents (for foundation admins). We also record donation details you submit, such as amount, item descriptions, and delivery preferences."
  },
  {
    heading: '2. How We Use Your Information',
    body: 'Your information is used to operate your account, process and display donations, verify foundation legitimacy, send relevant notifications (such as campaign updates from foundations you follow), and improve the platform.'
  },
  {
    heading: '3. Sharing of Information',
    body: 'Donor information relevant to a specific donation (such as name and contact details) is shared with the foundation receiving that donation, so they can confirm receipt and arrange delivery or payment. We do not sell personal information to third parties.'
  },
  {
    heading: '4. Foundation Verification Documents',
    body: 'Identity and legitimacy documents submitted during foundation verification are used solely for review by our administrators and are not made public or shared outside the verification process.'
  },
  {
    heading: '5. Data Storage and Security',
    body: 'We take reasonable measures to protect your information from unauthorized access. Passwords are stored securely and are never visible to administrators or other users.'
  },
  {
    heading: '6. Your Choices',
    body: 'You can update most of your personal information from your Profile page. Email addresses cannot be changed once registered, as they serve as your account identifier.'
  },
  {
    heading: '7. Changes to This Policy',
    body: 'This policy may be updated from time to time. Continued use of the platform after changes are posted constitutes acceptance of the revised policy.'
  },
  {
    heading: '8. Contact',
    body: 'Questions about this Privacy Policy can be sent to',
    link: { text: 'support@foundationlink.com', href: 'mailto:support@foundationlink.com' }
  },
]

const form = reactive({
  first_name:            '',
  last_name:             '',
  phone:                 '',
  gender:                '',
  birthdate:             '',
  address:               '',
  email:                 '',
  password:              '',
  password_confirmation: '',
})

/* ── PHONE ── */
function normalizePhone(input) {
  if (!input) return null

  const cleaned = input.replace(/[\s\-()]/g, '')

  if (/^09\d{9}$/.test(cleaned)) {
    return '+63' + cleaned.slice(1)
  }

  if (/^\+639\d{9}$/.test(cleaned)) {
    return cleaned
  }

  return null
}

/* ── PASSWORD CHECKLIST ── */
const passwordChecks = computed(() => ({
  length: form.password.length >= 12,
  number: /\d/.test(form.password),
  special: /[^a-zA-Z0-9]/.test(form.password),
}))

const isPasswordValid = computed(() =>
  passwordChecks.value.length && passwordChecks.value.number && passwordChecks.value.special
)

const genderLabel = computed(() => {
  return { male: 'Male', female: 'Female', prefer_not_to_say: 'Prefer not to say' }[form.gender] || '—'
})

/* ── NEXT STEP ── */
function nextStep() {
  stepError.value = ''

  if (step.value === 1) {
    if (!form.first_name.trim()) return stepError.value = 'First name is required.'
    if (!form.last_name.trim())  return stepError.value = 'Last name is required.'
    if (!form.phone.trim())      return stepError.value = 'Phone number is required.'
    if (!normalizePhone(form.phone)) return stepError.value = 'Enter a valid PH phone number (09XXXXXXXXX or +639XXXXXXXXX).'
    step.value++
    return
  }

  if (step.value === 2) {
    if (!form.email.trim())    return stepError.value = 'Email is required.'
    if (!form.password)        return stepError.value = 'Password is required.'
    if (!isPasswordValid.value) return stepError.value = 'Password must be at least 12 characters and include a number and a special character.'
    if (form.password !== form.password_confirmation) return stepError.value = 'Passwords do not match.'
    step.value++
    return
  }
}

/* ── REGISTER ── */
async function register() {
  stepError.value = ''
  if (!agreedToTerms.value) {
    stepError.value = 'Please agree to the Terms of Service and Privacy Policy.'
    return
  }

  isLoading.value = true
  try {
    await api.post('/register/donor', {
      first_name:            form.first_name,
      last_name:             form.last_name,
      email:                 form.email,
      password:              form.password,
      password_confirmation: form.password_confirmation,
      phone:                 normalizePhone(form.phone),
      address:               form.address   || null,
      gender:                form.gender    || null,
      birthdate:             form.birthdate || null,
    })

    router.push('/login')
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) {
      stepError.value = Object.values(errors).flat().join(' · ')
    } else {
      stepError.value = err.response?.data?.message || 'Registration failed.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap');

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.page-wrapper {
  font-family: 'Nunito', sans-serif;
  background-color: #c0e8db;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 32px 16px;
}

.card {
  display: flex;
  width: min(1100px, 100%);
  min-height: 640px;
  background: #fff;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 24px 64px rgba(0, 80, 40, 0.16), 0 4px 16px rgba(0, 0, 0, 0.06);
}

/* LEFT */
.left-panel { position: relative; width: 380px; flex-shrink: 0; background: #0e5c36; overflow: hidden; }
.left-bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.35; }
.left-overlay { position: absolute; inset: 0; background: linear-gradient(170deg, rgba(14, 92, 54, 0.82) 0%, rgba(5, 48, 28, 0.92) 100%); }
.left-content { position: relative; z-index: 2; height: 100%; padding: 48px 42px; display: flex; flex-direction: column; }
.brand { display: flex; flex-direction: column; align-items: center; gap: 6px; }
.brand-logo { width: 90px; height: 90px; object-fit: contain; filter: brightness(1.4); }
.brand-name { font-size: 26px; font-weight: 900; color: #fff; letter-spacing: -0.3px; }
.brand-accent { color: #7eedc7; font-size: 32px; }
.tagline { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; }
.tagline-line { font-size: 30px; font-weight: 900; color: #fff; line-height: 1.25; display: flex; align-items: center; gap: 8px; }
.tagline-heart { width: 28px; height: 28px; object-fit: contain; }
.left-desc { font-size: 14px; font-weight: 500; color: rgba(255, 255, 255, 0.75); line-height: 1.75; text-align: center; }

/* RIGHT — top-aligned so content doesn't jump between steps */
.right-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  padding: 44px 56px 40px;
  background: #fff;
  overflow-y: auto;
}
.form-title { font-size: 1.6rem; font-weight: 900; color: #111; letter-spacing: -0.3px; margin-bottom: 4px; text-align: center; }
.form-sub   { font-size: 0.85rem; color: #9aaea6; margin-bottom: 28px; text-align: center; }

/* STEP INDICATOR — track and labels share one grid so they always line up,
   regardless of label text length */
.steps { width: 100%; max-width: 320px; margin-bottom: 32px; }
.step-track,
.step-labels {
  display: grid;
  grid-template-columns: 32px 1fr 32px 1fr 32px;
  align-items: center;
}
.step-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #94a3b8;
  font-size: 0.8rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.25s, color 0.25s;
}
.step-circle.active { background: #1a8a52; color: #fff; }
.step-circle.done   { background: #059669; color: #fff; }
.step-connector { height: 2px; background: #e2e8f0; margin: 0 6px; transition: background 0.25s; }
.step-connector.active { background: #1a8a52; }
.step-labels { margin-top: 8px; }
.step-labels span {
  font-size: 0.7rem;
  font-weight: 700;
  color: #94a3b8;
  text-align: center;
  transition: color 0.25s;
}
.step-labels span.active { color: #1a8a52; }

/* FORM */
.step-form { width: 100%; }
.input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

.input-group {
  display: flex;
  align-items: center;
  border: 1.5px solid #ddeee6;
  border-radius: 10px;
  margin-bottom: 14px;
  overflow: hidden;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: #fff;
}
.input-group.focused {
  border-color: #2db870;
  box-shadow: 0 0 0 3px rgba(45, 184, 112, 0.12);
}

.input-icon {
  width: 46px;
  height: 48px;
  background: #f0faf5;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border-right: 1.5px solid #ddeee6;
  color: #94a3b8;
  transition: background 0.2s, border-color 0.2s, color 0.2s;
}
.input-group.focused .input-icon {
  background: #e4f7ed;
  border-right-color: #2db870;
  color: #2db870;
}

.input-group input,
.input-group select {
  flex: 1;
  height: 48px;
  border: none;
  outline: none;
  padding: 0 14px;
  font-size: 14px;
  font-family: 'Nunito', sans-serif;
  font-weight: 600;
  color: #1a1a1a;
  background: transparent;
}

.input-group select { cursor: pointer; }
.input-group input::placeholder { color: #c0d4ca; font-weight: 500; }

.eye-btn {
  width: 44px;
  height: 48px;
  border: none;
  background: transparent;
  cursor: pointer;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: color 0.2s;
}
.eye-btn:hover { color: #1a8a52; }

/* PASSWORD CHECKLIST */
.password-checklist {
  list-style: none;
  padding: 0;
  margin: -6px 0 14px 2px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.password-checklist li {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  color: #94a3b8;
  transition: color 0.15s;
}

.password-checklist li.met {
  color: #1a8a52;
}

.check-icon {
  font-size: 0.85rem;
  width: 14px;
  display: inline-block;
  text-align: center;
}

/* CONFIRM */
.confirm-card { background: #f0faf5; border-radius: 14px; padding: 22px; text-align: center; margin-bottom: 16px; }
.confirm-avatar { width: 56px; height: 56px; border-radius: 16px; background: #1a8a52; color: white; font-size: 1.2rem; font-weight: 800; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; text-transform: uppercase; }
.confirm-name  { font-size: 1rem; font-weight: 800; color: #0F2D52; }
.confirm-email { font-size: 0.8rem; color: #94a3b8; margin-top: 2px; }

.confirm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
.cg-item  { display: flex; flex-direction: column; gap: 3px; }
.cg-label { font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }
.cg-item span:last-child { font-size: 0.85rem; color: #0F2D52; font-weight: 600; }

.terms-row { display: flex; align-items: flex-start; gap: 10px; font-size: 0.83rem; color: #64748b; margin-bottom: 18px; cursor: pointer; line-height: 1.5; }
.terms-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: #1a8a52; margin-top: 2px; flex-shrink: 0; }
.terms-link { color: #1a8a52; font-weight: 700; text-decoration: none; background: none; border: none; padding: 0; font-family: inherit; font-size: inherit; cursor: pointer; }
.terms-link:hover { text-decoration: underline; }

.error-text { font-size: 0.82rem; font-weight: 600; color: #cc3333; background: #fff5f5; border: 1px solid #ffd0d0; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; }

.btn-row { display: flex; gap: 12px; }
.btn-back { flex: 1; height: 50px; background: white; color: #64748b; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 15px; font-weight: 700; font-family: 'Nunito', sans-serif; cursor: pointer; transition: all 0.2s; }
.btn-back:hover { background: #f1f5f9; border-color: #cbd5e1; }

.btn-signup {
  flex: 2;
  height: 50px;
  background: #1a8a52;
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 800;
  font-family: 'Nunito', sans-serif;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
  box-shadow: 0 4px 14px rgba(26, 138, 82, 0.3);
}
.btn-signup:hover:not(:disabled) {
  background: #157042;
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(26, 138, 82, 0.38);
}
.btn-signup:disabled { opacity: 0.65; cursor: not-allowed; transform: none; box-shadow: none; }

/* NEXT BUTTON — gradient, lift, sliding arrow */
.btn-next {
  flex: 2;
  width: 100%;
  height: 52px;
  background: linear-gradient(135deg, #22a366 0%, #157042 100%);
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 800;
  font-family: 'Nunito', sans-serif;
  letter-spacing: 0.3px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.3s ease;
  box-shadow: 0 6px 20px rgba(26, 138, 82, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.15);
}
.btn-next:hover {
  background: linear-gradient(135deg, #29ab6c 0%, #0f5c36 100%);
  transform: translateY(-2px);
  box-shadow: 0 10px 26px rgba(26, 138, 82, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.2);
}
.btn-next:active {
  transform: translateY(0);
  box-shadow: 0 4px 14px rgba(26, 138, 82, 0.3);
}
.btn-next-arrow {
  transition: transform 0.25s ease;
}
.btn-next:hover .btn-next-arrow {
  transform: translateX(4px);
}

.spinner { width: 20px; height: 20px; border: 2px solid rgba(255, 255, 255, 0.35); border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.login-row  { font-size: 13px; font-weight: 600; color: #9aaea6; margin-top: 20px; text-align: center; }
.login-link { color: #1a8a52; font-weight: 800; text-decoration: none; }
.login-link:hover { text-decoration: underline; }

@media (max-width: 780px) {
  .card { flex-direction: column; }
  .left-panel { width: 100%; min-height: 180px; }
  .right-panel { padding: 32px 24px 28px; }
  .input-row { grid-template-columns: 1fr; }
  .steps { max-width: 100%; }
}
</style>