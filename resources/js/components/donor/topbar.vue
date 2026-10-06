<template>
  <header class="topbar">
    <div class="topbar-inner">

      <!-- LOGO -->
      <router-link to="/donor/dashboard" class="logo">
        <img src="@/assets/img/logo.png" alt="logo" class="logo-img" />
        <div>
          <span class="logo-name">Foundation<span class="logo-accent">Link</span></span>
          <span class="logo-sub">Monetary and Item Donation</span>
        </div>
      </router-link>

      <!-- NAV -->
      <nav class="nav">
        <router-link
          v-for="link in navLinks" :key="link.to"
          :to="link.to"
          class="nav-link"
          :class="{ 'nav-active': isActive(link.match) }"
        >{{ link.label }}</router-link>
      </nav>

      <!-- RIGHT -->
      <div class="topbar-right">
        <!-- NOTIFICATIONS -->
        <button class="notif-btn" :class="{ 'has-unread': unreadCount > 0 }" @click="onNotifClick">
          <svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
        </button>

        <!-- PROFILE DROPDOWN -->
        <div class="profile-wrap" ref="profileRef">
          <button class="profile-btn" :class="{ open: showDropdown }" @click="showDropdown = !showDropdown">
            <div class="avatar">
              <img v-if="user?.profile_photo" :src="getImage(user.profile_photo)" alt="" />
              <span v-else>{{ initials }}</span>
            </div>
            <span class="profile-name">{{ user?.first_name }}</span>
            <svg class="chevron" :class="{ open: showDropdown }" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>

          <transition name="dropdown-fade">
            <div v-if="showDropdown" class="dropdown">
              <div class="dropdown-header">
                <div class="dh-avatar">
                  <img v-if="user?.profile_photo" :src="getImage(user.profile_photo)" alt="" />
                  <span v-else>{{ initials }}</span>
                </div>
                <div>
                  <div class="dh-name">{{ user?.first_name }} {{ user?.last_name }}</div>
                  <div class="dh-email">{{ user?.email }}</div>
                </div>
              </div>
              <div class="dropdown-divider"></div>
              <router-link to="/donor/profile" class="dropdown-item" @click="showDropdown = false">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
                My Profile
              </router-link>
              <router-link to="/donor/donations" class="dropdown-item" @click="showDropdown = false">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                My Donations
              </router-link>
              <div class="dropdown-divider"></div>
              <button class="dropdown-item logout" @click="logout">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                  <polyline points="16 17 21 12 16 7"/>
                  <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Logout
              </button>
            </div>
          </transition>
        </div>
      </div>

    </div>
  </header>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'

const router       = useRouter()
const route        = useRoute()
const showDropdown = ref(false)
const profileRef   = ref(null)
const unreadCount  = ref(0)

// how often the profile re-checks the server while the page is open
const PROFILE_POLL_MS = 30000
let profileTimer = null

const navLinks = [
  { to: '/donor/dashboard',   label: 'Home',        match: '/donor/dashboard'   },
  { to: '/donor/foundations', label: 'Foundations', match: '/donor/foundations' },
  { to: '/donor/campaigns',   label: 'Campaigns',    match: '/donor/campaigns'   },
  { to: '/donor/donations',   label: 'Donations',    match: '/donor/donations'   },

]

// prefix match so nested routes (e.g. /donor/foundations/5) still highlight their parent nav item
function isActive(matchPath) {
  return route.path === matchPath || route.path.startsWith(matchPath + '/')
}

const user = ref(loadUserFromStorage())

function loadUserFromStorage() {
  try { return JSON.parse(sessionStorage.getItem('user') || 'null') }
  catch { return null }
}

function handleUserUpdated(e) {
  user.value = e.detail || loadUserFromStorage()
}

/* ── REFRESH USER FROM SERVER ──
   Picks up changes made elsewhere (e.g. the mobile app). Saves to
   sessionStorage and fires 'user-updated' so every listener (this topbar,
   the Dashboard greeting) updates together. Only fires when something
   actually changed. */
async function refreshUser() {
  const current = loadUserFromStorage()
  if (!current) return
  try {
    const res    = await api.get('/donor/profile')
    const merged = { ...current, ...res.data }

    const changed =
      merged.first_name    !== current.first_name ||
      merged.last_name     !== current.last_name  ||
      merged.profile_photo !== current.profile_photo ||
      merged.email         !== current.email

    if (!changed) return

    sessionStorage.setItem('user', JSON.stringify(merged))
    window.dispatchEvent(new CustomEvent('user-updated', { detail: merged }))
  } catch (err) {
    console.error(err)
  }
}

// browser tab becomes visible again
function handleVisibility() {
  if (document.visibilityState === 'visible') refreshUser()
}

// browser window gets focus (covers side-by-side windows, where
// visibilitychange does not fire)
function handleFocus() {
  refreshUser()
}

// refetch whenever the donor navigates to another page
watch(() => route.path, () => { refreshUser() })

const initials = computed(() => {
  if (!user.value) return 'D'
  return ((user.value.first_name?.[0] || '') + (user.value.last_name?.[0] || '')).toUpperCase()
})

function getImage(path) {
  if (!path) return ''
  return `/storage/${path}`
}

/* ── NOTIFICATION COUNT ── */
async function loadUnreadCount() {
  try {
    const res = await api.get('/donor/notifications/count')
    unreadCount.value = res.data.count ?? 0
  } catch (err) {
    console.error(err)
  }
}

async function onNotifClick() {
  // optimistic clear, like Facebook
  unreadCount.value = 0
  router.push('/donor/notifications')
  try {
    await api.post('/donor/notifications/seen')
  } catch (err) {
    console.error(err)
  }
}

async function logout() {
  try {
    await api.post('/logout')
  } catch {}
  sessionStorage.clear()
  router.push('/login')
}

// close dropdown on outside click
function handleClick(e) {
  if (profileRef.value && !profileRef.value.contains(e.target)) {
    showDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClick)
  document.addEventListener('visibilitychange', handleVisibility)
  window.addEventListener('focus', handleFocus)
  window.addEventListener('user-updated', handleUserUpdated)
  loadUnreadCount()
  refreshUser()
  profileTimer = setInterval(() => {
    if (document.visibilityState === 'visible') refreshUser()
  }, PROFILE_POLL_MS)
})
onUnmounted(() => {
  document.removeEventListener('click', handleClick)
  document.removeEventListener('visibilitychange', handleVisibility)
  window.removeEventListener('focus', handleFocus)
  window.removeEventListener('user-updated', handleUserUpdated)
  if (profileTimer) clearInterval(profileTimer)
})
</script>

<style scoped>
.topbar {
  background: rgba(255,255,255,0.92);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid #e9f5ee;
  box-shadow: 0 1px 0 rgba(0,60,30,0.03), 0 4px 20px rgba(0,60,30,0.04);
  position: sticky;
  top: 0;
  z-index: 50;
}
.topbar-inner { max-width: 1200px; margin: 0 auto; padding: 0 24px; height: 68px; display: flex; align-items: center; gap: 32px; }

.logo { display: flex; align-items: center; gap: 11px; text-decoration: none; flex-shrink: 0; transition: opacity 0.15s; }
.logo:hover { opacity: 0.85; }
.logo-img  { width: 40px; height: 40px; object-fit: contain; }
.logo-name { display: block; font-size: 1.12rem; font-weight: 900; color: #1a8a52; letter-spacing: -0.01em; }
.logo-accent { color: #0F2D52; }
.logo-sub  { display: block; font-size: 0.64rem; color: #9caba3; font-weight: 600; letter-spacing: 0.02em; }

/* NAV */
.nav { display: flex; align-items: center; gap: 2px; flex: 1; justify-content: center; }
.nav-link {
  position: relative;
  padding: 9px 18px;
  font-size: 0.88rem;
  font-weight: 650;
  color: #5b6f66;
  text-decoration: none;
  border-radius: 10px;
  transition: color 0.18s, background 0.18s;
}
.nav-link:hover { color: #1a8a52; background: #f0fdf4; }

.nav-active {
  color: #1a8a52 !important;
  font-weight: 800;
  background: #eafcf1;
}
.nav-active::after {
  content: '';
  position: absolute;
  left: 18px; right: 18px; bottom: 4px;
  height: 2.5px;
  border-radius: 99px;
  background: #1a8a52;
}

/* RIGHT SIDE */
.topbar-right { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }

.notif-btn {
  position: relative; width: 40px; height: 40px; border: none; background: #f0fdf4;
  border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center;
  color: #1a8a52; transition: background 0.18s, transform 0.15s;
}
.notif-btn:hover { background: #dcfce7; transform: translateY(-1px); }
.notif-btn.has-unread { animation: notif-pulse 2.2s ease-in-out infinite; }
@keyframes notif-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(26,138,82,0.25); }
  50% { box-shadow: 0 0 0 6px rgba(26,138,82,0); }
}
.notif-badge {
  position: absolute; top: 1px; right: 1px; min-width: 17px; height: 17px; padding: 0 4px;
  background: #dc2626; color: white; font-size: 0.62rem; font-weight: 800; border-radius: 20px;
  display: flex; align-items: center; justify-content: center; border: 2px solid white;
}

.profile-wrap { position: relative; }
.profile-btn {
  display: flex; align-items: center; gap: 9px; padding: 6px 13px 6px 6px;
  border: 1px solid #e6f2ea; background: #fbfdfc; border-radius: 12px; cursor: pointer;
  transition: all 0.18s; color: #445c50; font-size: 0.87rem; font-weight: 650;
}
.profile-btn:hover, .profile-btn.open { border-color: #1a8a52; color: #1a8a52; background: #f0fdf4; }

.avatar {
  width: 30px; height: 30px; border-radius: 9px;
  background: linear-gradient(135deg, #1a8a52, #0e5c36);
  color: white; font-size: 0.7rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;
}
.avatar img { width: 100%; height: 100%; object-fit: cover; }

.chevron { transition: transform 0.2s; color: #9caba3; }
.chevron.open { transform: rotate(180deg); }

.dropdown {
  position: absolute; top: calc(100% + 10px); right: 0; width: 250px; background: white;
  border-radius: 16px; border: 1px solid #edf6f0; box-shadow: 0 16px 44px rgba(0,40,20,0.14);
  z-index: 100; overflow: hidden;
}

.dropdown-fade-enter-active, .dropdown-fade-leave-active { transition: opacity 0.15s, transform 0.15s; }
.dropdown-fade-enter-from, .dropdown-fade-leave-to { opacity: 0; transform: translateY(-6px); }

.dropdown-header { display: flex; align-items: center; gap: 11px; padding: 16px; background: linear-gradient(135deg, #f0fdf4, #eafcf1); }
.dh-avatar {
  width: 38px; height: 38px; border-radius: 11px;
  background: linear-gradient(135deg, #1a8a52, #0e5c36);
  color: white; font-size: 0.82rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;
}
.dh-avatar img { width: 100%; height: 100%; object-fit: cover; }
.dh-name  { font-size: 0.86rem; font-weight: 750; color: #0F2D52; }
.dh-email { font-size: 0.72rem; color: #94a3b8; }

.dropdown-divider { height: 1px; background: #f1f5f9; }

.dropdown-item {
  display: flex; align-items: center; gap: 11px; width: 100%; padding: 12px 16px;
  font-size: 0.85rem; font-weight: 620; color: #47605a; text-decoration: none;
  background: transparent; border: none; cursor: pointer; transition: background 0.15s, color 0.15s;
  text-align: left;
}
.dropdown-item svg { color: #9caba3; flex-shrink: 0; transition: color 0.15s; }
.dropdown-item:hover { background: #f6fbf8; color: #1a8a52; }
.dropdown-item:hover svg { color: #1a8a52; }
.dropdown-item.logout { color: #dc2626; }
.dropdown-item.logout svg { color: #ef8f8f; }
.dropdown-item.logout:hover { background: #fef2f2; }
.dropdown-item.logout:hover svg { color: #dc2626; }

@media (max-width: 860px) {
  .logo-sub { display: none; }
  .nav-link { padding: 9px 12px; font-size: 0.83rem; }
  .profile-name { display: none; }
}
</style>