<template>
  <FoundationAuthLayout :showStepper="true">

    <!-- STEP -->
    <template #stepper>
      <FoundationStepper :currentStep="3" />
    </template>

    <!-- LEFT FEATURES -->
    <template #features>
      <div class="feature-item">
        <div class="feature-icon">🪪</div>
        <div class="feature-title">Identity Verification</div>
        <div class="feature-desc">Secure admin authentication</div>
      </div>

      <div class="feature-item">
        <div class="feature-icon">📄</div>
        <div class="feature-title">Document Review</div>
        <div class="feature-desc">Validate foundation legitimacy</div>
      </div>

      <div class="feature-item">
        <div class="feature-icon">🔒</div>
        <div class="feature-title">Approval System</div>
        <div class="feature-desc">Safe verification process</div>
      </div>
    </template>

    <!-- FORM -->
    <div>
      <h2 class="form-title">Foundation Verification</h2>
      <p class="form-subtitle">Upload required documents for approval</p>

      <form @submit.prevent="submitVerification">

        <!-- ADMIN ID TYPE -->
        <div class="field">
          <label>Foundation Admin Identity</label>
          <select v-model="form.admin_id_type" class="field-input">
            <option value="" disabled>Select Identity Type</option>
            <option>National ID</option>
            <option>Passport</option>
            <option>Driver's License</option>
          </select>
        </div>

        <!-- ADMIN FILES -->
        <div class="field">
          <label>Upload Admin Identity (1–2 images)</label>
          <input
            type="file"
            accept="image/*"
            multiple
            class="field-input"
            @change="handleAdminFiles"
          />
          <p class="field-hint" v-if="form.admin_files.length">
            {{ form.admin_files.length }} image{{ form.admin_files.length > 1 ? 's' : '' }} selected
          </p>
        </div>

        <!-- DOCUMENT TYPE -->
        <div class="field">
          <label>Foundation Legitimacy Document</label>
          <select v-model="form.doc_type" class="field-input">
            <option value="" disabled>Select Document Type</option>
            <option>SEC Certificate of Registration</option>
            <option>DSWD Accreditation</option>
            <option>Government Charter</option>
            <option>CDA Registration</option>
            <option>Other Legal Proof</option>
          </select>
        </div>

        <!-- DOCUMENT FILES -->
        <div class="field">
          <label>Upload Legitimacy Document (1–2 images)</label>
          <input
            type="file"
            accept="image/*"
            multiple
            class="field-input"
            @change="handleDocFiles"
          />
          <p class="field-hint" v-if="form.doc_files.length">
            {{ form.doc_files.length }} image{{ form.doc_files.length > 1 ? 's' : '' }} selected
          </p>
        </div>

        <p v-if="errorMsg" class="error-text">{{ errorMsg }}</p>

        <button class="next-btn" type="submit" :disabled="isSubmitting">
          <span v-if="!isSubmitting">Submit for Verification ▶</span>
          <span v-else class="spinner" />
        </button>

      </form>
    </div>

    <!-- SUCCESS POPUP -->
    <div v-if="showSuccess" class="success-overlay">
      <div class="success-popup">
        <div class="success-icon">✓</div>
        <p>Verification submitted successfully!</p>
      </div>
    </div>

  </FoundationAuthLayout>
</template>

<script setup>
import { reactive, ref, onMounted } from "vue"
import { useRouter } from "vue-router"
import api from "@/services/api"

import FoundationAuthLayout from "../components/foundation-admin/FoundationAuthLayout.vue"
import FoundationStepper from "../components/foundation-admin/FoundationStepper.vue"

const router = useRouter()
const errorMsg = ref('')
const isSubmitting = ref(false)
const showSuccess = ref(false)

const form = reactive({
  admin_id_type: "",
  doc_type: "",
  admin_files: [],
  doc_files: []
})

onMounted(() => {
  const adminId = sessionStorage.getItem("foundation_admin_id")
  const foundationId = sessionStorage.getItem("foundation_id")

  if (!adminId || !foundationId) {
    router.push("/foundation/register")
  }
})

function handleAdminFiles(e) {
  const files = Array.from(e.target.files || [])
  form.admin_files = files.slice(0, 2) // allows 1 or 2 images
}

function handleDocFiles(e) {
  const files = Array.from(e.target.files || [])
  form.doc_files = files.slice(0, 2) // allows 1 or 2 images
}

async function submitVerification() {
  errorMsg.value = ''

  if (
    !form.admin_id_type ||
    !form.doc_type ||
    form.admin_files.length < 1 || form.admin_files.length > 2 ||
    form.doc_files.length < 1 || form.doc_files.length > 2
  ) {
    errorMsg.value = "Please upload 1 or 2 images for each section."
    return
  }

  isSubmitting.value = true

  try {
    const formData = new FormData()

    formData.append("foundation_id", sessionStorage.getItem("foundation_id"))
    formData.append("admin_id_type", form.admin_id_type)
    formData.append("doc_type", form.doc_type)

    form.admin_files.forEach(file => {
      formData.append("admin_files[]", file)
    })

    form.doc_files.forEach(file => {
      formData.append("doc_files[]", file)
    })

    await api.post("/foundation-verification", formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    isSubmitting.value = false
    showSuccess.value = true

    setTimeout(() => {
      showSuccess.value = false
      router.push("/login")
    }, 2000)

  } catch (err) {
    console.error(err)
    isSubmitting.value = false
    errorMsg.value = err.response?.data?.message || "Failed to submit verification"
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
}

.field-hint {
  font-size: 0.78rem;
  color: #1a8a52;
  margin-top: 5px;
  font-weight: 600;
}

.error-text {
  font-size: 0.83rem;
  font-weight: 600;
  color: #cc3333;
  background: #fff5f5;
  border: 1px solid #ffd0d0;
  border-radius: 8px;
  padding: 10px 14px;
  margin: 12px 0;
}

.next-btn {
  width: 100%;
  padding: 14px;
  background: #0d47a1;
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.2s;
}

.next-btn:hover:not(:disabled) {
  background: #1565c0;
}

.next-btn:disabled {
  opacity: 0.75;
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

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* SUCCESS POPUP */
.success-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}

.success-popup {
  background: white;
  border-radius: 16px;
  padding: 32px 40px;
  text-align: center;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
  animation: popIn 0.25s ease;
}

.success-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #f0fdf4;
  color: #059669;
  font-size: 1.6rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 14px;
}

.success-popup p {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0;
}

@keyframes popIn {
  from { transform: scale(0.85); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
</style>