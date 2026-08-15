<template>
  <header class="topbar">
    <div class="topbar-left">
      <div class="page-info">
        <h2 class="page-title">{{ pageTitle }}</h2>
        <nav class="breadcrumb">
          <span>Admin</span>
          <span class="sep">›</span>
          <span class="active">{{ pageTitle }}</span>
        </nav>
      </div>
    </div>

    <div class="topbar-right">
      <div v-if="isSearchable" class="search-box">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input v-model="searchQuery" type="text" :placeholder="searchPlaceholder" />
        <button v-if="searchQuery" class="clear-btn" title="Clear" @click="searchQuery = ''">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <div class="notif-wrap" ref="notifWrap">
        <button class="icon-btn" title="Notifications" @click="toggleNotifMenu">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
        </button>

        <div v-if="notifMenuOpen" class="notif-menu">
          <div class="notif-menu-header">
            <strong>Notifications</strong>
            <button v-if="notifications.length" class="mark-all-btn" @click="markAllSeen">
              Mark all read
            </button>
          </div>

          <div v-if="notifLoading" class="notif-empty">Loading…</div>

          <div v-else-if="!notifications.length" class="notif-empty">
            No notifications yet
          </div>

          <div v-else class="notif-list">
            <div
              v-for="n in notifications"
              :key="n.id"
              class="notif-item"
              :class="{ unread: !n.read_at }"
              @click="openNotification(n)"
            >
              <div class="notif-dot" v-if="!n.read_at"></div>
              <div class="notif-content">
                <strong>{{ n.title }}</strong>
                <p v-if="n.message">{{ n.message }}</p>
                <span class="notif-time">{{ formatTime(n.created_at) }}</span>
              </div>
              <button class="notif-delete" title="Delete" @click.stop="deleteOne(n.id)">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
          </div>

          <div v-if="notifications.length" class="notif-menu-footer">
            <button class="clear-all-btn" @click="clearAll">Clear all</button>
          </div>
        </div>
      </div>

      <div class="divider"></div>

      <div class="profile-wrap" ref="profileWrap">
        <div class="profile" @click="toggleMenu">
          <div class="avatar">{{ initials }}</div>
          <div class="profile-info">
            <strong>{{ userName }}</strong>
            <small>System Administrator</small>
          </div>
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#94a3b8">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </div>

        <div v-if="menuOpen" class="profile-menu">
          <router-link to="/admin/setting" class="profile-menu-item" @click="menuOpen = false">
            Settings
          </router-link>
          <button class="profile-menu-item danger" @click="logout">
            Logout
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useSearch } from '../../composable/usesearch'

const route = useRoute()
const router = useRouter()

const titleMap = {
  '/admin/dashboard': 'Dashboard',
  '/admin/approval': 'Foundation Approvals',
  '/admin/foundation': 'Foundations',
  '/admin/campaign': 'Campaigns',
  '/admin/category': 'Categories',
  '/admin/payment-method': 'Payment Methods',
  '/admin/user': 'Users',
  '/admin/report': 'Reports',
  '/admin/setting': 'Settings',
}

const pageTitle = computed(() => titleMap[route.path] || 'Dashboard')

/* ══════════════════════════════
   SHARED SEARCH
   Only shown/active on the 4 pages that actually filter by it.
   Cleared automatically whenever the admin navigates to a
   different page, so a leftover query never silently filters
   a page the admin didn't mean to search.
══════════════════════════════ */
const { searchQuery } = useSearch()

const searchPlaceholders = {
  '/admin/approval':   'Search foundations...',
  '/admin/foundation': 'Search foundations...',
  '/admin/campaign':   'Search campaigns...',
  '/admin/user':       'Search users...',
}

const isSearchable = computed(() => route.path in searchPlaceholders)
const searchPlaceholder = computed(() => searchPlaceholders[route.path] || 'Search...')

watch(() => route.path, () => {
  searchQuery.value = ''
})

/* ══════════════════════════════
   NOTIFICATIONS
══════════════════════════════ */
const notifMenuOpen = ref(false)
const notifWrap = ref(null)
const notifications = ref([])
const unreadCount = ref(0)
const notifLoading = ref(false)
let pollTimer = null

async function fetchNotifications() {
  notifLoading.value = true
  try {
    const { data } = await api.get('/admin/notifications')
    notifications.value = data.notifications ?? []
    unreadCount.value = data.unread_count ?? 0
  } catch (err) {
    console.error('Failed to load notifications:', err)
  } finally {
    notifLoading.value = false
  }
}

async function fetchUnreadCount() {
  try {
    const { data } = await api.get('/admin/notifications/count')
    unreadCount.value = data.count ?? 0
  } catch (err) {
    console.error('Failed to load notification count:', err)
  }
}

function toggleNotifMenu() {
  notifMenuOpen.value = !notifMenuOpen.value
  if (notifMenuOpen.value) fetchNotifications()
}

async function markAllSeen() {
  try {
    await api.post('/admin/notifications/seen')
    notifications.value = notifications.value.map(n => ({ ...n, read_at: n.read_at || new Date().toISOString() }))
    unreadCount.value = 0
  } catch (err) {
    console.error('Failed to mark all as read:', err)
  }
}

async function openNotification(n) {
  if (!n.read_at) {
    try {
      await api.post(`/admin/notifications/${n.id}/read`)
      n.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (err) {
      console.error('Failed to mark notification as read:', err)
    }
  }

  // Route superadmin to the relevant page based on notification type
  if (n.type === 'foundation_registered' || n.type === 'foundation_resubmitted') {
    router.push('/admin/approval')
  }
  notifMenuOpen.value = false
}

async function deleteOne(id) {
  try {
    await api.delete(`/admin/notifications/${id}`)
    const wasUnread = notifications.value.find(n => n.id === id && !n.read_at)
    notifications.value = notifications.value.filter(n => n.id !== id)
    if (wasUnread) unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch (err) {
    console.error('Failed to delete notification:', err)
  }
}

async function clearAll() {
  try {
    await api.delete('/admin/notifications')
    notifications.value = []
    unreadCount.value = 0
  } catch (err) {
    console.error('Failed to clear notifications:', err)
  }
}

function formatTime(dateStr) {
  const date = new Date(dateStr)
  const diffMs = Date.now() - date.getTime()
  const diffMin = Math.floor(diffMs / 60000)
  if (diffMin < 1) return 'Just now'
  if (diffMin < 60) return `${diffMin}m ago`
  const diffHr = Math.floor(diffMin / 60)
  if (diffHr < 24) return `${diffHr}h ago`
  const diffDay = Math.floor(diffHr / 24)
  if (diffDay < 7) return `${diffDay}d ago`
  return date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
}

function handleClickOutsideNotif(e) {
  if (notifWrap.value && !notifWrap.value.contains(e.target)) {
    notifMenuOpen.value = false
  }
}

/* ══════════════════════════════
   PROFILE
══════════════════════════════ */
const menuOpen = ref(false)
const profileWrap = ref(null)
const user = ref(null)

function loadUserFromSession() {
  try {
    user.value = JSON.parse(sessionStorage.getItem('user') || 'null')
  } catch {
    user.value = null
  }
}

const userName = computed(() => {
  if (!user.value) return 'Super Admin'
  const full = [user.value.first_name, user.value.last_name].filter(Boolean).join(' ')
  return full || user.value.name || 'Super Admin'
})

const initials = computed(() => {
  if (!user.value) return 'SA'
  const first = user.value.first_name || user.value.name || ''
  const last = user.value.last_name || ''
  return [first, last].filter(Boolean).map(w => w[0]).join('').toUpperCase().slice(0, 2) || 'SA'
})

function toggleMenu() {
  menuOpen.value = !menuOpen.value
}

function handleClickOutside(e) {
  if (profileWrap.value && !profileWrap.value.contains(e.target)) {
    menuOpen.value = false
  }
}

async function logout() {
  try {
    await api.post('/logout')
  } catch (err) {
    console.error(err)
  } finally {
    sessionStorage.removeItem('token')
    sessionStorage.removeItem('user')
    router.push('/login')
  }
}

onMounted(() => {
  loadUserFromSession()
  fetchUnreadCount()
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('click', handleClickOutsideNotif)
  // Refresh the unread badge every 30s so new notifications show up
  // without the admin having to refresh the page.
  pollTimer = setInterval(fetchUnreadCount, 30000)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('click', handleClickOutsideNotif)
  if (pollTimer) clearInterval(pollTimer)
})
</script>

<style scoped>
.topbar {
  height: 70px;
  background: white;
  border-bottom: 1px solid #e8edf5;
  padding: 0 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 10;
  box-shadow: 0 1px 4px rgba(15, 45, 82, 0.06);
}

.page-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #0F2D52;
  margin: 0;
}

.breadcrumb {
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 2px;
}

.breadcrumb .sep { margin: 0 5px; }
.breadcrumb .active { color: #D4AF37; font-weight: 500; }

.topbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f1f5f9;
  border: 1px solid transparent;
  border-radius: 10px;
  padding: 8px 14px;
  transition: all 0.2s;
  color: #94a3b8;
}

.search-box:focus-within {
  border-color: #D4AF37;
  background: white;
  box-shadow: 0 0 0 3px rgba(212,175,55,0.12);
}

.search-box input {
  border: none;
  background: transparent;
  outline: none;
  font-size: 0.85rem;
  width: 200px;
  color: #0F2D52;
}

.search-box input::placeholder { color: #94a3b8; }

.clear-btn {
  border: none;
  background: none;
  color: #94a3b8;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2px;
  border-radius: 4px;
  flex-shrink: 0;
  transition: color 0.15s, background 0.15s;
}
.clear-btn:hover { color: #dc2626; background: #fef2f2; }

.icon-btn {
  position: relative;
  width: 38px;
  height: 38px;
  border: none;
  background: #f1f5f9;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  transition: all 0.2s;
}

.icon-btn:hover:not(:disabled) { background: #e2e8f0; color: #0F2D52; }
.icon-btn:disabled { cursor: not-allowed; opacity: 0.5; }

.notif-wrap { position: relative; }

.notif-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 17px;
  height: 17px;
  padding: 0 4px;
  background: #dc2626;
  color: white;
  font-size: 0.62rem;
  font-weight: 700;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid white;
}

.notif-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 340px;
  max-height: 440px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(15,45,82,0.15);
  z-index: 60;
  display: flex;
  flex-direction: column;
}

.notif-menu-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
}

.notif-menu-header strong {
  font-size: 0.9rem;
  color: #0F2D52;
}

.mark-all-btn {
  border: none;
  background: none;
  color: #D4AF37;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}
.mark-all-btn:hover { text-decoration: underline; }

.notif-empty {
  padding: 28px 16px;
  text-align: center;
  color: #94a3b8;
  font-size: 0.83rem;
}

.notif-list {
  overflow-y: auto;
  max-height: 320px;
}

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 12px 16px;
  border-bottom: 1px solid #f8fafc;
  cursor: pointer;
  transition: background 0.15s;
  position: relative;
}

.notif-item:hover { background: #f8fafc; }
.notif-item.unread { background: #fefce8; }
.notif-item.unread:hover { background: #fef9e0; }

.notif-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #D4AF37;
  margin-top: 6px;
  flex-shrink: 0;
}

.notif-content { flex: 1; min-width: 0; }
.notif-content strong {
  display: block;
  font-size: 0.82rem;
  color: #0F2D52;
  margin-bottom: 2px;
}
.notif-content p {
  font-size: 0.78rem;
  color: #64748b;
  margin: 0 0 4px;
  line-height: 1.4;
}
.notif-time {
  font-size: 0.7rem;
  color: #94a3b8;
}

.notif-delete {
  border: none;
  background: none;
  color: #cbd5e1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
  border-radius: 4px;
  flex-shrink: 0;
  transition: color 0.15s, background 0.15s;
}
.notif-delete:hover { color: #dc2626; background: #fef2f2; }

.notif-menu-footer {
  padding: 10px 16px;
  border-top: 1px solid #f1f5f9;
  text-align: center;
}

.clear-all-btn {
  border: none;
  background: none;
  color: #94a3b8;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  padding: 4px;
}
.clear-all-btn:hover { color: #dc2626; }

.divider {
  width: 1px;
  height: 32px;
  background: #e8edf5;
  margin: 0 6px;
}

.profile-wrap { position: relative; }

.profile {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  padding: 6px 12px 6px 6px;
  border-radius: 12px;
  transition: background 0.2s;
}

.profile:hover { background: #f1f5f9; }

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #D4AF37, #f0cc5a);
  color: #0F2D52;
  display: flex;
  justify-content: center;
  align-items: center;
  font-weight: 800;
  font-size: 0.85rem;
  box-shadow: 0 2px 8px rgba(212,175,55,0.35);
}

.profile-info strong {
  display: block;
  font-size: 0.85rem;
  color: #0F2D52;
}

.profile-info small {
  display: block;
  font-size: 0.72rem;
  color: #94a3b8;
}

.profile-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 180px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(15,45,82,0.15);
  padding: 6px;
  z-index: 60;
}

.profile-menu-item {
  display: block;
  width: 100%;
  padding: 9px 10px;
  border: none;
  background: none;
  border-radius: 8px;
  font-size: 0.83rem;
  font-weight: 500;
  color: #334155;
  text-decoration: none;
  cursor: pointer;
  text-align: left;
  transition: background 0.15s;
  box-sizing: border-box;
}

.profile-menu-item:hover { background: #f1f5f9; color: #0F2D52; }
.profile-menu-item.danger { color: #dc2626; }
.profile-menu-item.danger:hover { background: #fef2f2; }
</style>