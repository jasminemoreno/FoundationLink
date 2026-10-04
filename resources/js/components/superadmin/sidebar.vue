<template>
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="logo-wrap">
        <div class="logo-icon">
  <img src="../../assets//img//logo.png" alt="FoundationLink logo" />
</div>
        <div>
          <h2>Foundation<span>Link</span></h2>
          <p>Super Administrator</p>
        </div>
      </div>
    </div>

    <div class="sidebar-section-label">MAIN MENU</div>

    <nav class="menu">
      <router-link to="/admin/dashboard" class="menu-item">
        <span class="icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
          </svg>
        </span>
        <span class="label">Dashboard</span>
      </router-link>

      <router-link to="/admin/approval" class="menu-item">
        <span class="icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M9 12l2 2 4-4"/><path d="M21 12c0 4.97-4.03 9-9 9S3 16.97 3 12 7.03 3 12 3s9 4.03 9 9z"/>
          </svg>
        </span>
        <span class="label">Foundation Approvals</span>
        <span v-if="pendingCount > 0" class="pill">{{ pendingCount > 99 ? '99+' : pendingCount }}</span>
      </router-link>

      <router-link to="/admin/foundation" class="menu-item">
        <span class="icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/>
          </svg>
        </span>
        <span class="label">Foundations</span>
      </router-link>

      <router-link to="/admin/campaign" class="menu-item">
        <span class="icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
  <path d="m3 11 18-5v12L3 14v-3z"/>
  <path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>
</svg>
        </span>
        <span class="label">Campaigns</span>
      </router-link>

      <router-link to="/admin/category" class="menu-item">
        <span class="icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M4 6h16M4 12h8m-8 6h6"/>
          </svg>
        </span>
        <span class="label">Categories</span>
      </router-link>

      <router-link to="/admin/payment-method" class="menu-item">
        <span class="icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <rect x="1" y="4" width="22" height="16" rx="2"/>
            <line x1="1" y1="10" x2="23" y2="10"/>
          </svg>
        </span>
        <span class="label">Payment Methods</span>
      </router-link>
    </nav>

    <div class="sidebar-section-label">MANAGEMENT</div>

    <nav class="menu">
      <router-link to="/admin/user" class="menu-item">
        <span class="icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </span>
        <span class="label">Users</span>
      </router-link>

      <router-link to="/admin/report" class="menu-item">
        <span class="icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>
          </svg>
        </span>
        <span class="label">Reports</span>
      </router-link>

      <router-link to="/admin/setting" class="menu-item">
        <span class="icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
          </svg>
        </span>
        <span class="label">Settings</span>
      </router-link>
    </nav>

    <div class="sidebar-footer">
      <button class="logout-btn" @click="logout">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Logout
      </button>
    </div>
  </aside>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

const pendingCount = ref(0)
let pollTimer = null

async function loadPendingCount() {
  try {
    const res = await api.get('/admin/foundations/pending-count')
    pendingCount.value = res.data.count
  } catch (err) {
    console.error('Failed to load pending approvals count:', err)
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
  loadPendingCount()
  // poll every 30s so the badge stays current without a full page reload
  pollTimer = setInterval(loadPendingCount, 30000)
})

onBeforeUnmount(() => {
  if (pollTimer) clearInterval(pollTimer)
})
</script>

<style scoped>
.sidebar {
  width: 265px;
  min-width: 265px;
  height: 100vh;
  background: #F0FDFA;
  color: #115E59;
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  left: 0;
  overflow-y: auto;
  scrollbar-width: none;
  border-right: 1px solid #99F6E4;
}

.sidebar::-webkit-scrollbar { display: none; }

.sidebar-header {
  height: 70px;
  padding: 0 20px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid #99F6E4;
  box-sizing: border-box;
}
.logo-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-icon {
  width: 44px;
  height: 44px;
  min-width: 44px;
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white; /* or transparent, depending on your logo */
  box-shadow: 0 4px 12px rgba(212,175,55,0.3);
}

.logo-icon img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
h2 {
  font-size: 1rem;
  font-weight: 700;
  color: #115E59;
  margin: 0;
  line-height: 1.2;
}

h2 span { color: #b8901f; }

p {
  font-size: 0.72rem;
  color: #4d9c92;
  margin: 2px 0 0;
}

.sidebar-section-label {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: #67b0a5;
  padding: 18px 20px 6px;
}

.menu {
  padding: 0 12px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 11px 12px;
  color: #0F766E;
  text-decoration: none;
  border-radius: 10px;
  margin-bottom: 2px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
  position: relative;
}

.menu-item:hover {
  background: #CCFBF1;
  color: #115E59;
}

.menu-item .icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(17,94,89,0.05);
  flex-shrink: 0;
  transition: all 0.2s;
}

.menu-item:hover .icon {
  background: rgba(17,94,89,0.1);
}

.label { flex: 1; }

.pill {
  background: #D4AF37;
  color: #115E59;
  font-size: 0.68rem;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 20px;
}

.router-link-active {
  background: linear-gradient(135deg, rgba(212,175,55,0.18), rgba(212,175,55,0.06));
  color: #9a7515;
  border: 1px solid rgba(212,175,55,0.35);
}

.router-link-active .icon {
  background: rgba(212,175,55,0.2);
  color: #b8901f;
}

.sidebar-footer {
  margin-top: auto;
  padding: 16px 12px;
  border-top: 1px solid #99F6E4;
}

.logout-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border: none;
  background: rgba(239,68,68,0.08);
  color: #dc2626;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.logout-btn:hover {
  background: rgba(239,68,68,0.15);
}
</style>