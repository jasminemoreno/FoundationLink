<template>
  <div class="page">

    <div class="page-header">
      <div class="left">
        <div class="header-icon">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
          </svg>
        </div>
        <div>
          <h2>Platform Settings</h2>
          <p>Manage FoundationLink platform configuration</p>
        </div>
      </div>
      <button class="btn-save" :disabled="saving || activeTab === 'maintenance'" @click="saveSettings">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
          <polyline points="17 21 17 13 7 13"/>
          <polyline points="7 3 7 8 15 8"/>
        </svg>
        {{ saving ? 'Saving…' : 'Save Changes' }}
      </button>
    </div>

    <!-- Toast -->
    <transition name="fade">
      <div v-if="toast.show" class="toast" :class="toast.type">
        <svg v-if="toast.type === 'success'" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
        <svg v-else width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ toast.message }}
      </div>
    </transition>

    <div v-if="loading" class="loading-state">
      <Spinner :size="22" />
      <span>Loading settings…</span>
    </div>

    <div v-else class="settings-layout">

      <!-- Sidebar -->
      <div class="settings-nav">
        <p class="nav-label">Configuration</p>
        <button
          v-for="tab in tabs" :key="tab.key"
          class="nav-item" :class="{ active: activeTab === tab.key }"
          @click="activeTab = tab.key"
        >
          <span class="nav-icon" v-html="tab.icon"></span>
          <div class="nav-text">
            <span class="nav-title">{{ tab.label }}</span>
            <span class="nav-sub">{{ tab.sub }}</span>
          </div>
        </button>
      </div>

      <!-- Content -->
      <div class="settings-content">

        <!-- General -->
        <div v-if="activeTab === 'general'" class="tab-pane">
          <div class="tab-header">
            <div class="tab-title-group">
              <h3>General Information</h3>
              <p class="tab-desc">Platform identity displayed across FoundationLink — visible to foundation admins and donors.</p>
            </div>
          </div>

          <div class="form-section">
            <p class="section-label">Platform Identity</p>

            <div class="form-row">
              <div class="form-group">
                <label>Platform Name</label>
                <input v-model="s.platform_name" type="text" placeholder="e.g. FoundationLink" />
                <span class="field-hint">Shown in email notifications and system headers.</span>
              </div>

              <div class="form-group">
                <label>
                  Administrator Email
                  <span class="readonly-badge">Read Only</span>
                </label>
                <div class="readonly-field">
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                  </svg>
                  <span>{{ s.admin_email }}</span>
                </div>
                <span class="field-hint">
                  <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                  To update this email, change it directly in the database.
                </span>
              </div>
            </div>

            <div class="form-group">
              <label>Platform Description</label>
              <textarea v-model="s.description" rows="4" placeholder="Briefly describe the purpose of this donation platform…"></textarea>
              <span class="field-hint">Used in outgoing emails and platform overview sections.</span>
            </div>
          </div>
        </div>

        <!-- Maintenance -->
        <div v-if="activeTab === 'maintenance'" class="tab-pane">
          <div class="tab-header">
            <div class="tab-title-group">
              <h3>System Maintenance</h3>
              <p class="tab-desc">Administrative tools for managing platform health and configuration.</p>
            </div>
          </div>

          <div class="form-section">
            <p class="section-label">Maintenance Actions</p>
            <div class="action-cards">
              <div class="action-card">
                <div class="action-icon cache">
                  <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/>
                    <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
                  </svg>
                </div>
                <div class="action-info">
                  <strong>Clear Application Cache</strong>
                  <p>Flush all cached data and force the platform to rebuild from fresh sources. Use this after configuration changes.</p>
                </div>
                <button class="btn-action" @click="confirmAction('cache')">Clear Cache</button>
              </div>

              <div class="action-card danger">
                <div class="action-icon reset">
                  <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                  </svg>
                </div>
                <div class="action-info">
                  <strong>Reset to Defaults</strong>
                  <p>Restore all platform settings to their original default values. This action cannot be undone.</p>
                </div>
                <button class="btn-action danger" @click="confirmAction('reset')">Reset</button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Confirm Modal -->
    <BaseModal v-model="confirm.show" width="420px">
      <template #header>
        <div class="modal-icon-wrap">
          <div class="modal-icon" :class="confirm.action">
            <svg v-if="confirm.action === 'cache'" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/>
              <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
            </svg>
            <svg v-else width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
              <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
          </div>
        </div>
      </template>

      <h4>{{ confirm.title }}</h4>
      <p class="confirm-msg">{{ confirm.message }}</p>

      <template #footer>
        <button class="btn-cancel" @click="confirm.show = false">Cancel</button>
        <button class="btn-confirm" :class="confirm.action" :disabled="confirm.loading" @click="runConfirm">
          {{ confirm.loading ? 'Processing…' : 'Confirm' }}
        </button>
      </template>
    </BaseModal>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'
import BaseModal from '../components/superadmin/basemodal.vue'
import Spinner from '../components/superadmin/spinner.vue'

const activeTab = ref('general')
const loading   = ref(true)
const saving    = ref(false)

const toast = reactive({ show: false, type: 'success', message: '' })
let toastTimer = null

function showToast(message, type = 'success') {
  clearTimeout(toastTimer)
  toast.message = message
  toast.type    = type
  toast.show    = true
  toastTimer    = setTimeout(() => { toast.show = false }, 3500)
}

/* ── Settings state ── */
const s = reactive({
  platform_name: '',
  admin_email: '',
  description: '',
})

/* ── Fetch ── */
async function fetchSettings() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/settings')
    Object.assign(s, data)
  } catch {
    showToast('Failed to load settings.', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(fetchSettings)

/* ── Save (only sends editable fields) ── */
async function saveSettings() {
  saving.value = true
  try {
    await api.put('/admin/settings', {
      platform_name: s.platform_name,
      description: s.description,
    })
    showToast('Settings saved successfully.')
  } catch (err) {
    const msg = err?.response?.data?.message ?? 'Failed to save settings.'
    showToast(msg, 'error')
  } finally {
    saving.value = false
  }
}

/* ── Confirm modal ── */
const confirm = reactive({ show: false, loading: false, action: '', title: '', message: '' })

function confirmAction(action) {
  const map = {
    cache: {
      title: 'Clear Application Cache',
      message: 'This will flush all cached data. The platform may respond slightly slower while cache rebuilds. Proceed?'
    },
    reset: {
      title: 'Reset to Default Settings',
      message: 'This will restore the platform name and description to their original default values. Your current settings will be lost.'
    },
  }
  Object.assign(confirm, { show: true, loading: false, action, ...map[action] })
}

async function runConfirm() {
  confirm.loading = true
  try {
    if (confirm.action === 'cache') {
      await api.post('/admin/settings/clear-cache')
      showToast('Application cache cleared successfully.')
    } else if (confirm.action === 'reset') {
      await api.post('/admin/settings/reset')
      await fetchSettings()
      showToast('Settings restored to defaults.')
    }
  } catch {
    showToast('Action failed. Please try again.', 'error')
  } finally {
    confirm.loading = false
    confirm.show    = false
  }
}

/* ── Tabs ── */
const tabs = [
  {
    key: 'general',
    label: 'General',
    sub: 'Platform identity',
    icon: `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>`
  },
  {
    key: 'maintenance',
    label: 'Maintenance',
    sub: 'System tools',
    icon: `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>`
  },
]
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

/* Header */
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.left { display: flex; align-items: center; gap: 14px; }
.header-icon { width: 42px; height: 42px; background: #0F2D52; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; }
.page-header h2 { font-size: 1.1rem; color: #0F2D52; margin: 0 0 3px; font-weight: 700; }
.page-header p  { font-size: 0.8rem; color: #94a3b8; margin: 0; }

.btn-save { display: flex; align-items: center; gap: 7px; padding: 10px 20px; background: #0F2D52; color: white; border: none; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-save:hover:not(:disabled) { background: #1a3f6b; }
.btn-save:disabled { opacity: 0.45; cursor: not-allowed; }

/* Toast */
.toast { display: flex; align-items: center; gap: 9px; padding: 12px 18px; border-radius: 12px; font-size: 0.87rem; font-weight: 600; margin-bottom: 20px; }
.toast.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #059669; }
.toast.error   { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Loading */
.loading-state { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 80px 0; color: #94a3b8; font-size: 0.9rem; }

/* Layout */
.settings-layout { display: grid; grid-template-columns: 230px 1fr; gap: 20px; }

/* Sidebar */
.settings-nav { background: white; border-radius: 16px; padding: 14px 10px; box-shadow: 0 1px 4px rgba(15,45,82,0.06); height: fit-content; }
.nav-label { font-size: 0.7rem; font-weight: 700; color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.08em; padding: 0 10px; margin: 0 0 8px; }
.nav-item { display: flex; align-items: center; gap: 12px; width: 100%; padding: 11px 12px; border: none; background: transparent; color: #64748b; font-size: 0.87rem; border-radius: 10px; cursor: pointer; transition: all 0.2s; text-align: left; }
.nav-item:hover { background: #f8fafc; color: #0F2D52; }
.nav-item.active { background: #EFF4FB; color: #0F2D52; }
.nav-icon { display: flex; align-items: center; flex-shrink: 0; }
.nav-text { display: flex; flex-direction: column; gap: 1px; }
.nav-title { font-size: 0.87rem; font-weight: 600; line-height: 1; }
.nav-sub { font-size: 0.75rem; color: #94a3b8; font-weight: 400; }
.nav-item.active .nav-sub { color: #7a9cc4; }

/* Content */
.settings-content { background: white; border-radius: 16px; padding: 28px; box-shadow: 0 1px 4px rgba(15,45,82,0.06); }
.tab-header { margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9; }
.tab-title-group h3 { font-size: 1rem; color: #0F2D52; margin: 0 0 5px; font-weight: 700; }
.tab-desc { font-size: 0.82rem; color: #94a3b8; margin: 0; line-height: 1.5; }

.form-section { margin-bottom: 28px; }
.section-label { font-size: 0.72rem; font-weight: 700; color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 16px; }

/* Forms */
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
.form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
.form-group:last-child { margin-bottom: 0; }
.form-group label { font-size: 0.82rem; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px; }
.form-group input, .form-group textarea {
  padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px;
  font-size: 0.87rem; outline: none; background: #fafafa; color: #0F2D52;
  transition: all 0.2s; font-family: inherit;
}
.form-group input:focus, .form-group textarea:focus {
  border-color: #0F2D52; background: white;
  box-shadow: 0 0 0 3px rgba(15,45,82,0.08);
}
.form-group textarea { resize: none; }
.field-hint { font-size: 0.76rem; color: #94a3b8; display: flex; align-items: center; gap: 4px; }

/* Read-only field */
.readonly-badge { font-size: 0.68rem; font-weight: 600; background: #f1f5f9; color: #94a3b8; padding: 2px 7px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em; }
.readonly-field { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; color: #64748b; font-size: 0.87rem; cursor: not-allowed; }
.readonly-field svg { color: #94a3b8; flex-shrink: 0; }
.readonly-field span { color: #475569; font-weight: 500; }

/* Action Cards */
.action-cards { display: flex; flex-direction: column; gap: 14px; }
.action-card { display: flex; align-items: center; gap: 16px; padding: 18px 20px; border: 1px solid #e2e8f0; border-radius: 14px; background: #fafafa; }
.action-card.danger { border-color: #fecaca; background: #fff8f8; }
.action-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.action-icon.cache { background: #EFF4FB; color: #0F2D52; }
.action-icon.reset { background: #fef2f2; color: #dc2626; }
.action-info { flex: 1; }
.action-info strong { display: block; font-size: 0.88rem; color: #0F2D52; margin-bottom: 4px; font-weight: 600; }
.action-card.danger .action-info strong { color: #dc2626; }
.action-info p { font-size: 0.79rem; color: #94a3b8; margin: 0; line-height: 1.5; }
.btn-action { padding: 8px 18px; border-radius: 9px; font-size: 0.84rem; font-weight: 600; cursor: pointer; transition: all 0.2s; border: 1px solid #e2e8f0; background: white; color: #0F2D52; white-space: nowrap; }
.btn-action:hover { background: #EFF4FB; border-color: #0F2D52; }
.btn-action.danger { border-color: #fca5a5; background: white; color: #dc2626; }
.btn-action.danger:hover { background: #fef2f2; }

/* Confirm modal content (structure/overlay now handled by BaseModal) */
.modal-icon-wrap { padding: 28px 28px 0; }
.modal-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.modal-icon.cache { background: #EFF4FB; color: #0F2D52; }
.modal-icon.reset { background: #fef2f2; color: #dc2626; }
h4 { font-size: 1rem; color: #0F2D52; margin: 0 0 8px; font-weight: 700; }
.confirm-msg { font-size: 0.85rem; color: #64748b; margin: 0; line-height: 1.6; }
.btn-cancel { padding: 9px 18px; border: 1px solid #e2e8f0; background: white; color: #64748b; font-size: 0.87rem; font-weight: 600; border-radius: 9px; cursor: pointer; }
.btn-cancel:hover { background: #f8fafc; }
.btn-confirm { padding: 9px 18px; border: none; font-size: 0.87rem; font-weight: 600; border-radius: 9px; cursor: pointer; transition: all 0.2s; }
.btn-confirm.cache { background: #0F2D52; color: white; }
.btn-confirm.cache:hover:not(:disabled) { background: #1a3f6b; }
.btn-confirm.reset { background: #dc2626; color: white; }
.btn-confirm.reset:hover:not(:disabled) { background: #b91c1c; }
.btn-confirm:disabled { opacity: 0.6; cursor: not-allowed; }
</style>