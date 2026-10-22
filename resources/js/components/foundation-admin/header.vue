<template>
  <header class="app-header">
    <div class="header-left">
      <!-- Small screens only: opens the slide-in menu -->
      <button type="button" class="menu-btn" aria-label="Open menu" :aria-expanded="sidebarOpen" @click="openSidebar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
          <path d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
      <h1 class="header-title">{{ pageTitle }}</h1>
    </div>

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
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Help Center
          </router-link>

          <router-link to="/foundation/terms" class="menu-item" @click="menuOpen = false">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Terms of Service
          </router-link>

          <router-link to="/foundation/privacy" class="menu-item" @click="menuOpen = false">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Privacy Policy
          </router-link>

          <router-link to="/foundation/contact" class="menu-item" @click="menuOpen = false">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
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
import { useFoundationSidebar } from '../../composable/useFoundationSidebar'

const { sidebarOpen, openSidebar } = useFoundationSidebar()
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
// Titles match the sidebar labels (route names are singular, e.g. foundation-campaign)
const PAGE_TITLES = {
  'foundation-dashboard': 'Dashboard',
  'foundation-campaign':  'Campaigns',
  'foundation-donation':  'Donations',
  'foundation-donor':     'Donors',
  'foundation-report':    'Reports',
  'foundation-setting':   'Settings',
  'foundation-profile':   'My Profile',
  'foundation-help':      'Help Center',
  'foundation-terms':     'Terms of Service',
  'foundation-privacy':   'Privacy Policy',
  'foundation-contact':   'Contact Us',
}

const pageTitle = computed(() => {
  if (route.meta?.title) return route.meta.title
  if (PAGE_TITLES[route.name]) return PAGE_TITLES[route.name]
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
  color: var(--fa-primary);
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
  background: var(--fa-primary-tint);
}
.org-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--fa-text);
}

/* NOTIFICATIONS */
.notif-wrap { position: relative; }

.notif-btn {
  width: 42px; height: 42px;
  border-radius: 50%;
  border: none; background: var(--fa-primary-tint);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; position: relative;
  color: var(--fa-primary);
  transition: background 0.2s;
}
.notif-btn:hover { background: var(--fa-primary-soft); }

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
  background: white; border: 1px solid var(--fa-border); border-radius: 14px;
  box-shadow: 0 10px 30px rgba(30,58,138,0.15);
  z-index: 60; overflow: hidden; display: flex; flex-direction: column;
}

.notif-menu-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 14px; border-bottom: 1px solid #f1f5f9;
  font-size: 0.85rem; font-weight: 700; color: var(--fa-heading);
}

.mark-all-btn {
  border: none; background: none; color: var(--fa-primary);
  font-size: 0.75rem; font-weight: 600; cursor: pointer;
}

.notif-empty {
  padding: 24px 14px; text-align: center; font-size: 0.82rem; color: var(--fa-muted);
}

.notif-list { overflow-y: auto; }

.notif-item {
  padding: 12px 14px; cursor: pointer; border-bottom: 1px solid #f8fafc;
  transition: background 0.15s;
}
.notif-item:hover { background: #f8fafc; }
.notif-item.unread { background: var(--fa-primary-tint); }
.notif-item.unread:hover { background: var(--fa-primary-soft); }

.notif-item-row {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 8px;
}

.notif-title { font-size: 0.83rem; font-weight: 700; color: var(--fa-heading); }

.notif-delete {
  border: none; background: none; color: var(--fa-muted); cursor: pointer;
  padding: 2px; flex-shrink: 0; border-radius: 4px; transition: color 0.15s, background 0.15s;
}
.notif-delete:hover { color: var(--fa-danger); background: #fef2f2; }

.notif-message { font-size: 0.78rem; color: var(--fa-muted); margin-top: 2px; }
.notif-time { font-size: 0.7rem; color: var(--fa-muted); margin-top: 4px; }

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
  background: linear-gradient(135deg, var(--fa-primary), var(--fa-primary-hover));
  color: white; font-size: 0.8rem; font-weight: 800;
}

.avatar-menu {
  position: absolute; top: calc(100% + 10px); right: 0; width: 240px;
  background: white; border: 1px solid var(--fa-border); border-radius: 14px;
  box-shadow: 0 10px 30px rgba(30,58,138,0.15);
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

.menu-name  { font-size: 0.85rem; font-weight: 700; color: var(--fa-heading); }
.menu-email { font-size: 0.73rem; color: var(--fa-muted); }

.menu-divider { height: 1px; background: #f1f5f9; margin: 4px 0; }

.menu-item {
  display: flex; align-items: center; gap: 10px;
  width: 100%; padding: 9px 10px; border: none; background: none;
  border-radius: 8px; font-size: 0.83rem; font-weight: 500; color: var(--fa-text);
  text-decoration: none; cursor: pointer; text-align: left;
  transition: background 0.15s;
}
.menu-item:hover { background: var(--fa-primary-tint); color: var(--fa-heading); }
.menu-item.danger { color: var(--fa-danger); }
.menu-item.danger:hover { background: #fef2f2; }

/* MENU BUTTON (small screens) */
.header-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
.menu-btn { display: none; }

@media (max-width: 1023px) {
  .app-header { padding: 0 16px; height: 62px; }
  .menu-btn {
    display: flex; align-items: center; justify-content: center;
    width: 40px; height: 40px; flex-shrink: 0;
    border: 1px solid var(--fa-border); border-radius: 10px;
    background: var(--fa-surface); color: var(--fa-primary); cursor: pointer;
  }
  .menu-btn:focus-visible { outline: 2px solid var(--fa-primary); outline-offset: 2px; }
  .header-title { font-size: 19px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  /* dropdowns never wider than the screen */
  .notif-menu { width: min(320px, calc(100vw - 32px)); }
}

@media (max-width: 600px) {
  .org-info { display: none; } /* the foundation name is already in the menu */
  .header-right { gap: 10px; }
}
</style>