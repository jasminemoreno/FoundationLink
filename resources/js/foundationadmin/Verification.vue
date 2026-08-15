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

        <button class="next-btn" type="submit">
          Submit for Verification ▶
        </button>

      </form>
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

    alert("Verification submitted successfully!")
    router.push("/login")

  } catch (err) {
    console.error(err)
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
}

.next-btn:hover {
  background: #1565c0;
}
</style>