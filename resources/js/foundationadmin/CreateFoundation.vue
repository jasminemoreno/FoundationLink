<template>
  <FoundationAuthLayout :showStepper="true">

    <!-- STEP -->
    <template #stepper>
      <FoundationStepper :currentStep="2" />
    </template>

    <!-- LEFT FEATURES -->
    <template #features>
      <div class="feature-item">
        <div class="feature-icon">🏛️</div>
        <div class="feature-title">Create Foundation</div>
        <div class="feature-desc">Set up organization profile</div>
      </div>

      <div class="feature-item">
        <div class="feature-icon">📊</div>
        <div class="feature-title">Manage Impact</div>
        <div class="feature-desc">Track donations & progress</div>
      </div>

      <div class="feature-item">
        <div class="feature-icon">🤝</div>
        <div class="feature-title">Build Trust</div>
        <div class="feature-desc">Verified organizations</div>
      </div>
    </template>

    <!-- FORM -->
    <div>

      <div class="form-title">Create Your Foundation</div>
      <div class="form-subtitle">Tell us about your organization</div>

      <form @submit.prevent="submitFoundation">

        <div class="field">
          <label>Foundation Name</label>
          <input v-model="form.name" type="text" class="field-input" placeholder="e.g. Bright Future Foundation" />
        </div>

        <div class="field">
          <label>Description</label>
          <textarea v-model="form.description" class="field-input field-textarea" placeholder="What does your foundation do?"></textarea>
        </div>

        <!-- CATEGORY -->
        <div class="field">
          <label>Category</label>
          <select v-model="form.category_id" class="field-input field-select">
            <option value="" disabled>Select a category</option>
            <option
              v-for="cat in categories"
              :key="cat.id"
              :value="cat.id"
            >
              {{ cat.icon }} {{ cat.name }}
            </option>
          </select>
          <p v-if="categories.length === 0" class="field-hint">
            No categories available yet. An admin must add them first.
          </p>
        </div>

        <!-- STRUCTURED ADDRESS -->
        <div class="field">
          <label>Region</label>
          <input v-model="form.region" type="text" class="field-input" placeholder="e.g. Region XI" />
        </div>

        <div class="field">
          <label>Province</label>
          <input v-model="form.province" type="text" class="field-input" placeholder="e.g. Davao del Sur" />
        </div>

        <div class="field">
          <label>City / Municipality</label>
          <input v-model="form.city" type="text" class="field-input" placeholder="e.g. Davao City" />
        </div>

        <div class="field">
          <label>Barangay</label>
          <input v-model="form.barangay" type="text" class="field-input" placeholder="e.g. Poblacion" />
        </div>

        <div class="field">
          <label>Street / Purok</label>
          <input v-model="form.street" type="text" class="field-input" placeholder="e.g. Purok 5, Rizal St." />
        </div>

        <p v-if="errorMsg" class="error-text">{{ errorMsg }}</p>

        <button class="next-btn" type="submit" :disabled="isLoading">
          <span v-if="!isLoading">Next ▶</span>
          <span v-else class="spinner" />
        </button>

      </form>

    </div>

  </FoundationAuthLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue"   // ← ref added
import { useRouter } from "vue-router"
import api from "@/services/api"

import FoundationAuthLayout from "../components/foundation-admin/FoundationAuthLayout.vue"
import FoundationStepper from "../components/foundation-admin/FoundationStepper.vue"

const router   = useRouter()
const isLoading = ref(false)
const errorMsg  = ref('')

/* ── FORM ── */
const form = reactive({
  name:        '',
  description: '',
  category_id: '',   // ← new
  region:      '',
  province:    '',
  city:        '',
  barangay:    '',
  street:      ''
})

/* ── CATEGORIES ── */
const categories = ref([])

async function fetchCategories() {
  try {
    const res = await api.get('/categories')
    categories.value = Array.isArray(res.data)
      ? res.data
      : (res.data?.data || [])
  } catch (err) {
    console.error('Failed to load categories:', err)
  }
}

/* ── GUARD + LOAD ── */
onMounted(() => {
  const adminId = sessionStorage.getItem('foundation_admin_id')
  if (!adminId) {
    router.push('/foundation/register')
    return
  }
  fetchCategories()
})

/* ── SUBMIT ── */
async function submitFoundation() {
  errorMsg.value = ''

  if (
    !form.name      ||
    !form.description ||
    !form.region    ||
    !form.province  ||
    !form.city      ||
    !form.barangay  ||
    !form.street
  ) {
    errorMsg.value = 'Please complete all required fields.'
    return
  }

  isLoading.value = true

  try {
    const adminId = sessionStorage.getItem('foundation_admin_id')

    if (!adminId) {
      errorMsg.value = 'Session expired. Please register again.'
      router.push('/foundation/register')
      return
    }

    const response = await api.post('/foundations', {
      user_id:     adminId,
      category_id: form.category_id || null,
      name:        form.name,
      description: form.description,
      region:      form.region,
      province:    form.province,
      city_municipality: form.city,
      barangay:    form.barangay,
      street:      form.street
    })

    const foundationId =
      response.data?.foundation?.id ||
      response.data?.id ||
      null

    if (!foundationId) {
      throw new Error('No foundation ID returned from backend.')
    }

    sessionStorage.setItem('foundation_id', foundationId)
    router.push('/foundation/verification')

  } catch (err) {
    console.error('Foundation creation error:', err)
    errorMsg.value =
      err.response?.data?.message ||
      'Failed to create foundation. Please try again.'
  } finally {
    isLoading.value = false
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
  width: 100%;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 10px;
  outline: none;
  font-size: 0.88rem;
  font-family: inherit;
  box-sizing: border-box;
  transition: border-color 0.2s;
}

.field-input:focus {
  border-color: #1565c0;
  box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1);
}

.field-textarea {
  height: 90px;
  resize: none;
}

.field-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  cursor: pointer;
}

.field-hint {
  font-size: 0.78rem;
  color: #f59e0b;
  margin-top: 5px;
}

.error-text {
  font-size: 0.83rem;
  font-weight: 600;
  color: #cc3333;
  background: #fff5f5;
  border: 1px solid #ffd0d0;
  border-radius: 8px;
  padding: 10px 14px;
  margin-bottom: 12px;
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
  font-size: 0.95rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.next-btn:hover:not(:disabled) { background: #1565c0; }
.next-btn:disabled { opacity: 0.65; cursor: not-allowed; }

.spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255,255,255,0.35);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>