<template>
  <div class="page">

    <PageHeader title="Settings" subtitle="Manage your foundation profile" />

    <EmptyState v-if="isLoading" loading message="Loading settings..." />

    <div v-else class="settings-layout">

      <!-- LEFT: MAIN -->
      <div class="settings-main">

        <!-- FOUNDATION INFO -->
        <div class="panel">
          <div class="panel-head">
            <h3 class="panel-title">Foundation Information</h3>
          </div>
          <div class="panel-body">

            <div class="form-group">
              <label>Foundation Name</label>
              <input v-model="form.name" type="text" placeholder="Foundation name" />
            </div>

            <div class="form-group">
              <label>Description</label>
              <textarea v-model="form.description" placeholder="About your foundation..."></textarea>
            </div>

            <div class="section-divider">Address</div>

            <div class="form-row">
              <div class="form-group">
                <label>Street / Purok</label>
                <input v-model="form.street" type="text" placeholder="e.g. Purok 5, Rizal St." />
              </div>
              <div class="form-group">
                <label>Barangay</label>
                <input v-model="form.barangay" type="text" placeholder="e.g. Poblacion" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>City / Municipality</label>
                <input v-model="form.city_municipality" type="text" placeholder="e.g. Davao City" />
              </div>
              <div class="form-group">
                <label>Province</label>
                <input v-model="form.province" type="text" placeholder="e.g. Davao del Sur" />
              </div>
            </div>

            <p v-if="successMsg" class="success-text">{{ successMsg }}</p>
            <p v-if="errorMsg"   class="error-text">{{ errorMsg }}</p>

            <div class="form-actions">
              <button class="btn-save" @click="saveInfo" :disabled="saving">
                <span v-if="!saving">Save Changes</span>
                <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
              </button>
            </div>

          </div>
        </div>

        <!-- BRANDING -->
        <div class="panel" style="margin-top: 20px;">
          <div class="panel-head">
            <h3 class="panel-title">Branding</h3>
          </div>
          <div class="panel-body">

            <div class="branding-grid">
              <div class="form-group">
                <label>Logo</label>
                <div class="upload-area square" @click="$refs.logoInput.click()">
                  <img v-if="previewLogo" :src="previewLogo" class="upload-img" alt="logo" />
                  <div v-else class="upload-placeholder">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                      <rect x="3" y="3" width="18" height="18" rx="2"/>
                      <circle cx="8.5" cy="8.5" r="1.5"/>
                      <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    <span>Upload Logo</span>
                  </div>
                </div>
                <input ref="logoInput" type="file" accept="image/*" style="display:none" @change="onLogo" />
              </div>

              <div class="form-group">
                <label>Cover Photo</label>
                <div class="upload-area wide" @click="$refs.coverInput.click()">
                  <img v-if="previewCover" :src="previewCover" class="upload-img" alt="cover" />
                  <div v-else class="upload-placeholder">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                      <rect x="3" y="3" width="18" height="18" rx="2"/>
                      <circle cx="8.5" cy="8.5" r="1.5"/>
                      <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    <span>Upload Cover Photo</span>
                  </div>
                </div>
                <input ref="coverInput" type="file" accept="image/*" style="display:none" @change="onCover" />
              </div>
            </div>

            <p v-if="brandSuccess" class="success-text">{{ brandSuccess }}</p>
            <p v-if="brandError"   class="error-text">{{ brandError }}</p>

            <div class="form-actions">
              <button class="btn-save" @click="saveBranding" :disabled="brandSaving">
                <span v-if="!brandSaving">Save Branding</span>
                <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
              </button>
            </div>

          </div>
        </div>

        <!-- PAYMENT ACCOUNTS -->
        <div class="panel" style="margin-top: 20px;">
          <div class="panel-head">
            <h3 class="panel-title">Payment Accounts</h3>
          </div>
          <div class="panel-body">

            <p class="panel-hint">Set up your account details so donors can send payments to you.</p>

            <EmptyState v-if="paymentLoading" loading message="Loading payment methods..." compact />

            <EmptyState
              v-else-if="paymentMethods.length === 0"
              icon="💳" message="No payment methods available yet." compact
            />

            <div v-else>
              <div
                class="payment-account-row"
                v-for="m in paymentMethods"
                :key="m.payment_method_id"
              >
                <div class="payment-account-head">
                  <div class="payment-account-icon">{{ m.icon || '💳' }}</div>
                  <div class="payment-account-info">
                    <div class="payment-account-name">{{ m.name }}</div>
                    <div class="payment-account-desc">{{ m.description }}</div>
                  </div>
                  <Badge :label="m.is_configured ? 'Configured' : 'Not set up'" :tone="m.is_configured ? 'success' : 'warning'" />
                </div>

                <div class="form-row" style="margin-top: 12px;">
                  <div class="form-group" style="margin-bottom: 0;">
                    <label>Account Name</label>
                    <input
                      v-model="m.account_name"
                      type="text"
                      placeholder="e.g. Juan Dela Cruz"
                    />
                  </div>
                  <div class="form-group" style="margin-bottom: 0;">
                    <label>Account Number</label>
                    <input
                      v-model="m.account_number"
                      type="text"
                      :placeholder="placeholderFor(m.slug)"
                    />
                  </div>
                </div>
              </div>

              <p v-if="paymentSuccess" class="success-text" style="margin-top: 14px;">{{ paymentSuccess }}</p>
              <p v-if="paymentError"   class="error-text"   style="margin-top: 14px;">{{ paymentError }}</p>

              <div class="form-actions" style="margin-top: 16px;">
                <button class="btn-save" @click="savePayments" :disabled="paymentSaving">
                  <span v-if="!paymentSaving">Save Payment Accounts</span>
                  <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
                </button>
              </div>
            </div>

          </div>
        </div>

      </div>

      <!-- RIGHT: SIDEBAR -->
      <div class="settings-side">

        <!-- FOUNDATION CARD -->
        <div class="panel">
          <div class="panel-body">
            <div class="foundation-card">
              <div class="fc-cover" :style="previewCover ? `background-image: url(${previewCover})` : ''">
                <div v-if="!previewCover" class="fc-cover-placeholder">No Cover</div>
              </div>
              <div class="fc-body">
                <div class="fc-logo">
                  <img v-if="previewLogo" :src="previewLogo" alt="logo" />
                  <span v-else>{{ initials }}</span>
                </div>
                <div class="fc-name">{{ form.name || 'Foundation Name' }}</div>
                <div class="fc-desc">{{ form.description?.slice(0, 80) || 'No description' }}{{ form.description?.length > 80 ? '...' : '' }}</div>
                <div class="fc-address" v-if="form.city_municipality">
                  📍 {{ form.city_municipality }}{{ form.province ? ', ' + form.province : '' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ACCOUNT STATUS -->
        <div class="panel" style="margin-top: 16px;">
          <div class="panel-head">
            <h3 class="panel-title">Account Status</h3>
          </div>
          <div class="panel-body info-list">
            <div class="info-row">
              <span class="info-label">Status</span>
              <Badge :label="statusLabel(foundation?.status)" :tone="toneForFoundationStatus(foundation?.status)" />
            </div>
            <div class="info-row">
              <span class="info-label">Category</span>
              <span>{{ foundation?.category?.name || '—' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Member Since</span>
              <span>{{ formatDate(foundation?.created_at) }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Foundation ID</span>
              <span class="id-badge">#{{ foundation?.id }}</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import api from "@/services/api"
import PageHeader from "../components/foundation-admin/pageheader.vue"
import EmptyState from "../components/foundation-admin/emptystate.vue"
import Badge from "../components/foundation-admin/badge.vue"
import Spinner from "../components/foundation-admin/spinner.vue"

const isLoading   = ref(false)
const foundation  = ref(null)
const saving      = ref(false)
const brandSaving = ref(false)
const successMsg  = ref("")
const errorMsg    = ref("")
const brandSuccess = ref("")
const brandError   = ref("")

const previewLogo  = ref(null)
const previewCover = ref(null)
const logoFile     = ref(null)
const coverFile    = ref(null)
const logoInput    = ref(null)
const coverInput   = ref(null)

const form = ref({
  name:              "",
  description:       "",
  street:            "",
  barangay:          "",
  city_municipality: "",
  province:          "",
})

// Payment accounts state
const paymentMethods  = ref([])
const paymentLoading  = ref(false)
const paymentSaving   = ref(false)
const paymentSuccess  = ref("")
const paymentError    = ref("")

/* ── LOAD ── */
async function load() {
  isLoading.value = true
  try {
    const res = await api.get("/foundation/settings")
    foundation.value = res.data

    form.value = {
      name:              res.data.name              || "",
      description:       res.data.description       || "",
      street:            res.data.street            || "",
      barangay:          res.data.barangay          || "",
      city_municipality: res.data.city_municipality || "",
      province:          res.data.province          || "",
    }

    if (res.data.logo)        previewLogo.value  = getImage(res.data.logo)
    if (res.data.cover_photo) previewCover.value = getImage(res.data.cover_photo)

  } catch (err) {
    console.error("Failed to load settings:", err)
  } finally {
    isLoading.value = false
  }
}

async function loadPayments() {
  paymentLoading.value = true
  try {
    const res = await api.get("/foundation/payment-accounts")
    paymentMethods.value = Array.isArray(res.data) ? res.data : []
  } catch (err) {
    console.error(err)
    paymentMethods.value = []
  } finally {
    paymentLoading.value = false
  }
}

onMounted(() => {
  load()
  loadPayments()
})

/* ── SAVE INFO ── */
async function saveInfo() {
  successMsg.value = ""
  errorMsg.value   = ""
  saving.value     = true
  try {
    const fd = new FormData()
    fd.append("name",              form.value.name)
    fd.append("description",       form.value.description       || "")
    fd.append("street",            form.value.street            || "")
    fd.append("barangay",          form.value.barangay          || "")
    fd.append("city_municipality", form.value.city_municipality || "")
    fd.append("province",          form.value.province          || "")
    const res = await api.post("/foundation/settings", fd)
    foundation.value = res.data.foundation
    successMsg.value = "Foundation info updated successfully."
    setTimeout(() => successMsg.value = "", 3000)
  } catch (err) {
    errorMsg.value = err.response?.data?.message || "Failed to save."
  } finally {
    saving.value = false
  }
}

/* ── SAVE BRANDING ── */
async function saveBranding() {
  brandSuccess.value = ""
  brandError.value   = ""
  brandSaving.value  = true
  try {
    const fd = new FormData()
    if (logoFile.value  instanceof File) fd.append("logo",        logoFile.value)
    if (coverFile.value instanceof File) fd.append("cover_photo", coverFile.value)
    if (!logoFile.value && !coverFile.value) {
      brandError.value  = "Please select a logo or cover photo to upload."
      brandSaving.value = false
      return
    }
    const res = await api.post("/foundation/settings", fd)
    foundation.value   = res.data.foundation
    brandSuccess.value = "Branding updated successfully."
    setTimeout(() => brandSuccess.value = "", 3000)
  } catch (err) {
    brandError.value = err.response?.data?.message || "Failed to save branding."
  } finally {
    brandSaving.value = false
  }
}

/* ── SAVE PAYMENTS ── */
async function savePayments() {
  paymentSuccess.value = ""
  paymentError.value   = ""

  const accounts = paymentMethods.value
    .filter(m => m.account_name?.trim() && m.account_number?.trim())
    .map(m => ({
      payment_method_id: m.payment_method_id,
      account_name:      m.account_name.trim(),
      account_number:    m.account_number.trim(),
    }))

  if (accounts.length === 0) {
    paymentError.value = "Please fill in at least one payment account before saving."
    return
  }

  paymentSaving.value = true
  try {
    await api.post("/foundation/payment-accounts", { accounts })
    paymentSuccess.value = "Payment accounts saved successfully."
    await loadPayments()
    setTimeout(() => paymentSuccess.value = "", 3000)
  } catch (err) {
    const errors = err.response?.data?.errors
    paymentError.value = errors
      ? Object.values(errors).flat().join(" · ")
      : err.response?.data?.message || "Failed to save payment accounts."
  } finally {
    paymentSaving.value = false
  }
}

/* ── FILES ── */
function onLogo(e) {
  const f = e.target.files[0]
  if (!f) return
  logoFile.value    = f
  previewLogo.value = URL.createObjectURL(f)
}

function onCover(e) {
  const f = e.target.files[0]
  if (!f) return
  coverFile.value    = f
  previewCover.value = URL.createObjectURL(f)
}

function placeholderFor(slug) {
  const map = {
    gcash:   "09171234567",
    paymaya: "09171234567",
    paypal:  "name@email.com",
    bdo:     "Account number",
    bpi:     "Account number",
  }
  return map[slug] || "Account number"
}

/* ── HELPERS ── */
const initials = computed(() => {
  if (!form.value.name) return 'F'
  return form.value.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
})

function getImage(path) {
  if (!path) return ""
  return `/storage/${path}`
}

function formatDate(d) {
  if (!d) return "—"
  return new Date(d).toLocaleDateString("en-PH", { year: "numeric", month: "long", day: "numeric" })
}

function statusLabel(s) {
  if (s === 'verified')             return 'Verified'
  if (s === 'pending_verification') return 'Pending Review'
  if (s === 'rejected')             return 'Rejected'
  if (s === 'suspended')            return 'Suspended'
  return s || '—'
}

function toneForFoundationStatus(s) {
  if (s === 'verified')  return 'success'
  if (s === 'rejected')  return 'danger'
  if (s === 'suspended') return 'neutral'
  return 'warning'
}
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

.settings-layout { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; }
.settings-main   { display: flex; flex-direction: column; }
.settings-side   { display: flex; flex-direction: column; }

.panel { background: white; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(15,45,82,0.06); overflow: hidden; }
.panel-head  { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px 12px; border-bottom: 1px solid #f1f5f9; }
.panel-title { font-size: 0.92rem; font-weight: 700; color: #0F2D52; margin: 0; }
.panel-hint  { font-size: 0.82rem; color: #94a3b8; margin: 0 0 16px; }
.panel-body  { padding: 20px; }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
.form-group input,
.form-group textarea {
  width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px;
  font-size: 0.87rem; outline: none; box-sizing: border-box; font-family: inherit; transition: border-color 0.2s;
}
.form-group input:focus,
.form-group textarea:focus { border-color: #0F2D52; box-shadow: 0 0 0 3px rgba(15,45,82,0.08); }
.form-group textarea { height: 100px; resize: none; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

.section-divider { font-size: 0.72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.07em; margin: 4px 0 14px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }

.success-text { font-size: 0.82rem; color: #059669; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 9px 13px; margin-bottom: 4px; }
.error-text   { font-size: 0.82rem; color: #cc3333; background: #fff5f5; border: 1px solid #ffd0d0; border-radius: 8px; padding: 9px 13px; margin-bottom: 4px; }

.form-actions { display: flex; justify-content: flex-end; margin-top: 8px; }
.btn-save { padding: 10px 22px; border: none; background: #0F2D52; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; min-width: 130px; transition: background 0.2s; }
.btn-save:hover:not(:disabled) { background: #1a3f6b; }
.btn-save:disabled { opacity: 0.65; cursor: not-allowed; }

/* BRANDING */
.branding-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 16px; }
.upload-area { border: 2px dashed #e2e8f0; border-radius: 12px; cursor: pointer; overflow: hidden; transition: border-color 0.2s; display: flex; align-items: center; justify-content: center; }
.upload-area:hover { border-color: #0F2D52; }
.upload-area.square { height: 120px; }
.upload-area.wide   { height: 120px; }
.upload-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.upload-placeholder { display: flex; flex-direction: column; align-items: center; gap: 6px; color: #94a3b8; font-size: 0.78rem; padding: 16px; }

/* PAYMENT ACCOUNTS */
.payment-account-row { padding: 16px 0; border-bottom: 1px solid #f1f5f9; }
.payment-account-row:last-child { border-bottom: none; }
.payment-account-head { display: flex; align-items: center; gap: 12px; }
.payment-account-icon { font-size: 1.6rem; flex-shrink: 0; }
.payment-account-info { flex: 1; }
.payment-account-name { font-size: 0.88rem; font-weight: 700; color: #0F2D52; }
.payment-account-desc { font-size: 0.75rem; color: #94a3b8; margin-top: 2px; }

/* FOUNDATION PREVIEW CARD */
.foundation-card { border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
.fc-cover { height: 80px; background: #0F2D52; background-size: cover; background-position: center; display: flex; align-items: center; justify-content: center; }
.fc-cover-placeholder { color: rgba(255,255,255,0.4); font-size: 0.75rem; }
.fc-body  { padding: 12px 14px; }
.fc-logo  { width: 40px; height: 40px; border-radius: 10px; background: #0F2D52; color: white; font-size: 0.85rem; font-weight: 800; display: flex; align-items: center; justify-content: center; margin-top: -24px; border: 2px solid white; margin-bottom: 8px; overflow: hidden; }
.fc-logo img { width: 100%; height: 100%; object-fit: cover; }
.fc-name    { font-size: 0.88rem; font-weight: 700; color: #0F2D52; margin-bottom: 4px; }
.fc-desc    { font-size: 0.75rem; color: #64748b; line-height: 1.4; margin-bottom: 6px; }
.fc-address { font-size: 0.72rem; color: #94a3b8; }

/* INFO LIST */
.info-list { padding: 4px 0; }
.info-row  { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.83rem; }
.info-row:last-child { border-bottom: none; }
.info-label { color: #94a3b8; font-size: 0.78rem; }
.id-badge   { font-size: 0.75rem; font-weight: 700; background: #f1f5f9; color: #0F2D52; padding: 2px 8px; border-radius: 6px; }
</style>