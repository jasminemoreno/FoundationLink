<template>
  <aside class="sidebar">

    <!-- LOGO -->
    <div class="sidebar-logo">
      <div class="logo-badge">
        <img v-if="foundationLogo" :src="foundationLogo" alt="Foundation Logo" class="logo-image" />
        <span v-else class="logo-initials">{{ initials }}</span>
      </div>

      <span class="logo-text">{{ foundationName || 'Foundation' }}</span>
    </div>

    <div class="sidebar-divider"></div>

    <!-- NAV -->
    <nav class="sidebar-nav">
      <router-link
        v-for="item in navItems"
        :key="item.name"
        :to="item.route"
        class="nav-item"
        :class="{ active: isActive(item.route) }"
      >
        <span class="nav-accent"></span>
        <span class="nav-icon-chip">
          <span class="nav-icon" v-html="item.icon"></span>
        </span>
        <span class="nav-label">{{ item.name }}</span>
      </router-link>
    </nav>

    <!-- FOOTER -->
    <div class="sidebar-footer">
      <button class="logout-btn" @click="logout">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none">
          <path
            d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        Log out
      </button>
    </div>

  </aside>
</template>

<script setup>
import { computed, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import api from "@/services/api"
import { useFoundation } from "../../composable/usefoundation"

const route = useRoute()
const router = useRouter()

const { foundation, loadFoundation, getLogoUrl } = useFoundation()

const foundationName = computed(() => foundation.value?.name || '')
const foundationLogo = computed(() => getLogoUrl())

const initials = computed(() => {
  if (!foundationName.value) return 'F'
  return foundationName.value
    .split(' ')
    .map(w => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const navItems = [
  {
    name: "Dashboard",
    route: "/foundation/dashboard",
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>`
  },
  {
    name: "Campaigns",
    route: "/foundation/campaign",
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>`
  },
  {
    name: "Donation",
    route: "/foundation/donation",
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`
  },
  {
    name: "Donors",
    route: "/foundation/donor",
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`
  },
  {
    name: "Reports",
    route: "/foundation/report",
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>`
  },
  {
    name: "Settings",
    route: "/foundation/setting",
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>`
  }
]

function isActive(path) {
  return route.path.startsWith(path)
}

async function logout() {
  try {
    await api.post("/logout")
    sessionStorage.removeItem("token")
    router.push("/login")
  } catch (err) {
    console.error(err)
  }
}

onMounted(() => {
  loadFoundation()
})
</script>

<style scoped>
.sidebar {
  width: 290px;
  background:
    radial-gradient(120% 55% at 15% 0%, rgba(255,255,255,0.10) 0%, rgba(255,255,255,0) 60%),
    linear-gradient(180deg, #1a56c4 0%, #17469f 45%, #0f2f75 100%);
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
  z-index: 100;
  overflow-y: auto;
  scrollbar-width: none;
  box-shadow: 6px 0 30px rgba(9, 27, 66, 0.25);
}

.sidebar::-webkit-scrollbar { display: none; }

/* LOGO */
.sidebar-logo {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32px 20px 22px;
}

.logo-badge {
  width: 92px;
  height: 92px;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow:
    0 0 0 6px rgba(255, 255, 255, 0.04),
    0 8px 24px rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  overflow: hidden;
  flex-shrink: 0;
}

.logo-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.logo-initials {
  font-size: 30px;
  font-weight: 800;
  color: #fff;
  letter-spacing: 0.02em;
}

.logo-text {
  color: #fff;
  font-size: 20px;
  font-weight: 800;
  letter-spacing: 0.01em;
  text-align: center;
  line-height: 1.3;
  max-width: 100%;
  padding: 0 4px;

  /* Wrap up to 2 lines instead of truncating with ellipsis,
     so longer foundation names stay fully readable */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;

  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
}

.sidebar-divider {
  height: 1px;
  margin: 0 20px 10px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.16), transparent);
}

/* NAV */
.sidebar-nav {
  flex: 1;
  padding: 10px 15px;
}

.nav-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 13px 16px 13px 20px;
  margin-bottom: 4px;
  border-radius: 12px;
  color: rgba(255, 255, 255, 0.68);
  text-decoration: none;
  transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
}

.nav-accent {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%) scaleY(0);
  width: 3px;
  height: 22px;
  border-radius: 3px;
  background: #7eb0ff;
  box-shadow: 0 0 10px rgba(126, 176, 255, 0.7);
  transition: transform 0.2s ease;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.07);
  color: #fff;
}

.nav-item.active {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  font-weight: 700;
}

.nav-item.active .nav-accent {
  transform: translateY(-50%) scaleY(1);
}

/* ICON */
.nav-icon {
  display: flex;
  min-width: 26px;
  opacity: 0.95;
}

/* LABEL */
.nav-label {
  font-size: 15px;
  font-weight: 600;
}

/* FOOTER */
.sidebar-footer {
  padding: 18px 20px 22px;
}

.logout-btn {
  width: 100%;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1.5px solid rgba(248, 113, 113, 0.4);
  border-radius: 12px;
  background: rgba(248, 113, 113, 0.08);
  color: #ff9b9b;
  font-weight: 700;
  font-size: 14.5px;
  cursor: pointer;
  transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}

.logout-btn:hover {
  background: #fff;
  border-color: #fff;
  color: #e53e3e;
}

.logout-btn:focus-visible,
.nav-item:focus-visible {
  outline: 2px solid #7eb0ff;
  outline-offset: 2px;
}
</style>