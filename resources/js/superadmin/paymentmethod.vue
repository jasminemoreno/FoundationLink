<template>
  <div class="page">

    <PageHeader title="Payment Methods" subtitle="Manage which payment methods foundations can offer to donors">
      <template #actions>
        <button class="btn-add" @click="openCreate">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          New Payment Method
        </button>
      </template>
    </PageHeader>

    <!-- STATS -->
    <div class="stats-row">
      <StatCard
        layout="horizontal"
        :value="methods.length"
        label="Total Methods"
        bg="#eff6ff" col="#3b82f6"
        icon='<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>'
      />
      <StatCard
        layout="horizontal"
        :value="activeCount"
        label="Active"
        bg="#f0fdf4" col="#059669"
        icon='<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>'
      />
      <StatCard
        layout="horizontal"
        :value="methods.length - activeCount"
        label="Inactive"
        bg="#f1f5f9" col="#64748b"
        icon='<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>'
      />
    </div>

    <!-- LOADING -->
    <EmptyState v-if="isLoading" loading loading-message="Loading payment methods..." />

    <!-- EMPTY -->
    <EmptyState v-else-if="methods.length === 0" icon="💳" message="No payment methods yet.">
      <template #action>
        <button class="btn-add" @click="openCreate">Add your first payment method</button>
      </template>
    </EmptyState>

    <!-- GRID -->
    <div v-else class="grid">
      <div class="card" v-for="m in methods" :key="m.id" :class="{ inactive: !m.is_active }">
        <div class="card-top">
          <div class="card-icon">{{ m.icon || '💳' }}</div>
          <span class="status-pill" :class="m.is_active ? 'active' : 'paused'">
            {{ m.is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>
        <h3 class="card-title">{{ m.name }}</h3>
        <p class="card-desc">{{ m.description || 'No description.' }}</p>

        <div class="card-actions">
          <button class="act-btn" @click="openEdit(m)">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit
          </button>
          <button class="act-btn" :class="m.is_active ? 'warn' : 'ok'" @click="toggle(m)">
            <svg v-if="m.is_active" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            <svg v-else width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            {{ m.is_active ? 'Deactivate' : 'Activate' }}
          </button>
          <button class="act-btn danger" @click="confirmDelete(m)">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- CREATE / EDIT MODAL -->
    <BaseModal v-model="showForm" width="460px">
      <template #header>
        <div class="modal-head">
          <h3>{{ editTarget ? 'Edit Payment Method' : 'New Payment Method' }}</h3>
          <button class="btn-icon-close" @click="showForm = false">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
      </template>

      <div class="form-group">
        <label>Name <span class="req">*</span></label>
        <input v-model="form.name" type="text" placeholder="e.g. GCash, PayMaya, PayPal" />
      </div>

      <div class="form-group">
        <label>Icon <span class="label-hint">emoji, e.g. 📱 💳 🌐 🏦</span></label>
        <input v-model="form.icon" type="text" placeholder="📱" maxlength="4" />
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea v-model="form.description" placeholder="Brief description of this payment method"></textarea>
      </div>

      <div class="form-group checkbox-row">
        <label class="checkbox-label">
          <input type="checkbox" v-model="form.is_active" />
          Active (visible to foundations)
        </label>
      </div>

      <p v-if="formError" class="error-text">{{ formError }}</p>

      <template #footer>
        <button class="btn-cancel" @click="showForm = false">Cancel</button>
        <button class="btn-save" @click="save" :disabled="saving">
          <span v-if="!saving">{{ editTarget ? 'Save Changes' : 'Create' }}</span>
          <Spinner v-else :size="16" color="white" track="rgba(255,255,255,0.35)" />
        </button>
      </template>
    </BaseModal>

    <!-- DELETE CONFIRM -->
    <BaseModal v-model="showDeleteModal" width="420px">
      <template #header>
        <div class="modal-head"><h3>Delete Payment Method</h3></div>
      </template>

      <p class="delete-msg">
        Are you sure you want to delete <strong>{{ deleteTarget?.name }}</strong>?
        Foundations using this method will need to choose another.
      </p>

      <template #footer>
        <button class="btn-cancel" @click="deleteTarget = null">Cancel</button>
        <button class="btn-danger" @click="remove" :disabled="deleting">
          <span v-if="!deleting">Delete</span>
          <Spinner v-else :size="16" color="white" track="rgba(255,255,255,0.35)" />
        </button>
      </template>
    </BaseModal>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import api from "@/services/api"
import PageHeader from '../components/superadmin/pageheader.vue'
import StatCard from '../components/superadmin/statcard.vue'
import EmptyState from '../components/superadmin/emptystate.vue'
import BaseModal from '../components/superadmin/basemodal.vue'
import Spinner from '../components/superadmin/spinner.vue'

const methods     = ref([])
const isLoading   = ref(false)
const showForm    = ref(false)
const editTarget  = ref(null)
const deleteTarget = ref(null)
const saving      = ref(false)
const deleting    = ref(false)
const formError   = ref("")

const form = ref({ name: "", icon: "", description: "", is_active: true })

const activeCount = computed(() => methods.value.filter(m => m.is_active).length)

// BaseModal needs a boolean v-model; deleteTarget doubles as both the
// "which one" data and the "is it open" flag, same pattern as viewFoundation/viewCampaign elsewhere.
const showDeleteModal = computed({
  get: () => !!deleteTarget.value,
  set: (val) => { if (!val) deleteTarget.value = null }
})

async function load() {
  isLoading.value = true
  try {
    const res = await api.get("/admin/payment-methods")
    methods.value = Array.isArray(res.data) ? res.data : []
  } catch (err) {
    console.error(err)
    methods.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

function openCreate() {
  editTarget.value = null
  form.value = { name: "", icon: "", description: "", is_active: true }
  formError.value = ""
  showForm.value = true
}

function openEdit(m) {
  editTarget.value = m
  form.value = { name: m.name, icon: m.icon || "", description: m.description || "", is_active: m.is_active }
  formError.value = ""
  showForm.value = true
}

function confirmDelete(m) { deleteTarget.value = m }

async function save() {
  formError.value = ""
  if (!form.value.name.trim()) {
    formError.value = "Name is required."
    return
  }

  saving.value = true
  try {
    if (editTarget.value) {
      await api.patch(`/admin/payment-methods/${editTarget.value.id}`, form.value)
    } else {
      await api.post("/admin/payment-methods", form.value)
    }
    await load()
    showForm.value = false
  } catch (err) {
    const errors = err.response?.data?.errors
    formError.value = errors
      ? Object.values(errors).flat().join(" · ")
      : err.response?.data?.message || "Failed to save payment method."
  } finally {
    saving.value = false
  }
}

async function toggle(m) {
  try {
    await api.patch(`/admin/payment-methods/${m.id}/toggle`)
    m.is_active = !m.is_active
  } catch (err) {
    console.error(err)
  }
}

async function remove() {
  deleting.value = true
  try {
    await api.delete(`/admin/payment-methods/${deleteTarget.value.id}`)
    methods.value = methods.value.filter(m => m.id !== deleteTarget.value.id)
    deleteTarget.value = null
  } catch (err) {
    console.error(err)
  } finally {
    deleting.value = false
  }
}
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

.btn-add { display: flex; align-items: center; gap: 7px; padding: 10px 18px; background: #0F2D52; color: white; border: none; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-add:hover { background: #1a3f6b; }

.stats-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 24px; }

.empty-icon  { font-size: 2.5rem; }

.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }

.card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(15,45,82,0.06); transition: transform 0.2s, box-shadow 0.2s; }
.card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(15,45,82,0.1); }
.card.inactive { opacity: 0.6; }

.card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.card-icon { font-size: 2rem; line-height: 1; }

.status-pill { font-size: 0.68rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; }
.status-pill.active { background: #f0fdf4; color: #059669; }
.status-pill.paused { background: #f1f5f9; color: #64748b; }

.card-title { font-size: 1rem; font-weight: 800; color: #0F2D52; margin: 0 0 6px; }
.card-desc  { font-size: 0.8rem; color: #64748b; line-height: 1.5; margin: 0 0 16px; min-height: 36px; }

.card-actions { display: flex; gap: 6px; flex-wrap: wrap; }
.act-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 5px; padding: 8px; border: 1px solid #e2e8f0; background: white; color: #475569; border-radius: 8px; font-size: 0.76rem; font-weight: 600; cursor: pointer; transition: all 0.15s; white-space: nowrap; }
.act-btn:hover { background: #f8fafc; }
.act-btn.warn:hover { background: #fefce8; color: #ca8a04; border-color: #fde68a; }
.act-btn.ok:hover   { background: #f0fdf4; color: #059669; border-color: #bbf7d0; }
.act-btn.danger { flex: 0 0 auto; width: 34px; color: #dc2626; }
.act-btn.danger:hover { background: #fef2f2; border-color: #fecaca; }

/* Modal content (structure/overlay now handled by BaseModal) */
.modal-head { display: flex; justify-content: space-between; align-items: center; padding: 28px 28px 20px; }
.modal-head h3 { font-size: 1.05rem; color: #0F2D52; margin: 0; }
.btn-icon-close { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: transparent; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; flex-shrink: 0; }
.btn-icon-close:hover { background: #f1f5f9; }

.delete-msg { font-size: 0.88rem; color: #475569; margin: 0; }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
.req { color: #dc2626; }
.label-hint { font-size: 0.75rem; font-weight: 400; color: #94a3b8; margin-left: 6px; }

.form-group input, .form-group textarea {
  width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px;
  font-size: 0.87rem; outline: none; box-sizing: border-box; font-family: inherit; transition: border-color 0.2s;
}
.form-group input:focus, .form-group textarea:focus { border-color: #0F2D52; box-shadow: 0 0 0 3px rgba(15,45,82,0.08); }
.form-group textarea { height: 70px; resize: none; }

.checkbox-row { display: flex; align-items: center; }
.checkbox-label { display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; color: #475569; font-size: 0.85rem; }
.checkbox-label input { width: auto; }

.error-text { font-size: 0.82rem; color: #cc3333; background: #fff5f5; border: 1px solid #ffd0d0; border-radius: 8px; padding: 9px 13px; margin-bottom: 4px; }

.btn-cancel { padding: 9px 18px; border: 1px solid #e2e8f0; background: white; color: #64748b; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
.btn-save   { padding: 9px 20px; border: none; background: #0F2D52; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; min-width: 100px; }
.btn-save:hover:not(:disabled) { background: #1a3f6b; }
.btn-save:disabled { opacity: 0.65; cursor: not-allowed; }
.btn-danger { padding: 9px 20px; border: none; background: #dc2626; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; min-width: 80px; }
.btn-danger:disabled { opacity: 0.65; cursor: not-allowed; }
</style>