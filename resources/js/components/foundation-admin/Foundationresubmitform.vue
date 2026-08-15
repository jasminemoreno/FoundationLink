<template>
    <div class="resubmit-wrap">
      <div class="resubmit-title">Edit Your Foundation Details</div>
      <p class="resubmit-sub">Update the information below and resubmit for review.</p>
  
      <form @submit.prevent="submit">
  
        <div class="field">
          <label>Foundation Name</label>
          <input v-model="form.name" type="text" class="field-input" />
        </div>
  
        <div class="field">
          <label>Description</label>
          <textarea v-model="form.description" class="field-input field-textarea"></textarea>
        </div>
  
        <div class="field">
          <label>Category</label>
          <select v-model="form.category_id" class="field-input field-select">
            <option value="" disabled>Select a category</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
              {{ cat.icon }} {{ cat.name }}
            </option>
          </select>
        </div>
  
        <div class="field">
          <label>Province</label>
          <input v-model="form.province" type="text" class="field-input" />
        </div>
  
        <div class="field">
          <label>City / Municipality</label>
          <input v-model="form.city_municipality" type="text" class="field-input" />
        </div>
  
        <div class="field">
          <label>Barangay</label>
          <input v-model="form.barangay" type="text" class="field-input" />
        </div>
  
        <div class="field">
          <label>Street / Purok</label>
          <input v-model="form.street" type="text" class="field-input" />
        </div>
  
        <!-- DOCUMENT RE-UPLOAD (collapsible, optional) -->
        <div class="doc-toggle" @click="showDocs = !showDocs">
          <span>📄 Re-upload Identity / Legitimacy Documents</span>
          <span class="chevron">{{ showDocs ? '▲' : '▼' }}</span>
        </div>
        <p class="doc-hint">
          Only needed if the rejection was about an invalid ID or document. Skip this if your rejection was about foundation info.
        </p>
  
        <div v-if="showDocs" class="doc-section">
  
          <div class="field">
            <label>Foundation Admin Identity</label>
            <select v-model="form.admin_id_type" class="field-input">
              <option value="" disabled>Select Identity Type</option>
              <option>National ID</option>
              <option>Passport</option>
              <option>Driver's License</option>
            </select>
          </div>
  
          <div class="field">
            <label>Upload Admin Identity (Select 2 Images)</label>
            <input
              type="file"
              accept="image/*"
              multiple
              class="field-input"
              @change="handleAdminFiles"
            />
          </div>
  
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
  
          <div class="field">
            <label>Upload Legitimacy Document (Select 2 Images)</label>
            <input
              type="file"
              accept="image/*"
              multiple
              class="field-input"
              @change="handleDocFiles"
            />
          </div>
  
        </div>
  
        <p v-if="errorMsg" class="error-text">{{ errorMsg }}</p>
  
        <div class="btn-row">
          <button type="button" class="cancel-btn" @click="$emit('cancel')">
            Cancel
          </button>
          <button type="submit" class="submit-btn" :disabled="isLoading">
            <span v-if="!isLoading">Resubmit for Review ▶</span>
            <span v-else class="spinner" />
          </button>
        </div>
  
      </form>
    </div>
  </template>
  
  <script setup>
  import { reactive, ref, onMounted } from "vue"
  import api from "@/services/api"
  
  const props = defineProps({
    foundation: { type: Object, required: true }
  })
  const emit = defineEmits(["submitted", "cancel"])
  
  const isLoading = ref(false)
  const errorMsg  = ref("")
  const categories = ref([])
  const showDocs = ref(false)
  
  const form = reactive({
    name: props.foundation?.name || "",
    description: props.foundation?.description || "",
    category_id: props.foundation?.category_id || "",
    province: props.foundation?.province || "",
    city_municipality: props.foundation?.city_municipality || "",
    barangay: props.foundation?.barangay || "",
    street: props.foundation?.street || "",
    admin_id_type: "",
    doc_type: "",
    admin_files: [],
    doc_files: [],
  })
  
  function handleAdminFiles(e) {
    const files = Array.from(e.target.files || [])
    form.admin_files = files.slice(0, 2)
  }
  
  function handleDocFiles(e) {
    const files = Array.from(e.target.files || [])
    form.doc_files = files.slice(0, 2)
  }
  
  async function fetchCategories() {
    try {
      const res = await api.get("/categories")
      categories.value = Array.isArray(res.data) ? res.data : (res.data?.data || [])
    } catch (err) {
      console.error("Failed to load categories:", err)
    }
  }
  
  onMounted(fetchCategories)
  
  async function submit() {
    errorMsg.value = ""
  
    if (
      !form.name ||
      !form.description ||
      !form.province ||
      !form.city_municipality ||
      !form.barangay ||
      !form.street
    ) {
      errorMsg.value = "Please complete all required fields."
      return
    }
  
    if (showDocs.value) {
      if (
        !form.admin_id_type ||
        !form.doc_type ||
        form.admin_files.length !== 2 ||
        form.doc_files.length !== 2
      ) {
        errorMsg.value = "Please upload EXACTLY 2 images for each document section, or collapse the document panel if you don't need to re-upload."
        return
      }
    }
  
    isLoading.value = true
  
    try {
      const formData = new FormData()
      formData.append("name", form.name)
      formData.append("description", form.description)
      if (form.category_id) formData.append("category_id", form.category_id)
      formData.append("street", form.street)
      formData.append("barangay", form.barangay)
      formData.append("city_municipality", form.city_municipality)
      formData.append("province", form.province)
  
      if (showDocs.value) {
        formData.append("admin_id_type", form.admin_id_type)
        formData.append("doc_type", form.doc_type)
        form.admin_files.forEach(file => formData.append("admin_files[]", file))
        form.doc_files.forEach(file => formData.append("doc_files[]", file))
      }
  
      const response = await api.post("/foundation/resubmit", formData, {
        headers: { "Content-Type": "multipart/form-data" }
      })
  
      const updatedFoundation = response.data?.foundation
      if (!updatedFoundation) {
        throw new Error("No foundation returned from backend.")
      }
  
      emit("submitted", updatedFoundation)
  
    } catch (err) {
      console.error("Resubmit error:", err)
      errorMsg.value =
        err.response?.data?.message ||
        "Failed to resubmit. Please try again."
    } finally {
      isLoading.value = false
    }
  }
  </script>
  
  <style scoped>
  .resubmit-wrap {
    background: white;
    border: 1px solid #fca5a5;
    border-radius: 14px;
    padding: 24px 26px;
    margin-top: 22px;
    text-align: left;
  }
  
  .resubmit-title {
    font-size: 1rem;
    font-weight: 800;
    color: #0F2D52;
    margin-bottom: 4px;
  }
  
  .resubmit-sub {
    font-size: 0.82rem;
    color: #777;
    margin-bottom: 18px;
  }
  
  .field { margin-bottom: 14px; }
  
  label {
    font-size: 0.83rem;
    font-weight: 600;
    display: block;
    margin-bottom: 5px;
    color: #1a1a2e;
  }
  
  .field-input {
    width: 100%;
    padding: 11px;
    border: 1px solid #ddd;
    border-radius: 10px;
    outline: none;
    font-size: 0.86rem;
    font-family: inherit;
    box-sizing: border-box;
    transition: border-color 0.2s;
  }
  
  .field-input:focus {
    border-color: #1565c0;
    box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1);
  }
  
  .field-textarea { height: 80px; resize: none; }
  
  .doc-toggle {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 0.85rem;
    font-weight: 700;
    color: #0F2D52;
    cursor: pointer;
    margin-top: 6px;
    user-select: none;
  }
  .doc-toggle:hover { background: #f1f5f9; }
  .chevron { font-size: 0.7rem; color: #94a3b8; }
  
  .doc-hint {
    font-size: 0.76rem;
    color: #94a3b8;
    margin: 6px 2px 14px;
    line-height: 1.5;
  }
  
  .doc-section {
    background: #fafbfc;
    border: 1px dashed #cbd5e1;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 14px;
  }
  
  .field-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    cursor: pointer;
  }
  
  .error-text {
    font-size: 0.82rem;
    font-weight: 600;
    color: #cc3333;
    background: #fff5f5;
    border: 1px solid #ffd0d0;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 12px;
  }
  
  .btn-row {
    display: flex;
    gap: 10px;
    margin-top: 6px;
  }
  
  .cancel-btn {
    flex: 1;
    padding: 12px;
    background: #f1f5f9;
    color: #475569;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
  }
  .cancel-btn:hover { background: #e2e8f0; }
  
  .submit-btn {
    flex: 2;
    padding: 12px;
    background: #0d47a1;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: bold;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
  }
  .submit-btn:hover:not(:disabled) { background: #1565c0; }
  .submit-btn:disabled { opacity: 0.65; cursor: not-allowed; }
  
  .spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
  }
  
  @keyframes spin { to { transform: rotate(360deg); } }
  </style>