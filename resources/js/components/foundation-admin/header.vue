<template>
  <header class="app-header">
    <h1 class="header-title">{{ pageTitle }}</h1>

    <div class="header-right">
      <div class="org-info">
        <span class="org-name">{{ foundationName || 'Foundation' }}</span>
      </div>

      <!-- NOTIFICATIONS -->
      <div class="notif-wrap" ref="notifWrap">
        <button class="notif-btn" @click="toggleNotifs">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
        </button>

        <div v-if="notifOpen" class="notif-menu">
          <div class="notif-menu-head">
            <span>Notifications</span>
            <button v-if="unreadCount > 0" class="mark-all-btn" @click="markAllRead">Mark all read</button>
          </div>

          <div v-if="loadingNotifs" class="notif-empty">Loading...</div>
          <div v-else-if="notifications.length === 0" class="notif-empty">No notifications yet</div>

          <div v-else class="notif-list">
            <div
              v-for="n in notifications"
              :key="n.id"
              class="notif-item"
              :class="{ unread: !n.read_at }"
              @click="onNotifClick(n)"
            >
              <div class="notif-item-row">
                <div class="notif-title">{{ n.title }}</div>
                <button class="notif-delete" @click.stop="onDeleteNotif(n)" title="Delete">
                  <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                </button>
              </div>
              <div v-if="n.message" class="notif-message">{{ n.message }}</div>
              <div class="notif-time">{{ formatTime(n.created_at) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- AVATAR / PROFILE MENU -->
      <div class="avatar-wrap" ref="avatarWrap">
        <button class="avatar" @click="toggleMenu">
          <img v-if="avatarUrl" :src="avatarUrl" alt="User Avatar" />
          <span v-else class="avatar-initials-sm">{{ initials }}</span>
        </button>

        <div v-if="menuOpen" class="avatar-menu">
          <div class="menu-user">
            <div class="menu-avatar">
              <img v-if="avatarUrl" :src="avatarUrl" alt="User Avatar" />
              <span v-else class="avatar-initials-sm">{{ initials }}</span>
            </div>
            <div>
              <div class="menu-name">{{ userName }}</div>
              <div class="menu-email">{{ userEmail }}</div>
            </div>
          </div>

          <div class="menu-divider"></div>

          <router-link to="/foundation/profile" class="menu-item" @click="menuOpen = false">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            My Profile
          </router-link>

          <router-link to="/foundation/help" class="menu-item" @click="menuOpen = false">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="3"/>
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            Help Center
          </router-link>

          <router-link to="/foundation/terms" class="menu-item" @click="menuOpen = false">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="3"/>
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            Terms of Services
          </router-link>

          <router-link to="/foundation/privacy" class="menu-item" @click="menuOpen = false">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="3"/>
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            Privacy and Policy
          </router-link>

          <router-link to="/foundation/contact" class="menu-item" @click="menuOpen = false">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="3"/>
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            Contact Us
          </router-link>

          <div class="menu-divider"></div>

          <button class="menu-item danger" @click="logout">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Logout
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import { useFoundation } from '../../composable/usefoundation'

const router = useRouter()
const route = useRoute()

const menuOpen = ref(false)
const avatarWrap = ref(null)

const notifOpen = ref(false)
const notifWrap = ref(null)
const notifications = ref([])
const unreadCount = ref(0)
const loadingNotifs = ref(false)
let pollTimer = null

const { foundation, loadFoundation } = useFoundation()
const foundationName = computed(() => foundation.value?.name || '')

const user = ref(null)

/* ══════════════════════════════
   PAGE TITLE
══════════════════════════════ */
const pageTitle = computed(() => {
  if (route.meta?.title) return route.meta.title
  const name = route.name?.toString() || 'Dashboard'
  const last = name.split('-').pop()
  return last.charAt(0).toUpperCase() + last.slice(1)
})

/* ══════════════════════════════
   USER / AVATAR
══════════════════════════════ */
function loadUserFromSession() {
  try {
    user.value = JSON.parse(sessionStorage.getItem('user') || 'null')
  } catch {
    user.value = null
  }
}

const userName = computed(() => {
  if (!user.value) return 'Foundation Admin'
  const full = [user.value.first_name, user.value.last_name].filter(Boolean).join(' ')
  return full || user.value.name || 'Foundation Admin'
})

const userEmail = computed(() => user.value?.email || '')

const initials = computed(() => {
  if (!user.value) return 'FA'
  const first = user.value.first_name || user.value.name || ''
  const last = user.value.last_name || ''
  return [first, last].filter(Boolean).map(w => w[0]).join('').toUpperCase().slice(0, 2) || 'FA'
})

const avatarUrl = computed(() => {
  if (!user.value?.profile_photo) return null
  return `/storage/${user.value.profile_photo}`
})

/* ══════════════════════════════
   NOTIFICATIONS
══════════════════════════════ */
async function loadNotifications() {
  loadingNotifs.value = true
  try {
    const res = await api.get('/foundation/notifications')
    notifications.value = res.data.notifications
    unreadCount.value = res.data.unread_count
  } catch (err) {
    console.error('Failed to load notifications:', err)
  } finally {
    loadingNotifs.value = false
  }
}

function toggleNotifs() {
  notifOpen.value = !notifOpen.value
  if (notifOpen.value) loadNotifications()
}

function getNotifLink(n) {
  switch (n.type) {
    case 'donation_received':
    case 'donation_approved':
    case 'donation_rejected':
      // NOTE: route is registered singular ('donation'), not 'donations'
      return '/foundation/donation'
    case 'campaign_update':
      return n.notifiable_id ? `/foundation/campaigns/${n.notifiable_id}` : '/foundation/campaigns'
    case 'foundation_verified':
    case 'foundation_suspended':
    case 'foundation_unsuspended':
      return '/foundation/dashboard'
    default:
      return null
  }
}

async function onNotifClick(n) {
  if (!n.read_at) {
    try {
      await api.post(`/foundation/notifications/${n.id}/read`)
      n.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (err) {
      console.error('Failed to mark as read:', err)
    }
  }
  notifOpen.value = false
  const link = getNotifLink(n)
  if (link) router.push(link)
}

async function onDeleteNotif(n) {
  try {
    await api.delete(`/foundation/notifications/${n.id}`)
    notifications.value = notifications.value.filter(item => item.id !== n.id)
    if (!n.read_at) unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch (err) {
    console.error('Failed to delete notification:', err)
  }
}

async function markAllRead() {
  try {
    await api.post('/foundation/notifications/seen')
    notifications.value = notifications.value.map(n => ({ ...n, read_at: n.read_at || new Date().toISOString() }))
    unreadCount.value = 0
  } catch (err) {
    console.error('Failed to mark all as read:', err)
  }
}

function formatTime(dateStr) {
  const diff = (Date.now() - new Date(dateStr).getTime()) / 1000
  if (diff < 60) return 'just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  return `${Math.floor(diff / 86400)}d ago`
}

/* ══════════════════════════════
   MENUS / CLICK OUTSIDE
══════════════════════════════ */
function toggleMenu() {
  menuOpen.value = !menuOpen.value
}

function handleClickOutside(e) {
  if (avatarWrap.value && !avatarWrap.value.contains(e.target)) {
    menuOpen.value = false
  }
  if (notifWrap.value && !notifWrap.value.contains(e.target)) {
    notifOpen.value = false
  }
}

function logout() {
  sessionStorage.removeItem('token')
  sessionStorage.removeItem('user')
  router.push('/login')
}

onMounted(() => {
  loadUserFromSession()
  loadFoundation()
  loadNotifications()

  document.addEventListener('click', handleClickOutside)
  window.addEventListener('user-updated', loadUserFromSession)

  pollTimer = setInterval(loadNotifications, 30000)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('user-updated', loadUserFromSession)
  if (pollTimer) clearInterval(pollTimer)
})
</script>

<style scoped>
.app-header {
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(0,0,0,0.06);
  position: sticky;
  top: 0;
  z-index: 50;
  box-shadow: 0 2px 10px rgba(0,0,0,0.04);
}

.header-title {
  font-size: 22px;
  font-weight: 800;
  color: #1a56c4;
  font-family: 'Segoe UI', sans-serif;
  margin: 0;
  letter-spacing: 0.3px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 14px;
}

.org-info {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 10px;
  background: #f4f7ff;
}
.org-name {
  font-size: 13px;
  font-weight: 600;
  color: #2f3b52;
}

/* NOTIFICATIONS */
.notif-wrap { position: relative; }

.notif-btn {
  width: 42px; height: 42px;
  border-radius: 50%;
  border: none; background: #f4f7ff;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; position: relative;
  color: #1a56c4;
  transition: background 0.2s;
}
.notif-btn:hover { background: #e9efff; }

.notif-badge {
  position: absolute; top: -2px; right: -2px;
  background: #dc2626; color: white;
  font-size: 0.65rem; font-weight: 700;
  min-width: 16px; height: 16px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  padding: 0 4px;
}

.notif-menu {
  position: absolute; top: calc(100% + 10px); right: 0;
  width: 320px; max-height: 400px;
  background: white; border: 1px solid #e2e8f0; border-radius: 14px;
  box-shadow: 0 10px 30px rgba(15,45,82,0.15);
  z-index: 60; overflow: hidden; display: flex; flex-direction: column;
}

.notif-menu-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 14px; border-bottom: 1px solid #f1f5f9;
  font-size: 0.85rem; font-weight: 700; color: #0F2D52;
}

.mark-all-btn {
  border: none; background: none; color: #1a56c4;
  font-size: 0.75rem; font-weight: 600; cursor: pointer;
}

.notif-empty {
  padding: 24px 14px; text-align: center; font-size: 0.82rem; color: #94a3b8;
}

.notif-list { overflow-y: auto; }

.notif-item {
  padding: 12px 14px; cursor: pointer; border-bottom: 1px solid #f8fafc;
  transition: background 0.15s;
}
.notif-item:hover { background: #f8fafc; }
.notif-item.unread { background: #f4f7ff; }
.notif-item.unread:hover { background: #e9efff; }

.notif-item-row {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 8px;
}

.notif-title { font-size: 0.83rem; font-weight: 700; color: #0F2D52; }

.notif-delete {
  border: none; background: none; color: #94a3b8; cursor: pointer;
  padding: 2px; flex-shrink: 0; border-radius: 4px; transition: color 0.15s, background 0.15s;
}
.notif-delete:hover { color: #dc2626; background: #fef2f2; }

.notif-message { font-size: 0.78rem; color: #64748b; margin-top: 2px; }
.notif-time { font-size: 0.7rem; color: #94a3b8; margin-top: 4px; }

/* AVATAR */
.avatar-wrap { position: relative; }

.avatar {
  width: 42px; height: 42px;
  border-radius: 50%; overflow: hidden;
  border: 2px solid #e8edf3;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: transform 0.2s ease;
  padding: 0; background: none; cursor: pointer; display: block;
}
.avatar:hover { transform: scale(1.05); }
.avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

.avatar-initials-sm {
  width: 100%; height: 100%;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #1a56c4, #133c8a);
  color: white; font-size: 0.8rem; font-weight: 800;
}

.avatar-menu {
  position: absolute; top: calc(100% + 10px); right: 0; width: 240px;
  background: white; border: 1px solid #e2e8f0; border-radius: 14px;
  box-shadow: 0 10px 30px rgba(15,45,82,0.15);
  padding: 8px; z-index: 60;
}

.menu-user {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 8px 12px;
}

.menu-avatar {
  width: 38px; height: 38px; min-width: 38px;
  border-radius: 50%; overflow: hidden;
}
.menu-avatar img { width: 100%; height: 100%; object-fit: cover; }

.menu-name  { font-size: 0.85rem; font-weight: 700; color: #0F2D52; }
.menu-email { font-size: 0.73rem; color: #94a3b8; }

.menu-divider { height: 1px; background: #f1f5f9; margin: 4px 0; }

.menu-item {
  display: flex; align-items: center; gap: 10px;
  width: 100%; padding: 9px 10px; border: none; background: none;
  border-radius: 8px; font-size: 0.83rem; font-weight: 500; color: #334155;
  text-decoration: none; cursor: pointer; text-align: left;
  transition: background 0.15s;
}
.menu-item:hover { background: #f4f7ff; color: #0F2D52; }
.menu-item.danger { color: #dc2626; }
.menu-item.danger:hover { background: #fef2f2; }
</style>