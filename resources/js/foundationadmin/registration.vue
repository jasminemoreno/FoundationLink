<template>
  <FoundationAuthLayout :showStepper="true">

    <!-- STEP -->
    <template #stepper>
      <FoundationStepper :currentStep="1" />
    </template>

    <!-- FEATURES -->
    <template #features>
      <div class="feature-item">
        <div class="feature-icon">🛡️</div>
        <div class="feature-title">Secure & Trusted</div>
        <div class="feature-desc">Your data is protected</div>
      </div>

      <div class="feature-item">
        <div class="feature-icon">📈</div>
        <div class="feature-title">Track Progress</div>
        <div class="feature-desc">Monitor foundation growth</div>
      </div>

      <div class="feature-item">
        <div class="feature-icon">🤝</div>
        <div class="feature-title">Impact</div>
        <div class="feature-desc">Build meaningful change</div>
      </div>
    </template>

    <!-- FORM -->
    <div>

      <div class="form-title">Create Your Account</div>
      <div class="form-subtitle">Register to continue</div>

      <div class="field">
        <label>First Name</label>
        <input v-model="form.first_name" type="text" class="field-input" />
        <p v-if="errors.first_name" class="field-error">{{ errors.first_name }}</p>
      </div>

      <div class="field">
        <label>Last Name</label>
        <input v-model="form.last_name" type="text" class="field-input" />
        <p v-if="errors.last_name" class="field-error">{{ errors.last_name }}</p>
      </div>

      <div class="field">
        <label>Email</label>
        <input v-model="form.email" type="email" class="field-input" />
        <p v-if="errors.email" class="field-error">{{ errors.email }}</p>
      </div>

      <div class="field">
        <label>Phone</label>
        <input v-model="form.phone" type="text" class="field-input" placeholder="09XXXXXXXXX" />
        <p v-if="errors.phone" class="field-error">{{ errors.phone }}</p>
      </div>

      <div class="field">
        <label>Password</label>
        <div class="password-wrap">
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="field-input password-input"
            @focus="passwordFocused = true"
          />
          <button
            type="button"
            class="eye-btn"
            @click="showPassword = !showPassword"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
          >
            <svg v-if="!showPassword" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            <svg v-else width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>

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

        <p v-if="errors.password" class="field-error">{{ errors.password }}</p>
      </div>

      <div class="field">
        <label>Confirm Password</label>
        <div class="password-wrap">
          <input
            v-model="form.password_confirmation"
            :type="showConfirmPassword ? 'text' : 'password'"
            class="field-input password-input"
          />
          <button
            type="button"
            class="eye-btn"
            @click="showConfirmPassword = !showConfirmPassword"
            :aria-label="showConfirmPassword ? 'Hide password' : 'Show password'"
          >
            <svg v-if="!showConfirmPassword" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            <svg v-else width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>
        <p v-if="errors.password_confirmation" class="field-error">{{ errors.password_confirmation }}</p>
      </div>

      <p v-if="errors.general" class="field-error general-error">{{ errors.general }}</p>

      <button class="next-btn" @click="goNext">
        Next ▶
      </button>

      <div class="login-redirect">
        Already have an account?
        <router-link to="/login">Login</router-link>
      </div>

    </div>

  </FoundationAuthLayout>
</template>

<script setup>
import { reactive, computed, ref, onMounted } from "vue"
import { useRouter } from "vue-router"
import FoundationAuthLayout from "../components/foundation-admin/FoundationAuthLayout.vue"
import FoundationStepper from "../components/foundation-admin/FoundationStepper.vue"
import api from "@/services/api"

const router = useRouter()

// If the user already registered this session (e.g. clicked Back after
// successfully creating their account), skip the empty form and send them
// forward to where they actually are in the flow. router.replace (not push)
// swaps this history entry so a second Back press doesn't bounce them
// between the two pages in a loop.
onMounted(() => {
  const existingAdminId = sessionStorage.getItem("foundation_admin_id")
  if (existingAdminId) {
    router.replace("/foundation/create")
  }
})

const form = reactive({
  first_name: "",
  last_name: "",
  email: "",
  phone: "",
  password: "",
  password_confirmation: ""
})

const errors = reactive({
  first_name: "",
  last_name: "",
  email: "",
  phone: "",
  password: "",
  password_confirmation: "",
  general: ""
})

function clearErrors() {
  errors.first_name = ""
  errors.last_name = ""
  errors.email = ""
  errors.phone = ""
  errors.password = ""
  errors.password_confirmation = ""
  errors.general = ""
}

const passwordFocused = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)

const passwordChecks = computed(() => ({
  length: form.password.length >= 12,
  number: /\d/.test(form.password),
  special: /[^a-zA-Z0-9]/.test(form.password),
}))

const isPasswordValid = computed(() =>
  passwordChecks.value.length && passwordChecks.value.number && passwordChecks.value.special
)

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

async function goNext() {
  clearErrors()

  let hasError = false

  if (!form.first_name) { errors.first_name = "First name is required."; hasError = true }
  if (!form.last_name)  { errors.last_name = "Last name is required."; hasError = true }
  if (!form.email)      { errors.email = "Email is required."; hasError = true }
  if (!form.phone)      { errors.phone = "Phone number is required."; hasError = true }
  if (!form.password)   { errors.password = "Password is required."; hasError = true }
  if (!form.password_confirmation) { errors.password_confirmation = "Please confirm your password."; hasError = true }

  if (hasError) return

  const normalizedPhone = normalizePhone(form.phone)

  if (!normalizedPhone) {
    errors.phone = "Enter a valid PH phone number (09XXXXXXXXX or +639XXXXXXXXX)"
    return
  }

  if (!isPasswordValid.value) {
    errors.password = "Password must be at least 12 characters and include a number and a special character"
    return
  }

  if (form.password !== form.password_confirmation) {
    errors.password_confirmation = "Passwords do not match"
    return
  }

  try {
    const response = await api.post("/foundation-admin/register", {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      phone: normalizedPhone,
      password: form.password,
      password_confirmation: form.password_confirmation
    })

    const userId =
      response.data?.user?.id ||
      response.data?.data?.id ||
      response.data?.id

    if (!userId) {
      throw new Error("User ID not returned from backend")
    }

    sessionStorage.setItem("foundation_admin_id", userId)

    router.push("/foundation/create")

  } catch (error) {
    console.error(error)

    if (error.response?.data?.message) {
      errors.general = error.response.data.message
    } else {
      errors.general = "Registration failed"
    }
  }
}
</script>

<style scoped>
.form-title {
  text-align: center;
  font-size: 1.4rem;
  font-weight: 800;
  color: #1a1a2e;
}

.form-subtitle {
  text-align: center;
  font-size: 0.85rem;
  color: #777;
  margin-bottom: 1.5rem;
}

.field {
  margin-bottom: 1rem;
}

label {
  font-size: 0.85rem;
  font-weight: 600;
  display: block;
  margin-bottom: 5px;
}

.field-input {
  width: 95%;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 10px;
  outline: none;
}

.field-input:focus {
  border-color: #1565c0;
}

.password-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input {
  padding-right: 40px;
}

/* Hide native browser password-reveal icons so only the custom eye button shows */
.password-input::-ms-reveal,
.password-input::-ms-clear {
  display: none;
}

.password-input::-webkit-credentials-auto-fill-button {
  visibility: hidden;
  display: none !important;
  pointer-events: none;
  position: absolute;
  right: 0;
}

.eye-btn {
  position: absolute;
  right: 10px;
  background: none;
  border: none;
  cursor: pointer;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
  transition: color 0.15s;
}

.eye-btn:hover {
  color: #1565c0;
}

.field-error {
  font-size: 0.78rem;
  color: #dc2626;
  margin: 5px 0 0;
}

.general-error {
  margin-bottom: 12px;
}

.password-checklist {
  list-style: none;
  padding: 0;
  margin: 8px 0 0;
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
  color: #059669;
}

.check-icon {
  font-size: 0.85rem;
  width: 14px;
  display: inline-block;
  text-align: center;
}

.next-btn {
  width: 100%;
  padding: 14px;
  margin-top: 10px;
  background: #0d47a1;
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: bold;
  cursor: pointer;
}

.next-btn:hover {
  background: #1565c0;
}

.login-redirect {
  text-align: center;
  margin-top: 10px;
  font-size: 0.8rem;
}

.login-redirect a {
  color: #0d47a1;
  font-weight: bold;
}
</style>