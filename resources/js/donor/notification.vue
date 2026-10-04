<template>
  <div class="page">
    <div class="container">

      <div class="page-head">
        <div>
          <div class="title-row">
            <h1>Notifications</h1>
            <span v-if="unreadCount > 0" class="unread-badge">{{ unreadCount }}</span>
          </div>
          <p>Stay updated on your donations</p>
        </div>
        <button v-if="notifications.length" class="btn-clear-all" @click="confirmClearAll = true">
          Clear all
        </button>
      </div>

      <div v-if="isLoading" class="loading-wrap"><Spinner /></div>

      <EmptyState v-else-if="notifications.length === 0" icon="🔔" message="No notifications yet." />

      <div v-else class="notif-list">
        <div
          class="notif-card"
          v-for="n in notifications" :key="n.id"
          :class="{ unread: !n.read, clickable: !!n.link }"
        >
          <div class="notif-clickzone" @click="openNotification(n)">
            <div class="notif-icon" :class="n.type">
              <span>{{ n.type === 'success' ? '✅' : n.type === 'warning' ? '⚠️' : '📢' }}</span>
            </div>
            <div class="notif-body">
              <div class="notif-title">{{ n.title }}</div>
              <div class="notif-message">{{ n.message }}</div>
              <div class="notif-date">{{ formatDateTime(n.date) }}</div>
            </div>
            <div class="notif-right">
              <span v-if="!n.read" class="unread-dot"></span>
            </div>
          </div>

          <button class="btn-delete" @click="deleteNotification(n)" title="Delete">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
      </div>

    </div>

    <!-- CLEAR ALL CONFIRMATION -->
    <div v-if="confirmClearAll" class="modal-overlay" @click.self="confirmClearAll = false">
      <div class="confirm-modal">
        <h3>Clear all notifications?</h3>
        <p>This will permanently remove all your notifications. This can't be undone.</p>
        <div class="confirm-actions">
          <button class="btn-cancel" @click="confirmClearAll = false">Cancel</button>
          <button class="btn-confirm-delete" @click="clearAll" :disabled="clearing">
            {{ clearing ? 'Clearing...' : 'Clear all' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import Spinner from '../components/donor/spinner.vue'
import EmptyState from '../components/donor/emptystate.vue'
import { formatDateTime } from '../composable/donor.js'

const router = useRouter()

const notifications  = ref([])
const isLoading       = ref(false)
const confirmClearAll = ref(false)
const clearing         = ref(false)

const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)

async function load() {
  isLoading.value = true
  try {
    const res = await api.get('/donor/notifications')
    notifications.value = Array.isArray(res.data) ? res.data : []
  } catch (err) {
    console.error(err)
    notifications.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

async function openNotification(n) {
  if (!n.read) {
    n.read = true
    try {
      await api.post(`/donor/notifications/${n.id}/read`)
    } catch (err) {
      console.error(err)
    }
  }
  if (n.link) router.push(n.link)
}

async function deleteNotification(n) {
  const prev = notifications.value
  notifications.value = notifications.value.filter(x => x.id !== n.id)

  try {
    await api.delete(`/donor/notifications/${n.id}`)
  } catch (err) {
    console.error(err)
    notifications.value = prev
  }
}

async function clearAll() {
  clearing.value = true
  try {
    await api.delete('/donor/notifications')
    notifications.value = []
    confirmClearAll.value = false
  } catch (err) {
    console.error(err)
  } finally {
    clearing.value = false
  }
}
</script>

<style scoped>
.page { background: #c8f0e0; min-height: 100vh; padding-bottom: 40px; }
.container { max-width: 800px; margin: 0 auto; padding: 32px 24px; }

.page-head { margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
.page-head h1 { font-size: 1.5rem; font-weight: 900; color: #0e5c36; margin: 0; }
.page-head p  { font-size: 0.85rem; color: #475569; margin: 4px 0 0; }

.title-row { display: flex; align-items: center; gap: 10px; }
.unread-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 22px; height: 22px; padding: 0 6px;
  background: #1a8a52; color: white;
  font-size: 0.72rem; font-weight: 800;
  border-radius: 999px;
}

.btn-clear-all {
  padding: 9px 16px; border: 1.5px solid #e2e8f0; background: white; color: #64748b;
  border-radius: 10px; font-size: 0.82rem; font-weight: 700; cursor: pointer;
  white-space: nowrap; transition: all 0.15s;
}
.btn-clear-all:hover { border-color: #dc2626; color: #dc2626; background: #fef2f2; }

.loading-wrap { display: flex; justify-content: center; padding: 60px; background: white; border-radius: 16px; }

.notif-list { display: flex; flex-direction: column; gap: 10px; }

.notif-card {
  background: white; border-radius: 14px; display: flex; align-items: stretch;
  box-shadow: 0 2px 8px rgba(0,80,40,0.06); border-left: 4px solid transparent;
  transition: transform 0.15s;
}
.notif-card.unread { border-left-color: #1a8a52; background: #f0fdf4; }

.notif-clickzone { flex: 1; display: flex; align-items: flex-start; gap: 14px; padding: 16px 12px 16px 20px; min-width: 0; }
.notif-card.clickable .notif-clickzone { cursor: pointer; }

.notif-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.notif-icon.success { background: #f0fdf4; }
.notif-icon.warning { background: #fefce8; }
.notif-icon.info    { background: #eff6ff; }

.notif-body { flex: 1; min-width: 0; }
.notif-title   { font-size: 0.92rem; font-weight: 800; color: #0F2D52; margin-bottom: 4px; }
.notif-message { font-size: 0.83rem; color: #475569; line-height: 1.5; margin-bottom: 6px; }
.notif-date    { font-size: 0.73rem; color: #94a3b8; }

.notif-right { flex-shrink: 0; }
.unread-dot  { width: 10px; height: 10px; border-radius: 50%; background: #1a8a52; display: block; margin-top: 4px; }

.btn-delete {
  flex-shrink: 0; width: 40px; display: flex; align-items: center; justify-content: center;
  border: none; background: transparent; color: #cbd5e1; cursor: pointer;
  transition: color 0.15s, background 0.15s; border-radius: 0 14px 14px 0;
}
.btn-delete:hover { color: #dc2626; background: #fef2f2; }

/* CLEAR ALL MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; z-index: 200; padding: 24px; }
.confirm-modal { background: white; border-radius: 18px; padding: 26px; width: 360px; max-width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.confirm-modal h3 { font-size: 1.02rem; font-weight: 800; color: #0F2D52; margin: 0 0 8px; }
.confirm-modal p  { font-size: 0.85rem; color: #64748b; line-height: 1.6; margin: 0 0 20px; }

.confirm-actions { display: flex; gap: 10px; }
.btn-cancel { flex: 1; padding: 11px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; border-radius: 10px; font-size: 0.86rem; font-weight: 700; cursor: pointer; }
.btn-cancel:hover { background: #f8fafc; }
.btn-confirm-delete { flex: 1; padding: 11px; border: none; background: #dc2626; color: white; border-radius: 10px; font-size: 0.86rem; font-weight: 700; cursor: pointer; }
.btn-confirm-delete:hover:not(:disabled) { background: #b91c1c; }
.btn-confirm-delete:disabled { opacity: 0.6; cursor: default; }
</style>