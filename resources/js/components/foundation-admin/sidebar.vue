<template>
  <aside class="sidebar" :class="{ open: sidebarOpen }" aria-label="Main menu">

    <!-- Small screens only: close the slide-in menu -->
    <button type="button" class="sidebar-close" aria-label="Close menu" @click="closeSidebar">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
        <path d="M18 6 6 18M6 6l12 12"/>
      </svg>
    </button>

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
import { useFoundationSidebar } from "../../composable/useFoundationSidebar"

const { sidebarOpen, closeSidebar } = useFoundationSidebar()
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
    // Dashboard: overview panels (outline, to match the other icons)
    name: "Dashboard",
    route: "/foundation/dashboard",
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>`
  },
  {
    // Campaigns: megaphone (same idea as the super admin's Campaigns)
    name: "Campaigns",
    route: "/foundation/campaign",
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>`
  },
  {
    // Donations: hand holding a heart = giving (a plain heart reads as "like")
    name: "Donations",
    route: "/foundation/donation",
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 14h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 16"/><path d="m7 20 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"/><path d="m2 15 6 6"/><path d="M19.5 8.5c.7-.7 1.5-1.6 1.5-2.7A2.73 2.73 0 0 0 16 4a2.78 2.78 0 0 0-5 1.8c0 1.2.8 2 1.5 2.8L16 12Z"/></svg>`
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
/* Light sidebar: white panel, dark readable text, soft-blue highlight for the
   current page. Colours come from assets/foundation-theme.css. */
.sidebar {
  width: 290px;
  background: var(--fa-surface);
  border-right: 1px solid var(--fa-border);
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
  z-index: 100;
  overflow-y: auto;
  scrollbar-width: none;
  box-shadow: 2px 0 12px rgba(30, 58, 138, 0.05);
}

.sidebar::-webkit-scrollbar { display: none; }

/* LOGO */
.sidebar-logo {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 30px 20px 20px;
}

.logo-badge {
  width: 84px;
  height: 84px;
  border-radius: 22px;
  background: linear-gradient(135deg, var(--fa-primary-soft), #dbe8fd);
  border: 1px solid var(--fa-primary-border);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
  overflow: hidden;
  flex-shrink: 0;
}

.logo-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.logo-initials {
  font-size: 28px;
  font-weight: 800;
  color: var(--fa-primary);
  letter-spacing: 0.02em;
}

.logo-text {
  color: var(--fa-heading);
  font-size: 18px;
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
}

.sidebar-divider {
  height: 1px;
  margin: 0 20px 10px;
  background: var(--fa-border);
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
  gap: 12px;
  padding: 11px 14px 11px 16px;
  margin-bottom: 4px;
  border-radius: 12px;
  color: var(--fa-text);
  text-decoration: none;
  transition: background 0.2s ease, color 0.2s ease;
}

.nav-accent {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%) scaleY(0);
  width: 4px;
  height: 22px;
  border-radius: 0 4px 4px 0;
  background: var(--fa-primary);
  transition: transform 0.2s ease;
}

.nav-item:hover {
  background: var(--fa-primary-tint);
  color: var(--fa-primary-hover);
}

.nav-item.active {
  background: var(--fa-primary-soft);
  color: var(--fa-primary-hover);
  font-weight: 700;
}

.nav-item.active .nav-accent {
  transform: translateY(-50%) scaleY(1);
}

/* ICON CHIP: soft square behind each icon; turns solid blue on the current page */
.nav-icon-chip {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--fa-primary-tint);
  color: var(--fa-primary);
  flex-shrink: 0;
  transition: background 0.2s ease, color 0.2s ease;
}

.nav-item.active .nav-icon-chip {
  background: var(--fa-primary);
  color: #fff;
}

.nav-icon {
  display: flex;
}

/* LABEL */
.nav-label {
  font-size: 15px;
  font-weight: 600;
}

/* FOOTER */
.sidebar-footer {
  padding: 18px 20px 22px;
  border-top: 1px solid var(--fa-border-soft);
}

.logout-btn {
  width: 100%;
  height: 46px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1.5px solid var(--fa-danger-border);
  border-radius: 12px;
  background: var(--fa-danger-bg);
  color: var(--fa-danger);
  font-weight: 700;
  font-size: 14.5px;
  cursor: pointer;
  transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}

.logout-btn:hover {
  background: var(--fa-danger-solid);
  border-color: var(--fa-danger-solid);
  color: #fff;
}

.logout-btn:focus-visible,
.nav-item:focus-visible {
  outline: 2px solid var(--fa-primary);
  outline-offset: 2px;
}

.sidebar-close { display: none; }

/* Phones / small tablets: off-screen until the header's menu button opens it */
@media (max-width: 1023px) {
  .sidebar {
    width: min(290px, 86vw);
    transform: translateX(-100%);
    transition: transform 0.25s ease;
    box-shadow: none;
  }
  .sidebar.open {
    transform: translateX(0);
    box-shadow: 8px 0 30px rgba(15, 23, 42, 0.2);
  }
  .sidebar-close {
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: 12px;
    right: 12px;
    width: 36px;
    height: 36px;
    border: 1px solid var(--fa-border);
    border-radius: 10px;
    background: var(--fa-surface);
    color: var(--fa-text-2);
    cursor: pointer;
  }
  .sidebar-close:focus-visible { outline: 2px solid var(--fa-primary); outline-offset: 2px; }
}

@media (prefers-reduced-motion: reduce) {
  .sidebar { transition: none; }
}
</style>
