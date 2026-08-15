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
      </div>

      <div class="field">
        <label>Last Name</label>
        <input v-model="form.last_name" type="text" class="field-input" />
      </div>

      <div class="field">
        <label>Email</label>
        <input v-model="form.email" type="email" class="field-input" />
      </div>

      <div class="field">
        <label>Phone</label>
        <input v-model="form.phone" type="text" class="field-input" />
      </div>

      <div class="field">
        <label>Password</label>
        <input v-model="form.password" type="password" class="field-input" />
      </div>

      <div class="field">
        <label>Confirm Password</label>
        <input v-model="form.password_confirmation" type="password" class="field-input" />
      </div>

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
import { reactive } from "vue"
import { useRouter } from "vue-router"
import FoundationAuthLayout from "../components/foundation-admin/FoundationAuthLayout.vue"
import FoundationStepper from "../components/foundation-admin/FoundationStepper.vue"
import api from "@/services/api"

const router = useRouter()

const form = reactive({
  first_name: "",
  last_name: "",
  email: "",
  phone: "",
  password: "",
  password_confirmation: ""
})

async function goNext() {
  if (
    !form.first_name ||
    !form.last_name ||
    !form.email ||
    !form.phone ||
    !form.password ||
    !form.password_confirmation
  ) {
    alert("Please complete all fields")
    return
  }

  if (form.password !== form.password_confirmation) {
    alert("Passwords do not match")
    return
  }

  try {
    const response = await api.post("/foundation-admin/register", {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      phone: form.phone,
      password: form.password,
      password_confirmation: form.password_confirmation
    })

    // ✅ SAFE RESPONSE HANDLING (FIXES YOUR BUG)
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
      alert(error.response.data.message)
    } else {
      alert("Registration failed")
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