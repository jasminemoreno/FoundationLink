<template>
  <div class="page">

    <PageHeader title="Payment Accounts" subtitle="Set up your account details once — then just pick which methods to accept per campaign" />

    <!-- LOADING -->
    <EmptyState v-if="isLoading" loading message="Loading payment methods..." />

    <!-- EMPTY -->
    <EmptyState
      v-else-if="methods.length === 0"
      icon="💳" message="No payment methods available yet. Please check back later."
    />

    <!-- LIST -->
    <div v-else class="accounts-list">
      <div class="account-card" v-for="m in methods" :key="m.payment_method_id">

        <div class="account-head">
          <div class="account-icon">{{ m.icon || '💳' }}</div>
          <div class="account-name-wrap">
            <div class="account-name">{{ m.name }}</div>
            <div class="account-desc">{{ m.description }}</div>
          </div>
          <Badge :label="m.is_configured ? 'Configured' : 'Not set up'" :tone="m.is_configured ? 'success' : 'warning'" />
        </div>

        <div class="account-form">
          <div class="form-row">
            <div class="form-group">
              <label>Account Name</label>
              <input
                v-model="m.account_name"
                type="text"
                placeholder="e.g. Juan Dela Cruz / Foundation Name"
              />
            </div>
            <div class="form-group">
              <label>Account Number</label>
              <input
                v-model="m.account_number"
                type="text"
                :placeholder="placeholderFor(m.slug)"
              />
            </div>
          </div>
        </div>

      </div>

      <div class="save-bar">
        <p v-if="successMsg" class="success-text">{{ successMsg }}</p>
        <p v-if="errorMsg" class="error-text">{{ errorMsg }}</p>
        <button class="btn-save-all" @click="saveAll" :disabled="saving">
          <span v-if="!saving">Save All Changes</span>
          <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import api from "@/services/api"
import PageHeader from "../components/foundation-admin/pageheader.vue"
import EmptyState from "../components/foundation-admin/emptystate.vue"
import Badge from "../components/foundation-admin/badge.vue"
import Spinner from "../components/foundation-admin/spinner.vue"

const methods    = ref([])
const isLoading  = ref(false)
const saving     = ref(false)
const successMsg = ref("")
const errorMsg    = ref("")

async function load() {
  isLoading.value = true
  try {
    const res = await api.get("/foundation/payment-accounts")
    methods.value = Array.isArray(res.data) ? res.data : []
  } catch (err) {
    console.error(err)
    methods.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

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

async function saveAll() {
  successMsg.value = ""
  errorMsg.value   = ""

  // Only save entries the foundation actually filled in
  const accounts = methods.value
    .filter(m => m.account_name?.trim() && m.account_number?.trim())
    .map(m => ({
      payment_method_id: m.payment_method_id,
      account_name:      m.account_name.trim(),
      account_number:    m.account_number.trim(),
    }))

  if (accounts.length === 0) {
    errorMsg.value = "Please fill in at least one payment account before saving."
    return
  }

  saving.value = true
  try {
    await api.post("/foundation/payment-accounts", { accounts })
    successMsg.value = "Payment accounts saved successfully."
    await load()
  } catch (err) {
    const errors = err.response?.data?.errors
    errorMsg.value = errors
      ? Object.values(errors).flat().join(" · ")
      : err.response?.data?.message || "Failed to save payment accounts."
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

.accounts-list { display: flex; flex-direction: column; gap: 14px; max-width: 760px; }

.account-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(15,45,82,0.06); }

.account-head { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 16px; }
.account-icon { font-size: 1.8rem; line-height: 1; flex-shrink: 0; }
.account-name-wrap { flex: 1; }
.account-name { font-size: 0.95rem; font-weight: 800; color: #0F2D52; }
.account-desc { font-size: 0.78rem; color: #94a3b8; margin-top: 2px; }

.account-form { padding-top: 14px; border-top: 1px solid #f1f5f9; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
.form-group input {
  width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px;
  font-size: 0.87rem; outline: none; box-sizing: border-box; transition: border-color 0.2s;
}
.form-group input:focus { border-color: #0F2D52; box-shadow: 0 0 0 3px rgba(15,45,82,0.08); }

.save-bar { display: flex; align-items: center; justify-content: flex-end; gap: 14px; margin-top: 8px; flex-wrap: wrap; }
.success-text { font-size: 0.82rem; color: #059669; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 8px 14px; margin: 0; }
.error-text   { font-size: 0.82rem; color: #cc3333; background: #fff5f5; border: 1px solid #ffd0d0; border-radius: 8px; padding: 8px 14px; margin: 0; }

.btn-save-all { padding: 11px 24px; border: none; background: #0F2D52; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; min-width: 160px; transition: background 0.2s; }
.btn-save-all:hover:not(:disabled) { background: #1a3f6b; }
.btn-save-all:disabled { opacity: 0.65; cursor: not-allowed; }
</style>